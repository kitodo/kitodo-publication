<?php
namespace EWW\Dpf\Controller;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AjaxBackofficeController
 */
class AjaxBackofficeController extends \EWW\Dpf\Controller\AbstractController
{
    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @inject
     */
    protected $bookmarkRepository = null;


    /**
     * Creates a bookmark for the given document identifier
     *
     * @param string $identifier
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function addBookmarkAction($identifier)
    {
        /** @var \EWW\Dpf\Domain\Model\Bookmark $bookmark */
        $bookmark = $this->bookmarkRepository->findBookmark($this->security->getUser()->getUid(), $identifier);
        if (!$bookmark) {
            $bookmark = $this->objectManager->get(\EWW\Dpf\Domain\Model\Bookmark::class);
            $bookmark->setDocumentIdentifier($identifier);
            $bookmark->setOwnerUid($this->security->getUser()->getUid());
            $this->bookmarkRepository->add($bookmark);
        }

        return;
    }

    /**
     * Adds a workspace filter to the session.
     *
     * @param string $name
     * @param array $values
     */
    public function addWorkspaceFilterAction($name, $values = [])
    {
        if ($name && $values && is_array($values)) {
            $workspaceFilters = $this->getSessionData('workspaceFilters');
            if ($workspaceFilters && is_array($workspaceFilters)) {
                $workspaceFilters[$name] = $values;
                $this->setSessionData('workspaceFilters', $workspaceFilters);
            } else {
                $this->setSessionData('workspaceFilters', [$name => $values]);
            }
        } else {
            $workspaceFilters = $this->getSessionData('workspaceFilters');
            if ($name && is_array($workspaceFilters) && array_key_exists($name, $workspaceFilters)) {
                unset($workspaceFilters[$name]);
                $this->setSessionData('workspaceFilters', $workspaceFilters);
            }
        }

        return;

    }

    /**
     * Adds a workspace sort to the session.
     *
     * @param string $field
     * @param string $order
     */
    public function addWorkspaceSortAction($field, $order)
    {
        $this->session->setWorkspaceSort($field, $order);
        return;
    }

    /**
     * Toggles the filter to exclude discarded documents.
     *
     */
    public function toggleWorkspaceExcludeDiscardedAction()
    {
        $this->session->toggleWorkspaceExcludeDiscardedFilter();
        return;
    }


}
