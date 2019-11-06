<?php
namespace EWW\Dpf\Services\Document;

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

class DocumentCleaner
{

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @inject
     */
    protected $security = null;


    /**
     * @param string $actionMethodName
     * @param string $controllerClass
     * @param \EWW\Dpf\Domain\Model\Document $openedDocument
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function cleanUpDocuments($actionMethodName, $controllerClass, $openedDocument = NULL)
    {
        $this->cleanUpOutdatedDocuments();

        $feUserUid = $this->security->getUser()->getUid();
        $this->cleanUpTemporaryDocumentsByFeUser($feUserUid, $actionMethodName, $controllerClass);
        $this->unlockDocumentsByFeUser($feUserUid, $actionMethodName, $controllerClass);
    }


    /**
     * Removes all outdated temporary documents and unlocks all outdated locked documents.
     *
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    protected function cleanUpOutdatedDocuments()
    {
        // Remove outdated temporary documents from the document table.
        $outdatedTemporaryDocuments = $this->documentRepository->findOutdatedTemporaryDocuments(3600);
        foreach ($outdatedTemporaryDocuments as $outdatedTemporaryDocument) {
            /** @var  \EWW\Dpf\Domain\Model\Document $outdatedTemporaryDocument */
            $this->documentRepository->remove($outdatedTemporaryDocument);
        }

        // Unlock outdated locked documents.
        $outdatedLockedDocuments = $this->documentRepository->findOutdatedLockedDocuments(3600);
        foreach ($outdatedLockedDocuments as $outdatedLockedDocument) {
            /** @var  \EWW\Dpf\Domain\Model\Document $outdatedTemporaryDocument */
            $this->documentRepository->update($outdatedLockedDocument);
        }

        $this->persistenceManager->persistAll();
    }


    /**
     * Removes the temporary documents of a frontend user.
     *
     * @param $feUserUid
     * @param $actionMethodName
     * @param $controllerClass
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function cleanUpTemporaryDocumentsByFeUser($feUserUid, $actionMethodName, $controllerClass)
    {
        $excludeActions = [
            \EWW\Dpf\Controller\DocumentController::class => [
                'showDetailsAction',
                'deleteLocallyAction',
                'postponeAction',
                'discardAction',
                'releasePublishAction',
                'releaseUpdateAction',
                'releaseActivateAction'
            ],
            \EWW\Dpf\Controller\DocumentFormBackofficeController::class => [
                'editAction',
                'cancelEditAction',
                'updateAction',
                'updateLocallyAction',
                'updateRemoteAction'
            ]
        ];

        if (
            !array_key_exists($controllerClass, $excludeActions) ||
            !in_array($actionMethodName, $excludeActions[$controllerClass])
        ) {
            $documents = $this->documentRepository->findByTemporary(TRUE);
            foreach ($documents as $document) {
                /** @var  \EWW\Dpf\Domain\Model\Document $document */
                if ($document->getEditorUid() === $feUserUid) {
                    $this->documentRepository->remove($document);
                }
            }
        }

        $this->persistenceManager->persistAll();
    }


    /**
     * Unlocks the locked documents of a frontend user.
     *
     * @param $feUserUid
     * @param $actionMethodName
     * @param $controllerClass
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    public function unlockDocumentsByFeUser($feUserUid, $actionMethodName, $controllerClass)
    {
        $excludeActions = [
            \EWW\Dpf\Controller\DocumentController::class => [
                'showDetailsAction' => 'temporary',
                'deleteLocallyAction' => 'all',
                'postponeAction' => 'all',
                'discardAction' => 'all',
                'releasePublishAction' => 'all',
                'releaseUpdateAction' => 'all',
                'releaseActivateAction' => 'all'
            ],
            \EWW\Dpf\Controller\DocumentFormBackofficeController::class => [
                'editAction' => 'all',
                'cancelEditAction' => 'all',
                'updateAction' => 'all',
                'updateLocallyAction' => 'all',
                'updateRemoteAction' => 'all'
            ]
        ];

        if (
            !array_key_exists($controllerClass, $excludeActions) ||
            (
                !array_key_exists($actionMethodName, $excludeActions[$controllerClass])
            )
        ) {
            $lockedDocuments = $this->documentRepository->findByEditorUid($feUserUid);
            foreach ($lockedDocuments as $lockedDocument) {
                /** @var  \EWW\Dpf\Domain\Model\Document $lockedDocument */
                $lockedDocument->setEditorUid(0);
                $this->documentRepository->update($lockedDocument);
            }
        }

        $this->persistenceManager->persistAll();
    }


}
