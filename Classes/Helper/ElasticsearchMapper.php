<?php
namespace EWW\Dpf\Helper;

class ElasticsearchMapper {

    /**
     * [$documentRepository description]
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = NULL;

    /**
     * document2json
     * @param  Document $document [description]
     * @return json           Elasticsearch json format
     */
    public function getElasticsearchJson($document)
    {
        // document 2 json
        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
        
        // load xslt from fedora
        $xsltDoc = 'http://'.$confArr['fedoraHost'].'/fedora/objects/qucosa:XSLT/datastreams/METS-MODS-XML2JSON/content';

        // xslt
        $xsl = new \DOMDocument;

        // $xsl->load($extPath.'/'.'Resources/Private/XSLT/METS-MODS-XML2JSON.xml');
        $xsl->load($xsltDoc);


        $exporter = new \EWW\Dpf\Services\MetsExporter();
        $fileData = $document->getFileData();
        $exporter->setFileData($fileData);
               
        // slub:info
        $exporter->setSlubInfo($document->getSlubInfoData());

        // $exporter->setSlubInfo(array('documentType' => $document->getDocumentType()->getName()));
        $exporter->setMods($document->getXmlData());
        $exporter->buildMets();
        $metsXml = $exporter->getMetsData();

        $xml = new \DOMDocument;
        $xml->loadXML($metsXml);

        // xslt processing
        $proc = new \XSLTProcessor;
        $proc->importStyleSheet($xsl); // XSL Document importieren          

        $json = $proc->transformToXML($xml);

        return $json;
    }


}