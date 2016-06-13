<?php
namespace EWW\Dpf\Tasks;

class TransferTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    public function execute()
    {

        $success = true;

        $repository = new \EWW\Dpf\Services\Transfer\FedoraRepository();

        return $repository->ingest("");

        return true;

    }

}
