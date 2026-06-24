<?php

namespace EWW\Dpf\Command;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use EWW\Dpf\Domain\Model\Client;
use EWW\Dpf\Services\ElasticSearch\ElasticSearch;
use EWW\Dpf\Services\ElasticSearch\PublicElasticSearch;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Pool;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class IndexByFile
 *
 * A console command to import METS/MODS-Files into the KITODO Publication ElasticSearch index.
 * Usage: vendor/bin/typo3 dpf:indexByFile <client> <filename>
 * @package EWW\Dpf\Command
 */
class IndexByFile extends AbstractIndexCommand
{
    private const BULK_SIZE = 500;

    /** @var bool */
    protected $publicIndex = false;

    /** @var \EWW\Dpf\Domain\Model\Document[] */
    private $bulkBuffer = [];

    /** @var ElasticSearch|null */
    private $backofficeEs = null;

    /** @var PublicElasticSearch|null */
    private $publicEs = null;

    /**
     * Configure the command by defining arguments
     */
    protected function configure()
    {
        $this->setDescription('Index METS/MODS file as document of the given client');
        $this->addArgument('client', InputArgument::REQUIRED, 'The UID of the client.');
        $this->addArgument('filename', InputArgument::REQUIRED, 'The full path to the file containing the METS/MODS-Data.');
        $this->addOption('linklist', 'L', InputOption::VALUE_NONE, 'The given file contains URIs to METS/MODS documents.');
        $this->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'Specify the user name and password to use for server authentication.');
        $this->addOption('public', null, InputOption::VALUE_NONE, 'Index into the public search index instead of the backoffice index.');
        $this->addOption('concurrency', 'c', InputOption::VALUE_OPTIONAL, 'Number of concurrent Fedora HTTP requests when using --linklist.', 8);
    }

    /**
     * Executes the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $clientUid = $input->getArgument('client');
        $filename = $input->getArgument('filename');
        $linklist = $input->getOption('linklist');
        $credentials = $input->getOption('user');
        $this->publicIndex = (bool) $input->getOption('public');

        if (!file_exists($filename)) {
            $io->error("File `$filename` not found.");
            return 1;
        }

        // setup client configuration manager for later dependency injection
        /** @var Client $client */
        $client = $this->clientRepository->findByUid($clientUid);
        if (!$client) {
            $io->error("Unknown client '" . $clientUid . "'");
            return 1;
        }
        $this->clientConfigurationManager->switchToClient($clientUid);

        $io->title("Indexing: '" . $filename . "' for client '" . $client->getClient() . "'");

        // Use the storagePid of client record on other repositories
        $this->documentTypeRepository->setStoragePid($client->getPid());
        $this->documentRepository->setStoragePid($client->getPid());

        $result = true;

        if ($linklist) {
            $concurrency = max(1, (int) $input->getOption('concurrency'));
            $result = $this->indexLinklist($filename, $io, $credentials, $concurrency);
        } else {
            $result = $this->indexFile($filename, $io);
            $this->flushBulkBuffer($io);
        }

        if ($result === true) {
            $io->writeln("Successfully indexed contents of `$filename`");
            return 0;
        } else {
            $io->error("There where errors.");
            return 1;
        }
    }

    private function indexLinklist(string $filename, SymfonyStyle $io, ?string $credentials, int $concurrency): bool
    {
        $fn = null;
        $urls = [];
        try {
            $fn = fopen($filename, "r");
            if ($fn === false) {
                $io->error("Could not open file: " . $filename);
                return false;
            }
            while (!feof($fn)) {
                $uri = trim(fgets($fn));
                if ($uri) {
                    $urls[] = $uri;
                }
            }
        } finally {
            if ($fn) {
                fclose($fn);
            }
        }

        $requestConfig = ['http_errors' => false];
        if ($credentials) {
            $requestConfig['auth'] = explode(':', $credentials, 2);
        }
        $httpClient = new HttpClient();

        $result = true;

        $requests = function () use ($urls, $httpClient, $requestConfig) {
            foreach ($urls as $uri) {
                yield $uri => function () use ($httpClient, $uri, $requestConfig) {
                    return $httpClient->getAsync($uri, $requestConfig);
                };
            }
        };

        $pool = new Pool($httpClient, $requests(), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $uri) use ($io, &$result) {
                if (!$this->handleFulfilledResponse($response, $uri, $io)) {
                    $result = false;
                }
            },
            'rejected' => function ($reason, $uri) use ($io, &$result) {
                $io->writeln("$uri Error: {$reason->getMessage()}");
                $result = false;
            },
        ]);

        $pool->promise()->wait();
        $this->flushBulkBuffer($io);

        return $result;
    }

    private function handleFulfilledResponse($response, string $uri, SymfonyStyle $io): bool
    {
        if ($response->getStatusCode() !== 200) {
            $io->writeln("$uri Error: HTTP {$response->getStatusCode()} {$response->getReasonPhrase()}");
            return false;
        }
        $contentType = $response->getHeader('Content-Type')[0] ?? '';
        if (!preg_match('/^(text|application)\/(.*)xml(.*)$/', $contentType)) {
            $io->writeln("$uri Error: unexpected content type `$contentType`");
            return false;
        }
        try {
            $this->indexXml($response->getBody()->getContents());
            $io->writeln("Indexed $uri OK");
            return true;
        } catch (\Exception $e) {
            $io->writeln("$uri Error: {$e->getMessage()}");
            return false;
        } finally {
            $response->getBody()->close();
        }
    }

    /**
     * Index a particular file
     *
     * @param string $file URI or path to METS/MODS file to index
     * @param OutputInterface $io IO object for printing messages
     * @return bool False on error, true on success
     */
    protected function indexFile(string $path, OutputInterface $io): bool
    {
        if (file_exists($path)) {
            $mimeType = mime_content_type($path);
            if ($mimeType != 'text/xml') {
                $io->writeln("Invalid data format. Expected XML but found `$mimeType`.");
                return false;
            }
            $xml = file_get_contents($path);
            if (!$xml) {
                $io->writeln("Could not read contents from `$path`");
                return false;
            }
        }
        return $this->indexXml($xml);
    }

    /**
     * Index a METS/MODS XML document
     *
     * @param string $xml XML document data
     * @throws \Exception on error
     */
    protected function indexXml(string $xml)
    {
        $document = $this->createDocument($xml);
        if (!$document) {
            throw new \Exception("Could not create document from XML");
        }

        $this->bulkBuffer[] = $document;
        if (count($this->bulkBuffer) >= self::BULK_SIZE) {
            $this->flushBulkBuffer(null);
        }
    }

    private function flushBulkBuffer(?OutputInterface $io): void
    {
        if (empty($this->bulkBuffer)) {
            return;
        }
        if ($this->backofficeEs === null) {
            $this->backofficeEs = $this->objectManager->get(ElasticSearch::class);
        }
        $count = count($this->bulkBuffer);
        $this->backofficeEs->indexBulk($this->bulkBuffer);
        if ($this->publicIndex) {
            if ($this->publicEs === null) {
                $this->publicEs = $this->objectManager->get(PublicElasticSearch::class);
            }
            $this->publicEs->indexBulk($this->bulkBuffer);
        }
        $this->bulkBuffer = [];
        if ($io !== null) {
            $suffix = $this->publicIndex ? ' (backoffice + public)' : '';
            $io->writeln("  → Flushed batch of $count documents$suffix.");
        }
    }
}
