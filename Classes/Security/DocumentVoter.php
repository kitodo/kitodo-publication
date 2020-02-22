<?php
namespace EWW\Dpf\Security;

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

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use EWW\Dpf\Domain\Repository\DocumentRepository;

class DocumentVoter extends Voter
{
    const CREATE = "DOCUMENT_CREATE";
    const CREATE_REGISTER = "DOCUMENT_CREATE_REGISTER";
    const UPDATE = "DOCUMENT_UPDATE";
    const LIST = "DOCUMENT_LIST";
    const LIST_REGISTERED = "DOCUMENT_LIST_REGISTERED";
    const LIST_IN_PROGRESS = "DOCUMENT_LIST_IN_PROGRESS";

    const DISCARD = "DOCUMENT_DISCARD";

    const DELETE_LOCALLY = "DOCUMENT_DELETE_LOCALLY";
    const DELETE_WORKING_COPY = "DOCUMENT_DELETE_WORKING_COPY";
    const DUPLICATE = "DOCUMENT_DUPLICATE";
    const RELEASE_PUBLISH = "DOCUMENT_RELEASE_PUBLISH";
    const RELEASE_ACTIVATE = "DOCUMENT_RELEASE_ACTIVATE";
    const REGISTER = "DOCUMENT_REGISTER";
    const SHOW_DETAILS = "DOCUMENT_SHOW_DETAILS";
    const CANCEL_LIST_TASK = "DOCUMENT_CANCEL_LIST_TASK";
    const UPLOAD_FILES = "DOCUMENT_UPLOAD_FILES";
    const EDIT = "DOCUMENT_EDIT";
    const POSTPONE = "DOCUMENT_POSTPONE";
    const DOUBLET_CHECK = "DOCUMENT_DOUBLET_CHECK";
    const SUGGEST_RESTORE = "DOCUMENT_SUGGEST_RESTORE";
    const SUGGEST_MODIFICATION = "DOCUMENT_SUGGEST_MODIFICATION";
    const SUGGESTION_ACCEPT = "DOCUMENT_SUGGESTION_ACCEPT";

    /**
     * editingLockService
     *
     * @var \EWW\Dpf\Services\Document\EditingLockService
     * @inject
     */
    protected $editingLockService = null;

    /**
     * workflow
     *
     * @var DocumentWorkflow
     */
    protected $workflow;

    public function __construct()
    {
       $this->workflow = DocumentWorkflow::getWorkflow();
    }


    /**
     * Returns all supported attributes.
     *
     * @return array
     */
    protected static function getAttributes()
    {
        return array(
            self::CREATE,
            self::CREATE_REGISTER,
            self::UPDATE,
            self::LIST,
            self::LIST_REGISTERED,
            self::LIST_IN_PROGRESS,
            self::DISCARD,
            self::DELETE_LOCALLY,
            self::DELETE_WORKING_COPY,
            self::DUPLICATE,
            self::RELEASE_PUBLISH,
            self::RELEASE_ACTIVATE,
            self::REGISTER,
            self::SHOW_DETAILS,
            self::CANCEL_LIST_TASK,
            self::UPLOAD_FILES,
            self::EDIT,
            self::POSTPONE,
            self::DOUBLET_CHECK,
            self::SUGGEST_RESTORE,
            self::SUGGEST_MODIFICATION,
            self::SUGGESTION_ACCEPT
        );
    }


