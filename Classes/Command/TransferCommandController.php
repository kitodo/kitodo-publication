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

        $repository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');

        return true;
    }
}
