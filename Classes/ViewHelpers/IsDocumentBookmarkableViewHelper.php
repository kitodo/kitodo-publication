<?php
namespace EWW\Dpf\ViewHelpers;

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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use \EWW\Dpf\Security\Security;
use \EWW\Dpf\Domain\Repository\BookmarkRepository;

class IsDocumentBookmarkableViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('identifier', 'string', '', true);
        $this->registerArgument('creator', 'int', '', true);
        $this->registerArgument('state', 'string', '', true);
    }

    /**
     * @return bool
     */
    public function render()
    {
        $identifier = $this->arguments['identifier'];
        $creator = $this->arguments['creator'];
        $state = $this->arguments['state'];

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var Security $security */
        $security = $objectManager->get(Security::class);

        /** @var BookmarkRepository $bookmarkRepository */
        $bookmarkRepository = $objectManager->get(BookmarkRepository::class);

        if ($bookmarkRepository->findBookmark($security->getUser()->getUid(), $identifier)) {
            return false;
        }

        if ($security->getUser()->getUserRole() === Security::ROLE_LIBRARIAN) {
            return $state !== DocumentWorkflow::STATE_NEW_NONE;
        }

        if ($security->getUser()->getUserRole() === Security::ROLE_RESEARCHER) {
            return (
                $security->getUser()->getUid() !== $creator &&
                $state !== DocumentWorkflow::STATE_DISCARDED_NONE &&
                $state !== DocumentWorkflow::STATE_NONE_DELETED
            );
        }

        return false;
    }
}
