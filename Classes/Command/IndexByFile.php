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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        $this->setDescription('Indexing: ');
        $this->addArgument('client', InputArgument::REQUIRED, 'The UID of the client.');
        $this->addArgument('filename', InputArgument::REQUIRED, 'The full path to the file containing the METS/MODS-Data.');
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

        $io->title($this->getDescription()."'".$filename."'");

        /** @var Client $client */
        $client = $this->clientRepository->findByUid($clientUid);

        if ($client) {
            // Set the client storagePid
            Client::$storagePid = $client->getPid();
            $this->documentTypeRepository->setStoragePid(Client::$storagePid);
            $this->documentRepository->setStoragePid(Client::$storagePid);

            if (file_exists($filename)) {
                $mimeType = mime_content_type($filename);
                if ($mimeType == 'text/xml') {
                    $xml = file_get_contents($filename);
                    $document = $this->createDocument($xml);
                    if ($document) {
                        /** @var ElasticSearch $es */
                        $es = $this->objectManager->get(ElasticSearch::class);
                        $es->index($document);
                        $io->writeln('Successfull');
                        $io->writeln('');
                    }
                    return true;
                } else {
                    $error = "Invalid data format (xml needed).";
                }
            } else {
                $error = "Could not open the file";
            }
        } else {
            $error = "Unknown client '" . $clientUid ."'";
        }

        $io->write('Failed: ');
        $io->writeln($error);
        $io->writeln('');

        return false;
    }
}
