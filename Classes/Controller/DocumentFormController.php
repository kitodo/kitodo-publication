<?php
namespace EWW\Dpf\Controller;

class DocumentFormController extends AbstractDocumentFormController
{

    public function __construct()
    {
        parent::__construct();

    }

    protected function redirectToList($message = null)
    {
        $this->redirect('list', 'DocumentForm', null, array('message' => $message));
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
