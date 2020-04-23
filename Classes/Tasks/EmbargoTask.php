<?php

namespace EWW\Dpf\Tasks;

use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\Email\Notifier;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Domain\Repository\DocumentRepository;


class EmbargoTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    public function execute() {

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $documentRepository = $objectManager->get(DocumentRepository::class);
        $embargoDocuments = $documentRepository->crossClientEmbargoFindAll();

        $currentDate = new \DateTime('now');

        foreach ($embargoDocuments as $document) {
            if ($currentDate > $document->getEmbargoDate()) {

                if ($document->getState() != DocumentWorkflow::SIMPLE_STATE_REGISTERED) {
                    // send message
                    $notifier = $objectManager->get(Notifier::class);
                    $notifier->sendEmbargoNotification($document);
                }
            }
        }

        return TRUE;
    }

}