    /**
     * Determines if the voter supports the given attribute.
     *
     * @param string $attribute
     * @param mixed $subject
     * @return mixed
     */
    public static function supports($attribute, $subject = NULL)
    {
        if (!in_array($attribute, self::getAttributes())) {
            return FALSE;
        }

        if (!$subject instanceof Document && !is_null($subject)) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Determines if access for the given attribute and subject is allowed.
     *
     * @param string $attribute
     * @param mixed $subject
     * @return mixed
     */
    public function voteOnAttribute($attribute, $subject = NULL)
    {
        if (!$subject instanceof Document) {
            return FALSE;
        }

        switch ($attribute) {

            case self::CREATE:
                return $this->defaultAccess($subject);
                break;

            case self::CREATE_REGISTER:
                return $this->canCreateRegister($subject);
                break;

            case self::UPDATE:
                return $this->canUpdate($subject);
                break;

            case self::LIST:
                return $this->defaultAccess();
                break;

            case self::LIST_REGISTERED:
                return $this->defaultAccess();
                break;

            case self::LIST_IN_PROGRESS:
                return $this->defaultAccess();
                break;

            case self::DISCARD:
                return $this->canDiscard($subject);
                break;

            case self::DELETE_LOCALLY:
                return $this->canDeleteLocally($subject);
                break;

            case self::DELETE_WORKING_COPY:
                return $this->canDeleteWorkingCopy($subject);
                break;

            case self::DUPLICATE:
                return $this->librarianOnly();
                break;

            case self::RELEASE_PUBLISH:
                return $this->canReleasePublish($subject);
                break;

            case self::RELEASE_ACTIVATE:
                return $this->canReleaseActivate($subject);
                break;

            case self::REGISTER:
                return $this->canRegister($subject);
                break;

            case self::SHOW_DETAILS:
                return $this->canShowDetails($subject);
                break;

            case self::CANCEL_LIST_TASK:
                return $this->defaultAccess();
                break;

            case self::UPLOAD_FILES:
                return $this->canUpdate($subject);
                break;

            case self::EDIT:
                return $this->canEdit($subject);
                break;

            case self::POSTPONE:
                return $this->canPostpone($subject);
                break;

            case self::DOUBLET_CHECK:
                return $this->librarianOnly();
                break;

            case self::SUGGEST_RESTORE:
                return $this->canSuggestRestore($subject);
                break;

            case self::SUGGEST_MODIFICATION:
                return $this->canSuggestModification($subject);
                break;

            case self::SUGGESTION_ACCEPT:
                return $this->canSuggestionAccept($subject);
                break;
        }

        throw new \Exception("This code should not be reached!");
    }

    /**
     * @return bool
     */
    protected function defaultAccess()
    {
        return (
            $this->security->getUserRole() === Security::ROLE_LIBRARIAN ||
            $this->security->getUserRole() === Security::ROLE_RESEARCHER
        );
    }

    /**
     * @return bool
     */
    protected function librarianOnly()
    {
        return $this->security->getUserRole() === Security::ROLE_LIBRARIAN;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canDiscard($document)
    {
        if ($this->isDocumentLocked($document)) {
            return FALSE;
        }

        if ($this->workflow->can($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_DISCARD)) {
            return (
                $this->security->getUserRole() === Security::ROLE_LIBRARIAN ||
                (
                    $document->getOwner() === $this->security->getUser()->getUid() &&
                    $document->getState() === DocumentWorkflow::STATE_REGISTERED_NONE
                )
            );

        }

        return FALSE;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canShowDetails($document)
    {
        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return (
                $document->getState() !== DocumentWorkflow::STATE_NEW_NONE ||
                $document->getOwner() === $this->security->getUser()->getUid()
            );
        }

        if ($this->security->getUserRole() === Security::ROLE_RESEARCHER) {
            return (
                $document->getOwner() === $this->security->getUser()->getUid() ||
                (
                    $document->getState() !== DocumentWorkflow::STATE_NEW_NONE &&
                    $document->getState() !== DocumentWorkflow::STATE_DISCARDED_NONE &&
                    $document->getState() !== DocumentWorkflow::STATE_NONE_DELETED
                )
            );
        }

        return FALSE;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canRegister($document)
    {
        if (
            $this->workflow->can($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_REGISTER) &&
            $document->getOwner() === $this->security->getUser()->getUid()
        ) {
           return TRUE;
        }

        return FALSE;
    }


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canReleasePublish($document)
    {
        if ($this->isDocumentLocked($document)) {
            return FALSE;
        }

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return $this->workflow->can($document,
                \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_RELEASE_PUBLISH);
        }

        return FALSE;
    }


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canReleaseActivate($document)
    {
        if ($this->isDocumentLocked($document)) {
            return FALSE;
        }

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return $this->workflow->can($document,
                \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_RELEASE_ACTIVATE);
        }

        return FALSE;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canDeleteLocally($document)
    {
        if ($this->isDocumentLocked($document)) {
            return FALSE;
        }

        if ($document->isSuggestion()) {
            return $this->security->getUserRole() === Security::ROLE_LIBRARIAN;
        }

        if ($this->workflow->can($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_DELETE_LOCALLY)) {
            return $document->getOwner() === $this->security->getUser()->getUid();
        }

        if ($this->workflow->can($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_DELETE_DISCARDED)) {
            return $this->security->getUserRole() === Security::ROLE_LIBRARIAN;
        }

        return FALSE;
    }


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canDeleteWorkingCopy($document)
    {
        if ($document->isTemporary() || $this->isDocumentLocked($document)) {
            return FALSE;
        }

        if ($this->workflow->can($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_DELETE_WORKING_COPY)) {
            return $this->security->getUserRole() === Security::ROLE_LIBRARIAN;
        }

        return FALSE;
    }


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canEdit($document)
    {
        if ($this->isDocumentLocked($document)) {
            return FALSE;
        }

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return (
                $document->getState() !== DocumentWorkflow::STATE_NEW_NONE ||
                $document->getOwner() === $this->security->getUser()->getUid()
            );
        }

        if ($document->getOwner() === $this->security->getUser()->getUid()) {
            return (
                $document->getState() === DocumentWorkflow::STATE_NEW_NONE ||
                $document->getState() === DocumentWorkflow::STATE_REGISTERED_NONE
            );
        }

        return FALSE;
    }


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canUpdate($document)
    {
        if ($this->isDocumentLocked($document)) {
            return FALSE;
        }

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return (
                $document->getState() !== DocumentWorkflow::STATE_NEW_NONE ||
                $document->getOwner() === $this->security->getUser()->getUid()
            );
        }

        if ($document->getOwner() === $this->security->getUser()->getUid()) {
            return (
                $document->getState() === DocumentWorkflow::STATE_NEW_NONE ||
                $document->getState() === DocumentWorkflow::STATE_REGISTERED_NONE
            );
        }

        return false;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canSuggestRestore($document)
    {
        if ($this->security->getUserRole() === Security::ROLE_RESEARCHER) {

            $objectManager =GeneralUtility::makeInstance(ObjectManager::class);
            $documentRepository = $objectManager->get(DocumentRepository::class);

            $linkedDocument = $documentRepository->findOneByLinkedUid($document->getUid());

            if (!$linkedDocument && $document->getObjectIdentifier()) {
                $linkedDocument = $documentRepository->findOneByLinkedUid($document->getObjectIdentifier());
            }

            return (
                $document->getState() === DocumentWorkflow::STATE_DISCARDED_NONE ||
                $document->getState() === DocumentWorkflow::STATE_NONE_DELETED
            ) && !$linkedDocument;
        }

        return FALSE;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canSuggestModification($document)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $documentRepository = $objectManager->get(DocumentRepository::class);

        $linkedDocument = $documentRepository->findOneByLinkedUid($document->getUid());
        if (!$linkedDocument && $document->getObjectIdentifier()) {
            $linkedDocument = $documentRepository->findOneByLinkedUid($document->getObjectIdentifier());
        }

        if ($this->security->getUserRole() === Security::ROLE_RESEARCHER) {
            return (
                (
                    $document->getOwner() !== $this->security->getUser()->getUid() &&
                    $document->getState() === DocumentWorkflow::STATE_REGISTERED_NONE
                ) ||
                (
                    $document->getState() !== DocumentWorkflow::STATE_NEW_NONE &&
                    $document->getState() !== DocumentWorkflow::STATE_REGISTERED_NONE
                )
            ) && !$linkedDocument;
        }

        return FALSE;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canSuggestionAccept($document)
    {
        // TODO: What if a document should be restored?

        return $this->librarianOnly();
    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canPostpone($document)
    {
        if ($this->isDocumentLocked($document)) {
            return FALSE;
        }

        if ($this->workflow->can($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_POSTPONE)) {
            return $this->security->getUserRole() === Security::ROLE_LIBRARIAN;
        }

        return FALSE;
    }


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function canCreateRegister($document)
    {
        if ($this->security->getUserRole()) {
            return FALSE;
        }

        if ($this->workflow->can($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_CREATE_REGISTER)) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return bool
     */
    protected function isDocumentLocked($document)
    {
        $identifier = $document->getObjectIdentifier()? $document->getObjectIdentifier() : $document->getUid();
        return $this->editingLockService->isLocked($identifier, $this->security->getUser()->getUid());
    }

}