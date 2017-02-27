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

class ProcessNumberGenerator
{
    public function getProcessNumber($ownerId = NULL) {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\Object\\ObjectManager');
        $processNumberRepository = $objectManager->get("EWW\\Dpf\\Domain\\Repository\\ProcessNumberRepository");
        $persistenceManager = $objectManager->get("TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface");

        $processNumberRepository->startTransaction();
        try {
            if (!$ownerId) {
                $clientRepository = $objectManager->get("EWW\\Dpf\\Domain\\Repository\\ClientRepository");
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
                $processNumber = $objectManager->get("EWW\\Dpf\\Domain\\Model\\ProcessNumber");
                $processNumber->setOwnerId(strtolower($ownerId));
                $processNumber->setYear($currentYear);
                $processNumber->setCounter(1);
                $processNumber->setPid(0);
                $processNumberRepository->add($processNumber);
            }

            $persistenceManager->persistAll();

            $processNumberRepository->commitTransaction();
            return $processNumber->getProcessNumberString();
        } catch (\Exception $e) {
            $processNumberRepository->rollbackTransaction();
        }

        return FALSE;
    }

}
