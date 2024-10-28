<?php
namespace EWW\Dpf\Services\Api;

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

use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class DocumentToJsonMapper
{
    /**
     * @var string
     */
    protected $mapping;

    /**
     * @var \DOMXpath
     */
    protected $xpath;

    /**
     * @var ClientConfigurationManager
     */
    protected $clientConfigurationManager;

    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);
    }
        /**
     * @return string
     */
    public function getMapping(): string
    {
        return $this->mapping;
    }

    /**
     * @param string $mapping
     */
    public function setMapping(string $mapping): void
    {
        $this->mapping = $mapping;
    }

    /**
     * Gets a json representation of the given document
     *
     * @param Document $document
     * @return string
     */
    public function getJson(Document $document)
    {
        $internalFormat = new \EWW\Dpf\Services\Api\InternalFormat($document->getXmlData());
        $this->xpath = $internalFormat->getXpath();
        $mapping = json_decode($this->getMapping(), true);
        $data = $this->crawl($mapping);

        $aliasStateName = $this->clientConfigurationManager->getFisApiWorkflowStateName();

        if ($aliasStateName) {
            $data[$aliasStateName] = DocumentWorkflow::getAliasStateByLocalOrRepositoryState($document->getState());
        }

        return json_encode($data);
    }

    /**
     * Crawls the given configuration data (an array representation of json data) and creates the document-json by
     * using the mapping information inside this configuration.
     *
     * @param array $elements
     * @param \DOMNode $parentNode
     * @return array
     */
    protected function crawl($elements, \DOMNode $parentNode = null)
    {
        $branch = [];

        foreach ($elements as $index => $items) {

            if ($index == "_mapping") {
                continue;
            }

            if (array_key_exists(0, $items)) {
                $items = $items[0];
            }

            $mapping = $items["_mapping"];

            if (is_array($items) && sizeof($items) - (array_key_exists("_mapping", $items)? 1 : 0) > 0) {

                if (empty($parentNode) || $parentNode instanceof \DOMElement) {

                    $nodes = $this->xpath->query($mapping, $parentNode);

                    if ($nodes->length == 1) {
                        $itemCrawl = $this->crawl($items, $nodes->item(0));
                        if (!empty($itemCrawl)) {
                            $branch[$index] = $itemCrawl;
                        }
                    } else {
                        foreach ($nodes as $node) {
                            $nodeCrawl = $this->crawl($items, $node);
                            if (!empty($nodeCrawl)) {
                                $branch[$index][] = $nodeCrawl;
                            }
                        }
                    }
                }

            } else {
                if (empty($parentNode) || $parentNode instanceof \DOMElement) {

                    $nodes = $this->xpath->query($mapping, $parentNode);

                    if ($nodes->length == 1) {
                        $itemValue = trim($nodes->item(0)->nodeValue);
                        if (!empty($itemValue)) {
                            $branch[$index] = $itemValue;
                        }
                    } else {
                        foreach ($nodes as $k => $node) {
                            $nodeValue = trim($node->nodeValue);
                            if (!empty($nodeValue)) {
                                $branch[$index][] = $nodeValue;
                            }
                        }
                    }
                }
            }
        }

        return $branch;
    }
}
