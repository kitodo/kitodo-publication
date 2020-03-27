<?php

namespace EWW\Dpf\Tasks;

use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
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
                    switch ($document->getState()) {
                        case DocumentWorkflow::STATE_IN_PROGRESS_NONE:
                        case DocumentWorkflow::STATE_DISCARDED_NONE:
                        case DocumentWorkflow::STATE_NONE_INACTIVE:
                        case DocumentWorkflow::STATE_NONE_ACTIVE:
                        case DocumentWorkflow::STATE_NONE_DELETED:
                            // update file restriction
                            $documentTransferManager->setRemoteRepository($fedoraRepository);
                            $documentTransferManager->update($document);
                    }
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