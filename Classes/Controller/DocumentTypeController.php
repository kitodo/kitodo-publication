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

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Helper\InternalFormat;
use EWW\Dpf\Security\DocumentVoter;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Exceptions\DPFExceptionInterface;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Domain\Model\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;


/**
 * DocumentTypeController
 */
class DocumentTypeController extends AbstractController
{
    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository = null;

    public function listAction()
    {
        $docTypes =  $this->documentTypeRepository->findByUidList($this->settings['documentTypes']);

        if ($this->request->getPluginName() == "BackofficeDocumentTypes") {
            $this->view->assign('controller', 'DocumentFormBackoffice');
        } else {
            $this->view->assign('controller', 'DocumentForm');
        }

        $this->view->assign('documentTypes', $docTypes);
    }
}
