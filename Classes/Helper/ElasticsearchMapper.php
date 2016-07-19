<?php
namespace EWW\Dpf\Helper;

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

class ElasticsearchMapper
{

    /**
     * [$documentRepository description]
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

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
        $xsltDoc = 'http://' . $confArr['fedoraHost'] . '/fedora/objects/qucosa:XSLT/datastreams/METS-MODS-XML2JSON/content';

        // xslt
        $xsl = new \DOMDocument;

        $xsl->load($xsltDoc);

        $exporter = new \EWW\Dpf\Services\MetsExporter();
        $fileData = $document->getFileData();
        $exporter->setFileData($fileData);

        // slub:info
        $exporter->setSlubInfo($document->getSlubInfoData());

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
