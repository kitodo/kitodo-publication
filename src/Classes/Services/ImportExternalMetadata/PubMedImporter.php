<?php
namespace EWW\Dpf\Services\ImportExternalMetadata;

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

\Httpful\Bootstrap::init();

use \Httpful\Request;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use EWW\Dpf\Services\Transformer\DocumentTransformer;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Model\PubMedMetadata;
use EWW\Dpf\Domain\Model\ExternalMetadata;

class PubMedImporter extends AbstractImporter implements Importer
{
    /**
     * @var string
     */
    protected $apiUrl = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils";

    /**
     * @var string
     */
    protected $resource = "/esummary.fcgi?version=2.0&db=pubmed&id=";

    /**
     * @var string
     */
    protected $searchPath = "/esearch.fcgi?version=2.0&db=pubmed";

    /**
     * Returns the list of all publication types
     *
     * @return array
     */
    public static function types()
    {
        return [
            'Collected Works',
            'Congress',
            'Dataset',
            'Dictionary',
            'Journal Article'
        ];
    }

    /**
     * @param string $identifier
     * @return ExternalMetadata|null
     */
    public function findByIdentifier($identifier)
    {
        try {
            $response = Request::get($this->apiUrl . $this->resource . $identifier)->send();

            if (!$response->hasErrors() && $response->code == 200) {

                /** @var PubMedMetadata $pubMedMetadata */
                $pubMedMetadata = $this->objectManager->get(PubMedMetadata::class);

                $pubMedMetadata->setSource(get_class($this));
                $pubMedMetadata->setFeUser($this->security->getUser()->getUid());
                $pubMedMetadata->setData($response);
                $pubMedMetadata->setPublicationIdentifier($identifier);

                $xmlDataXpath = $pubMedMetadata->getDataXpath();

                $node = $xmlDataXpath->query('/eSummaryResult/ERROR');
                if ($node->length > 0) {
                    return null;
                }

                return $pubMedMetadata;

            } else {
                return null;
            }

        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
            throw $throwable;
        }

        return null;
    }

    /**
     * @param string $query
     * @param int $rows
     * @param int $offset
     * @param string $searchField
     * @return array|mixed
     * @throws \Throwable
     */
    public function search($query, $rows = 10, $offset = 0, $searchField = 'author')
    {
        $requestUri = $this->apiUrl . $this->searchPath . '&retmax='.$rows;

        if ($offset > 0) $requestUri .= '&retstart=' . $offset;

        $requestUri .= "&term=" . urlencode($query);

        $results = [];

        try {
            $response = Request::get($requestUri)->send();

            if (!$response->hasErrors() && $response->code == 200) {

                $dom = new \DOMDocument();
                if (is_null(@$dom->loadXML($response))) {
                    throw new \Exception("Invalid XML: " . get_class($this));
                }
                $xmlDataXpath = \EWW\Dpf\Helper\XPath::create($dom);

                $node = $xmlDataXpath->query('/eSearchResult/ERROR');
                if ($node->length > 0) {
                    return null;
                }

                $node = $xmlDataXpath->query('/eSearchResult/Count');
                if ($node->length > 0) {
                    $results['total-results'] = $node->item(0)->nodeValue;
                }

                $node = $xmlDataXpath->query('/eSearchResult/RetMax');
                if ($node->length > 0) {
                    $results['items-per-page'] = $node->item(0)->nodeValue;
                }

                $nodes = $xmlDataXpath->query('/eSearchResult/IdList/Id');
                if ($nodes->length > 0) {

                    $identifierList = [];
                    foreach ($nodes as $node) {
                        $identifierList[] = $node->nodeValue;
                    }

                    $results['items'] = $this->findByIdentifierList($identifierList);

                    return $results;
                }
            }
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
            throw $throwable;
        }

        return null;
    }

    /**
     * @return \EWW\Dpf\Domain\Model\TransformationFile
     */
    protected function getDefaultXsltTransformation() : ?\EWW\Dpf\Domain\Model\TransformationFile
    {
        /** @var \EWW\Dpf\Domain\Model\Client $client */
        $client = $this->clientRepository->findAll()->current();

        /** @var \EWW\Dpf\Domain\Model\TransformationFile $xsltTransformationFile */
        return $client->getPubmedTransformation()->current();
    }

    /**
     * @return string
     */
    protected function getDefaultXsltFilePath() : string
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
            'EXT:dpf/Resources/Private/Xslt/pubmed-default.xsl'
        );
    }

    /**
     * @return string
     */
    protected function getImporterName()
    {
        return 'pubmed';
    }

    /**
     * @param array $identifierList
     * @return array
     */
    protected function findByIdentifierList($identifierList)
    {
        try {
            $identifiers = implode(',', $identifierList);
            $response = Request::get($this->apiUrl . $this->resource . $identifiers)->send();

            if (!$response->hasErrors() && $response->code == 200) {

                $dom = new \DOMDocument();
                if (is_null(@$dom->loadXML($response))) {
                    throw new \Exception("Invalid XML: " . get_class($this));
                }
                $xmlDataXpath = \EWW\Dpf\Helper\XPath::create($dom);

                $node = $xmlDataXpath->query('/eSummaryResult/ERROR');
                if ($node->length > 0) {
                    return [];
                }

                $nodes = $xmlDataXpath->query('/eSummaryResult/DocumentSummarySet/DocumentSummary');
                if ($nodes->length > 0) {

                    $results = [];

                    foreach ($nodes as $nodeItem) {

                        $xml = '<eSummaryResult><DocumentSummarySet>';
                        $xml .= $dom->saveXML($nodeItem);
                        $xml .= '</DocumentSummarySet></eSummaryResult>';

                        $idNode = $xmlDataXpath->query('@uid', $nodeItem);
                        if ($idNode->length > 0) {
                            $identifier = $idNode->item(0)->nodeValue;

                            if ($identifier) {
                                $itemDom = new \DOMDocument();

                                if (is_null(@$itemDom->loadXML($xml))) {
                                    throw new \Exception("Invalid XML: " . get_class($this));
                                }

                                /** @var PubMedMetadata $pubMedMetadata */
                                $pubMedMetadata = $this->objectManager->get(PubMedMetadata::class);

                                $pubMedMetadata->setSource(get_class($this));
                                $pubMedMetadata->setFeUser($this->security->getUser()->getUid());
                                $pubMedMetadata->setData($xml);
                                $pubMedMetadata->setPublicationIdentifier($identifier);

                                $results[$identifier] = $pubMedMetadata;
                            }
                        }
                    }

                    return $results;
                }
            }
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
            throw $throwable;
        }

        return [];
    }
}
