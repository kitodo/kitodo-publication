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
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;
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

        $io->title("Indexing: '" . $filename . "'");
        if ($linklist) {
            $io->writeln("Assuming the file contains URIs...");
        }

        /** @var Client $client */
        $client = $this->clientRepository->findByUid($clientUid);

        if (!$client) {
            $io->write('Failed: ');
            $io->writeln("Unknown client '" . $clientUid ."'");
            $io->writeln('');
            return false;
        }

        if (!file_exists($filename)) {
            $io->write('Failed: ');
            $io->writeln("File `$filename` not found.");
            $io->writeln('');
            return false;
        }

        // Set the client storagePid
        Client::$storagePid = $client->getPid();
        $this->documentTypeRepository->setStoragePid(Client::$storagePid);
        $this->documentRepository->setStoragePid(Client::$storagePid);

        $result = true;

        if ($linklist) {
            $fn = fopen($filename, "r");
            $httpClient = new HttpClient();
            while (!feof($fn)) {
                $uri = trim(fgets($fn));
                if ($uri) {
                    $result = $this->indexHttpDocument($uri, $io, $httpClient, $credentials) 
                        && $result;
                }
            }
            fclose($fn);
        } else {
            $result = $this->indexFile($filename, $io);
        }

        if ($result === true) {
            $io->writeln("Successfully indexed contents of `$filename`");
            return true;
        } else {
            $io->writeln("There where errors.");
            return false;
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
        if(file_exists($path)) {
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
     * Index a particular web document
     * 
     * @param string URI to METS/MODS file to index
     * @param OutputInterface $io IO object for printing messages
     * @param string|null Credentials string as "user:password"
     * @return bool False on error, true on success
     */
    protected function indexHttpDocument(
        string $uri,
        OutputInterface $io,
        HttpClient $httpClient,
        string $credentials = null)
    {
        if (str_starts_with($uri, 'http')) {
            $requestConfig = ['http_errors' => false];
            if ($credentials) {
                $requestConfig['auth'] = explode(':', $credentials);
            }

            /** @var ResponseInterface */
            $response = $httpClient->get($uri, $requestConfig);

            if ($response->getStatusCode() !== 200) {
                $statusCode = $response->getStatusCode();
                $reasonPhrase = $response->getReasonPhrase();
                $io->writeln("Could not get contents from `$uri`: $statusCode $reasonPhrase");
                return false;
            }

            $contentType = $response->getHeader('Content-Type')[0];

            if (preg_match('/^(text|application)\/(.*)xml(.*)$/', $contentType)) {
                return $this->indexXml($response->getBody()->getContents());    
            } else {
                $io->writeln("Invalid data format. Expected XML but found `$contentType`.");
                return false;
            }
        } else {
            $io->writeln("Unrecognized URI scheme: `$uri`");
            return false;
        }
    }

    /**
     * Index a METS/MODS XML document
     * 
     * @param string $xml XML document data
     * @return bool False on error, true on success
     */
    protected function indexXml(string $xml)
    {
        $document = $this->createDocument($xml);
        if ($document) {
            /** @var ElasticSearch $es */
            $es = $this->objectManager->get(ElasticSearch::class);
            $es->index($document);
        }
        return true;
    }

}
