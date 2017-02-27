<?php
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

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

class ext_update {

    public function access() {
        return TRUE;
    }

    public function main() {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\Object\\ObjectManager');
        $clientRepository = $objectManager->get("EWW\\Dpf\\Domain\\Repository\\ClientRepository");

        $processNumberGenerator = $objectManager->get("EWW\\Dpf\\Services\\ProcessNumber\\ProcessNumberGenerator");

        $documentRepository = $objectManager->get("EWW\\Dpf\\Domain\\Repository\\DocumentRepository");

        $documents = $documentRepository->findDocumentsWithoutProcessNumber();

        if (count($documents) == 0) return;

        foreach ($documents as $document) {
            $pid = $document->getPid();
            $clients = $clientRepository->findAllByPid($pid);
            if ($clients) {
                if (count($clients) != 1) {
                    throw new \Exception('Invalid number of client records for pid: '.$pid);
                }
            }
        }

        foreach ($documents as $document) {
            $pid = $document->getPid();
            $clients = $clientRepository->findAllByPid($pid);
            if ($clients) {
                if (count($clients) == 1) {
                    $client = $clients->getFirst();
                    $ownerId = $client->getOwnerId();
                    $processNumber = $processNumberGenerator->getProcessNumber($ownerId);
                    $document->setProcessNumber($processNumber);
                    $documentRepository->update($document);
                }
            }
        }

        return "Das Update wurde erfolgreich ausgeführt.";

    }


}