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
     * editingLockService
     *
     * @var \EWW\Dpf\Services\Document\EditingLockService
     * @inject
     */
    protected $editingLockService = null;

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
    public function cleanUpDocuments($actionMethodName, $controllerClass)
    {
        $excludeActions = [
            \EWW\Dpf\Controller\DocumentController::class => [
                'showDetailsAction',
                'postponeAction',
                'discardAction',
                'releaseActivateAction',
                'suggestModificationAction'
            ],
            \EWW\Dpf\Controller\DocumentFormBackofficeController::class => [
                'editAction',
                'cancelEditAction',
                'updateAction',
                'updateLocallyAction',
                'updateRemoteAction',
                'createSuggestionDocumentAction'
            ]
        ];

        $this->cleanUpOutdatedTemporaryDocuments();

        if (
            !array_key_exists($controllerClass, $excludeActions) ||
            !in_array($actionMethodName, $excludeActions[$controllerClass])
        ) {
            // Remove all locked temporary documents of the current user.
            $feUserUid = $this->security->getUser()->getUid();
            $documents = $this->documentRepository->findByTemporary(TRUE);
            $docIdentifiers = $this->editingLockService->getLockedDocumentIdentifiersByUserUid($feUserUid);

            foreach ($documents as $document) {
                /** @var  \EWW\Dpf\Domain\Model\Document $document */
                if (in_array($document->getDocumentIdentifier(), $docIdentifiers)) {
                    $this->documentRepository->remove($document);
                }
            }
        }

        $this->cleanUpEditingLocks($actionMethodName, $controllerClass);
    }

    /**
     * Removes all outdated temporary documents and unlocks all outdated editing locks.
     *
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    protected function cleanUpOutdatedTemporaryDocuments()
    {
        // Remove outdated temporary documents from the document table.
        $outdatedTemporaryDocuments = $this->documentRepository->findOutdatedTemporaryDocuments(3600);
        foreach ($outdatedTemporaryDocuments as $outdatedTemporaryDocument) {
            /** @var  \EWW\Dpf\Domain\Model\Document $outdatedTemporaryDocument */
            $this->documentRepository->remove($outdatedTemporaryDocument);
        }
        $this->persistenceManager->persistAll();
    }

    /**
     * Unlocks all editing locks of the current user.
     */
    protected function cleanUpEditingLocks($controllerClass, $actionMethodName)
    {
        $excludeActions = [
            \EWW\Dpf\Controller\DocumentController::class => [
            ],
            \EWW\Dpf\Controller\DocumentFormBackofficeController::class => [
            ]
        ];

        // Unlock outdated editing locks.
        $this->editingLockService->unlockOutdatedLocks(3600);

        if (
            !array_key_exists($controllerClass, $excludeActions) ||
            !in_array($actionMethodName, $excludeActions[$controllerClass])
        ) {
            $feUserUid = $this->security->getUser()->getUid();
            $this->editingLockService->unlockAllByEditor($feUserUid);
        }
    }

}
