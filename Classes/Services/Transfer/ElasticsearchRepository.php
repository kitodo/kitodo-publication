<?php
namespace EWW\Dpf\Services\Transfer;

$extpath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dpf');

require_once($extpath . '/Lib/Vendor/Httpful/Bootstrap.php');
\Httpful\Bootstrap::init();

use \Httpful\Request;


class ElasticsearchRepository implements Repository {

    protected $host;

    protected $index;

    protected $type;

    protected $url;



    public function __construct() {
        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);

        $this->host = $confArr['elasticSearchHost'].':'.$confArr['elasticSearchPort'];

        $this->index = 'fedora';

        $this->type = 'local';

        $this->url = $this->host . '/' . $this->index . '/' . $this->type . '/';

    }

    /**
     * saves new document into elasticsearch local
     * @param \EWW\Dpf\Domain\Model\Document $document
     */
    public function add($document, $json) {

        try {
            // send json
            // updates if document id already exists
            $response = Request::put($this->url . $document->getUid())
                ->sendsJson()
                ->body($json)
                ->send();

        } catch(Exception $exception) {
            var_dump($exception);
        }

    }


    /**
     * removes document from elasticsearch local
     * @param \EWW\Dpf\Domain\Model\Document $document
     */
    public function delete($document) {

        try {
            $response = Request::delete($this->url . $document->getUid())
                ->send();

        } catch(Exception $exception) {
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




}