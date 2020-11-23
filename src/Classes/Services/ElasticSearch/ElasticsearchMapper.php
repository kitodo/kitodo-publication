<?php
namespace EWW\Dpf\Services\ElasticSearch;

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

use EWW\Dpf\Helper\XSLTransformator;

class ElasticsearchMapper
{

    /**
     * [$documentRepository description]
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

    /**
     * clientConfigurationManager
     * 
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager 
     * @inject
     */
    protected $clientConfigurationManager;


    /**
     * document2json
     * @param  Document $document [description]
     * @return json Elasticsearch json format
     */
    public function getElasticsearchJson($document)
    {
        $xsltDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:dpf/Resources/Private/Xslt/mets-mods-xml2json.xsl');

        // xslt
        $xsl = new \DOMDocument;
        $xsl->load($xsltDoc);

        $XSLTransformator = new XSLTransformator();
        $transformedXml = $XSLTransformator->getTransformedOutputXML($document);

        $xml = new \DOMDocument;
        $xml->loadXML($transformedXml);

        // xslt processing
        $proc = new \XSLTProcessor;
        $proc->importStyleSheet($xsl); // XSL Document importieren

        $json = $proc->transformToXML($xml);

        return $json;
    }

}
