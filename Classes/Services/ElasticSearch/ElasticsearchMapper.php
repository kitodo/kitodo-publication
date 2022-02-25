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

use EWW\Dpf\Domain\Model\Client;
use EWW\Dpf\Helper\XSLTransformator;
use TYPO3\CMS\Core\Core\Environment;

class ElasticsearchMapper
{

    /**
     * [$documentRepository description]
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

    /**
     * clientConfigurationManager
     * 
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager 
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientConfigurationManager;

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientRepository = null;

    /**
     * document2json
     * @param  Document $document [description]
     * @return json Elasticsearch json format
     */
    public function getElasticsearchJson($document)
    {
        if (Client::$storagePid > 0) {
            $this->clientRepository->setStoragePid(Client::$storagePid);
        }

        /** @var \EWW\Dpf\Domain\Model\Client $client */
        $client = $this->clientRepository->findAll()->current();

        /** @var \EWW\Dpf\Domain\Model\TransformationFile $xsltTransformationFile */
        $xsltTransformationFile = $client->getElasticSearchTransformation()->current();

        if ($xsltTransformationFile) {
            $xsltDoc = Environment::getPublicPath() . '/fileadmin' .
                $xsltTransformationFile->getFile()->getOriginalResource()->getIdentifier();
        } else {
            throw new \Exception("Missing XSLT file for ElasticSearch json mapping.");
        }

        // xslt
        $xsl = new \DOMDocument;
        $xsl->load($xsltDoc);
        
        $xml = new \DOMDocument;
        $xml->loadXML($document->getXmlData());

        // xslt processing
        $proc = new \XSLTProcessor;
        $proc->importStyleSheet($xsl); // XSL Document importieren

        $json = $proc->transformToXML($xml);

        return $json;
    }

}
