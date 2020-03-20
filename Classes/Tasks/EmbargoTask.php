<?php

namespace EWW\Dpf\Tasks;

use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Domain\Repository\DocumentRepository;
use EWW\Dpf\Domain\Repository\MetadataObjectRepository;

class EmbargoTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    public function execute() {

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $documentRepository = $objectManager->get(DocumentRepository::class);
        $documentTransferManager = $objectManager->get(DocumentTransferManager::class);
        $fedoraRepository = $objectManager->get(FedoraRepository::class);

        $embargoDocuments = $documentRepository->crossClientEmbargoFindAll();

        $currentDate = new \DateTime('now');

        foreach ($embargoDocuments as $document) {
            if ($currentDate > $document->getEmbargoDate()) {
                if ($document->getAutomaticEmbargo()) {
                    // update file restriction
                    $documentTransferManager->setRemoteRepository($fedoraRepository);
                    $documentTransferManager->update($document);


                } else {
                    // send message
                    $notifier = $objectManager->get(Notifier::class);
                    $notifier->sendEmbargoNotification($document);
                }

            }
        }

        return TRUE;
    }

}