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

use \Httpful\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Configuration\ClientConfigurationManager;

class ElasticsearchRepository implements Repository
{

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @inject
     */
    protected $clientRepository = null;

    protected $host;

    protected $index;

    protected $url;

    protected $esUser = '';

    protected $esPassword = '';

    public function __construct()
    {

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        $this->esUser     = $clientConfigurationManager->getElasticSearchWriterUser();
        $this->esPassword = $clientConfigurationManager->getElasticSearchWriterPassword();
        $this->host = 'https://' . $clientConfigurationManager->getElasticSearchHost()
            . ':' . $clientConfigurationManager->getElasticSearchPort();

        $this->index = 'fedora';

        $this->url = $this->host . '/' . $this->index . '/_doc/';

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
        $esJson['join_field']                 = 'object';
        $esJson['_dissemination']             = array();
        $esJson['_dissemination']['_content'] = json_decode($json);

        $esJson = json_encode($esJson);

        try {
            // send json
            // updates if document id already exists
            $response = Request::put($this->url . $document->getUid())
                ->sendsJson()
                ->body($esJson)
                ->basicAuth($this->esUser, $this->esPassword)
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
                ->basicAuth($this->esUser, $this->esPassword)
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
