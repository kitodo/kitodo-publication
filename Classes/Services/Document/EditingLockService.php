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

use EWW\Dpf\Domain\Model\EditingLock;

class EditingLockService
{
    /**
     * editingLockRepository
     *
     * @var \EWW\Dpf\Domain\Repository\EditingLockRepository
     * @inject
     */
    protected $editingLockRepository = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

    /**
     * Locks editing for the given document identifier and all users except the given user.
     *
     * @param string $documentIdentifier
     * @param int $userUid
     * @throws \Exception
     */
    public function lock($documentIdentifier, $userUid)
    {
        if ($this->editingLockRepository->findOneByDocumentIdentifier($documentIdentifier)) {
            throw new \Exception("Error: Unexpected editing lock!");
        }

        /** @var \EWW\Dpf\Domain\Model\EditingLock $editingLock */
        $editingLock = new EditingLock();
        $editingLock->setEditorUid($userUid);
        $editingLock->setDocumentIdentifier($documentIdentifier);
        $this->editingLockRepository->add($editingLock);
        $this->persistenceManager->persistAll();
    }

    /**
     * Unlocks editing for the given document identifier
     *
     * @param string $documentIdentifier
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function unlock($documentIdentifier)
    {
        /** @var \EWW\Dpf\Domain\Model\EditingLock $editingLock */
        $editingLock = $this->editingLockRepository->findOneByDocumentIdentifyer($documentIdentifier);
        $this->editingLockRepository->remove($editingLock);
        $this->persistenceManager->persistAll();
    }

    /**
     * Checks if editing of the specified document (by its document identifier) is locked for the given user uid.
     *
     * @param string $documentIdentifier
     * @param int $userUid
     * @return bool
     */
    public function isLocked($documentIdentifier, $userUid)
    {
        $locks = $this->editingLockRepository->findByDocumentIdentifier($documentIdentifier);

        /** @var  \EWW\Dpf\Domain\Model\EditingLock $lock */
        foreach ($locks as $lock) {
            if ($lock->getEditorUid() != $userUid) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Unlocks all outdated editing locks.
     *
     * @param int $timeout : Time interval (in seconds) in which locks are not outdated, default is 1 hour.
     */
    public function unlockOutdatedLocks($timeout = 3600)
    {
        // Unlock outdated editing locks.
        $outdatedLocks = $this->editingLockRepository->findOutdatedLocks($timeout);
        foreach ($outdatedLocks as $outdatedLock) {
            $this->editingLockRepository->remove($outdatedLock);
        }
        $this->persistenceManager->persistAll();
    }

    /**
     * Unlocks all editing locks of the given user (editor uid).
     *
     * @param int $editorUid
     */
    public function unlockAllByEditor($editorUid)
    {
        $locks = $this->editingLockRepository->findByEditorUid($editorUid);
        foreach ($locks as $lock) {
            $this->editingLockRepository->remove($lock);
        }

        $this->persistenceManager->persistAll();
    }

}