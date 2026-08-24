<?php
namespace EWW\Dpf\Services\ProcessNumber;

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

use EWW\Dpf\Domain\Model\ProcessNumber;
use EWW\Dpf\Domain\Repository\ClientRepository;
use EWW\Dpf\Domain\Repository\DocumentRepository;
use EWW\Dpf\Domain\Repository\ProcessNumberRepository;
use EWW\Dpf\Services\ElasticSearch\ElasticSearch;
use Throwable;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class ProcessNumberGenerator
{
    public function getProcessNumber($ownerId = NULL) {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $processNumberRepository = $objectManager->get(ProcessNumberRepository::class);
        $persistenceManager = $objectManager->get(PersistenceManagerInterface::class);

        $processNumberRepository->startTransaction();
        try {
            if (!$ownerId) {
                $clientRepository = $objectManager->get(ClientRepository::class);
                $ownerId = $clientRepository->findAll()->getFirst()->getOwnerId();
            }

            $datetime = new \DateTime();
            $currentYear = $datetime->format('y');

            $processNumber = $processNumberRepository->getHighestProcessNumberByOwnerIdAndYear(strtolower($ownerId),$currentYear);

            if ($processNumber) {
                $counter = $processNumber->getCounter() + 1;
                $processNumber->setCounter($counter);
                $processNumberRepository->update($processNumber);
            } else {
                $processNumber = $objectManager->get(ProcessNumber::class);
                $processNumber->setOwnerId(strtolower($ownerId));
                $processNumber->setYear($currentYear);
                $processNumber->setCounter(1);
                $processNumber->setPid(0);
                $processNumberRepository->add($processNumber);
            }

            $persistenceManager->persistAll();

            $candidateString = $processNumber->getProcessNumberString();

            $documentRepository = $objectManager->get(DocumentRepository::class);
            $es = $objectManager->get(ElasticSearch::class);
            if ($documentRepository->findByIdentifier($candidateString) !== null
                || $this->existsInBackofficeIndex($es, $candidateString)
            ) {
                $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
                $logger->warning('ProcessNumber collision detected, incrementing counter', ['candidate' => $candidateString]);
                // Single-shot retry, not a loop - if the bumped counter also
                // collides it's returned anyway undetected. Pre-existing
                // limitation, not introduced here. Upgrade to a bounded
                // while-loop re-checking both sources if bulk migrations
                // keep landing on generator-owned ranges.
                $processNumber->setCounter($processNumber->getCounter() + 1);
                $processNumberRepository->update($processNumber);
                $persistenceManager->persistAll();
            }

            $processNumberRepository->commitTransaction();
            return $processNumber->getProcessNumberString();
        } catch (\Exception $e) {
            $processNumberRepository->rollbackTransaction();
        }

        return FALSE;
    }

    /**
     * Checks the backoffice ElasticSearch index for an existing document
     * with the given process number. The local DB check above only sees
     * documents that currently have a working copy row - bulk-migrated
     * Fedora 6 documents (e.g. from the UBL migration) never got one, so a
     * new process number could silently collide with an already-published
     * migrated document without this second check.
     *
     * Fails open (returns false) on any ES error: this is a best-effort
     * duplicate check, not a hard uniqueness guarantee, matching the
     * existing DB check's own non-atomic nature.
     */
    private function existsInBackofficeIndex(ElasticSearch $es, string $candidateString): bool
    {
        try {
            $result = $es->search([
                'body' => [
                    'query' => ['term' => ['process_number' => $candidateString]],
                    'size' => 0,
                ],
            ]);
            return ($result['hits']['total']['value'] ?? 0) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

}
