<?php
namespace EWW\Dpf\Command;

class TransferCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{

    /**
     * metadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
     * @inject
     */
    protected $metadataObjectRepository = null;

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

    public function transferCommand()
    {

        $success = true;

        //$repository = new \EWW\Dpf\Services\Transfer\FedoraRepository();

        $repository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');

        //return $repository->ingest("");

        /*
        try {

        $repository = new FedoraRepository();

        $tasks = new tx_dpfscheduler_task_model();

        $tasks->findUnsentTasks();

        while ($unsent_task = $tasks->nextUnsentTask()) {

        $uid = $unsent_task['uid'];

        // write mods data to a temporary file and send it.
        $temp_mods_data_file = PATH_site."typo3temp/sword_mods_".$uid.".xml";
        $mods_data = $unsent_task['mods_data'];

        if ( file_put_contents( $temp_mods_data_file, $mods_data ) ) {

        // send mods data
        $sword_result = $repository->deposit("text/xml",$temp_mods_data_file);

        if ($sword_result->success()) {
        // mark task as sent
        $success = $success && $tasks->updateTask($uid,0,$sword_result->getResponse(),$sword_result->getHttpStatus(),$sword_result->getErrorMessage());
        } else {
        $tasks->updateTask($uid,1,$sword_result->getResponse(),$sword_result->getHttpStatus(),$sword_result->getErrorMessage());
        $success = FALSE;
        }
        }
        }

        return $success;
        } catch ( Exception $e ) {
        return false;
        }
         */

        return true;
    }
}
