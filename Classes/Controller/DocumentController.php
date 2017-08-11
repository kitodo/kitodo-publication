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

class DocumentController extends AbstractDocumentController
{

    public function __construct()
    {
        parent::__construct();

    }

    protected function redirectToList($message = null)
    {
        $this->redirect('list', 'Document', null, array('message' => $message));
    }

    /**
     * action create
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm
     * @return void
     */
    public function createAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm)
    {

        foreach ($newDocumentForm->getNewFiles() as $newFile) {
            $uid = $newFile->getUID();
            if (empty($uid)) {
                $newFile->setDownload(true);
            }
            $files[] = $newFile;
        }

        $newDocumentForm->setNewFiles($files);

        parent::createAction($newDocumentForm);
    }
}
