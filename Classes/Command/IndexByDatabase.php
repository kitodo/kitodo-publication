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
 * A console command to import Metadata from the TYPO3 database into the ElasticSearch index.
 * Usage: vendor/bin/typo3 dpf:indexByDatabase <client>
 *
 * @package EWW\Dpf\Command
 */
class IndexByDatabase extends AbstractIndexCommand
{
    /**
     * Configure the command by defining arguments
     */
    protected function configure()
    {
        $this->setDescription('Reindex all local documents for the given client');
        $this->addArgument('client', InputArgument::REQUIRED, 'The UID of the client.');
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

        /** @var Client $client */
        $client = $this->clientRepository->findByUid($clientUid);

        if ($client) {
            $io->title("Indexing: '" . $client->getClient() . "'");

            // Set the client storagePid
            Client::$storagePid = $client->getPid();
            $this->documentTypeRepository->setStoragePid(Client::$storagePid);
            $this->documentRepository->setStoragePid(Client::$storagePid);

            $numFailures = 0;
            $numSuccess = 0;

            foreach ($this->documentRepository->findAll() as $document) {

                $creationDate = $document->getCreationDate();
                if (empty($creationDate) && $document->getObjectIdentifier()) {
                    $creationDate = $document->getCreationDate();
                }
                $document->setCreationDate($creationDate);
                $this->documentRepository->update($document);

                try {
                    if (!$document->isTemporary() && !$document->isSuggestion()) {
                        // index the document
                        /** @var ElasticSearch $es */
                        $es = $this->objectManager->get(ElasticSearch::class);
                        $es->index($document);
                        $io->writeln($document->getUid().': Successfull');
                        $io->writeln('');
                        ++$numSuccess;
                    }
                } catch(\Throwable $e) {
                    $failures = true;
                    $io->writeln($document->getUid().': Failed');
                    $io->writeln($e->getMessage().': Failed');
                    $io->writeln('');
                    ++$numFailures;
                }
            }

            $io->writeln('Documents successfully indexed: '.$numSuccess);
            $io->writeln('Documents failed to index: '.$numFailures);
            return true;
        } else {
            $error = "Unknown client '" . $clientUid ."'";
        }

        $io->write('Failed: ');
        $io->writeln($error);
        $io->writeln('');

        return false;
    }
}
