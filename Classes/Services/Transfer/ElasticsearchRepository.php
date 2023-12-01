<?php
namespace EWW\Dpf\Services\Transfer;

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

$extpath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dpf');

\Httpful\Bootstrap::init();

use EWW\Dpf\Configuration\ClientConfigurationManager;
use Httpful\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class ElasticsearchRepository
 * @package EWW\Dpf\Services\Transfer
 * @deprecated since version 4.0
 */
class ElasticsearchRepository implements Repository
{
    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientRepository = null;

    protected $host;

    protected $index;

    protected $type;

    protected $url;

    public function __construct()
    {

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        $this->host = $clientConfigurationManager->getElasticSearchHost() . ':' . $clientConfigurationManager->getElasticSearchPort();

        $this->index = 'fedora';

        $this->type = 'local';

        $this->url = $this->host . '/' . $this->index . '/' . $this->type . '/';

    }

    /**
     * saves new document into elasticsearch local
     * @param \EWW\Dpf\Domain\Model\Document $document
     */
    public function add($document, $json)
    {

        $client = $this->clientRepository->findAll()->current();

        // build es json
        $esJson                               = array();
        $esJson['OWNER_ID']                   = $client->getOwnerId();
        $esJson['_dissemination']             = array();
        $esJson['_dissemination']['_content'] = json_decode($json);

        $esJson = json_encode($esJson);

        try {
            // send json
            // updates if document id already exists
            $response = Request::put($this->url . $document->getUid())
                ->sendsJson()
                ->body($esJson)
                ->send();

        } catch (\Exception $exception) {

            if ($exception instanceof \Httpful\Exception\ConnectionErrorException) {
                $message = $exception->getMessage();
                throw new \EWW\Dpf\Exceptions\ElasticSearchConnectionErrorException($message);
            } else {
                throw $exception;
            }
        }

    }

    /**
     * removes document from elasticsearch local
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param string $state
     */
    public function delete($document, $state)
    {

        try {
            $response = Request::delete($this->url . $document->getUid())
                ->send();

        } catch (\Exception $exception) {

            if ($exception instanceof \Httpful\Exception\ConnectionErrorException) {
                $message = $exception->getMessage();
                throw new \EWW\Dpf\Exceptions\ElasticSearchConnectionErrorException($message);
            } else {
                throw $exception;
            }
        }

    }

    public function retrieve($id)
    {
        # not implemented yet
    }

    public function ingest($document, $metsXML)
    {
        # not implemented yet
    }

    public function update($document, $metsXml)
    {
        # not implemented yet
    }

    public function getNextDocumentId()
    {
        # not implemented yet
    }

}
