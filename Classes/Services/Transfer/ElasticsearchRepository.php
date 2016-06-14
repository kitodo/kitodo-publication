<?php
namespace EWW\Dpf\Services\Transfer;

/**
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

require_once $extpath . '/Lib/Vendor/Httpful/Bootstrap.php';
\Httpful\Bootstrap::init();

use \Httpful\Request;

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

    protected $type;

    protected $url;

    public function __construct()
    {
        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);

        $this->host = $confArr['elasticSearchHost'] . ':' . $confArr['elasticSearchPort'];

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

        } catch (Exception $exception) {
            var_dump($exception);
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

        } catch (Exception $exception) {
            var_dump($exception);
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
