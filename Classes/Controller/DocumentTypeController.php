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
