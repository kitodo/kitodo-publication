<?php

namespace EWW\Dpf\Services\Xml;

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

use DOMDocument;
use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Domain\Repository\DocumentTypeRepository;
use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * ParserGenerator
 */
class ParserGenerator
{
    /**
     * clientConfigurationManager
     *
     * @var ClientConfigurationManager
     *
     */
    protected $clientConfigurationManager;

    /**
     * documentTypeRepository
     *
     * @var DocumentTypeRepository
     */
    protected $documentTypeRepository = null;

    /**
     * files from form
     * @var array
     */
    protected $files = array();

    /**
     * xml data
     * @var DOMDocument
     */
    protected $xmlData = null;

    /**
     * ref id counter
     */
    protected $counter = 0;

    /**
     * namespaces as string
     * @var string
     */
    protected $namespaceString = '';

    /**
     * ParserGenerator constructor.
     *
     * @param int $clientPid
     * @throws Exception
     */
    public function __construct(int $clientPid = 0)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        if ($clientPid) {
            $this->clientConfigurationManager->switchToClientStorage($clientPid);
        }

        $this->documentTypeRepository = $objectManager->get(DocumentTypeRepository::class);

        $namespaceConfigurationString = $this->clientConfigurationManager->getNamespaces();
        if (!empty($namespaceConfigurationString)) {
            $namespaceConfiguration = explode(";", $namespaceConfigurationString);
            foreach ($namespaceConfiguration as $value) {
                $namespace = explode("=", $value);
                $this->namespaceString .= ' xmlns:' . $namespace[0] . '="' . $namespace[1] . '"';
            }
        }

        $this->xmlData = new DOMDocument();
        $this->xmlData->loadXML("<data $this->namespaceString></data>");
    }

    /**
     * returns the mods xml string
     * @return string mods xml
     */
    public function getXMLData()
    {
        $xml = $this->xmlData->saveXML();
        // FIXME What in all heavens is replaced here and why?
        return preg_replace("/eww=\"\d-\d-\d\"/", '${1}${2}${3}', $xml);
    }

    /**
     * build mods from form array
     * @param array $array structured form data array
     */
    public function buildXmlFromForm(array $array)
    {
        $fedoraNamespace = $this->clientConfigurationManager->getFedoraNamespace();

        // Build xml mods from form fields
        // loop each group
        foreach ($array['metadata'] as $key => $group) {
            //groups
            $mapping = $group['mapping'];

            $values = $group['values'];
            $attributes = $group['attributes'];

            $attributeXPath = '';
            $extensionAttribute = '';
            foreach ($attributes as $attribute) {
                if (!$attribute["modsExtension"]) {
                    $attributeXPath .= '[' . $attribute['mapping'] . '="' . $attribute['value'] . '"]';
                } else {
                    $extensionAttribute .= '[' . $attribute['mapping'] . '="' . $attribute['value'] . '"]';
                }
            }

            // mods extension
            if ($group['modsExtensionMapping']) {
                $counter = sprintf("%'03d", $this->counter);
                $attributeXPath .= '[@ID="' . $fedoraNamespace . '_' . $counter . '"]';
            }

            $existsExtensionFlag = false;
            $i = 0;
            // loop each object
            if (!empty($values)) {
                //$values = empty($values)? [] : $values;
                foreach ($values as $value) {
                    if ($value['mapping'] != '.') {
                        $value['mapping'] .= '[@metadata-item-id=' . '"' . $value['id'] . '"' . ']';
                    }

                    if ($value['modsExtension']) {
                        $existsExtensionFlag = true;
                        // mods extension
                        $counter = sprintf("%'03d", $this->counter);
                        $referenceAttribute = $extensionAttribute . '[@' . $group['modsExtensionReference'] . '="' . $fedoraNamespace . '_' . $counter . '"]';

                        $path = $group['modsExtensionMapping'] . $referenceAttribute . '%/' . $value['mapping'];

                        $this->customXPath($path, false, $value['value']);
                    } else {

                        $path = $mapping . $attributeXPath . '%/' . $value['mapping'];

                        if ($i == 0) {
                            $newGroupFlag = true;
                        } else {
                            $newGroupFlag = false;
                        }

                        $this->customXPath($path, $newGroupFlag, $value['value']);
                        $i++;
                    }
                }
            } else {
                if (!empty($attributeXPath)) {
                    $path = $mapping . $attributeXPath;
                    $this->customXPath($path, true, '', true);
                }
            }
            if (!$existsExtensionFlag && $group['modsExtensionMapping']) {
                $counter = sprintf("%'03d", $this->counter);
                $xPath = $group['modsExtensionMapping'] . $extensionAttribute . '[@' . $group['modsExtensionReference'] . '="' . $fedoraNamespace . '_' . $counter . '"]';
                $this->customXPath($xPath, true, '', true);
            }
            if ($group['modsExtensionMapping']) {
                $this->counter++;
            }
        }
    }

    /**
     * Customized xPath parser
     * @param string $xPath xpath expression
     * @param string $value form value
     * @return string created xml
     */
    public function customXPath(string $xPath, $newGroupFlag = false, string $value = '', $attributeOnly = false): string
    {
        if (!$attributeOnly) {
            // Explode xPath
            $newPath = explode('%', $xPath);

            $explodedXPath = explode('[', $newPath[0]);
            if (count($explodedXPath) > 1) {
                // predicate is given
                if (substr($explodedXPath[1], 0, 1) == "@") {
                    // attribute
                    $path = $newPath[0];
                } else {
                    // path
                    $path = $explodedXPath[0];
                }
            } else {
                $path = $newPath[0];
            }

            if (isset($value) === true && $value !== '') {
                // Escape quotes for use in XPath expression
                $escapedValue = str_ireplace('"', '\"', $value);
                $newPath[1] = $newPath[1] . '="' . $escapedValue . '"';
            }

            $modsDataXPath = XPath::create($this->xmlData);

            if (!$newGroupFlag && $modsDataXPath->query('/data/' . $newPath[0])->length > 0) {
                // first xpath path exist

                // build xml from second xpath part
                $xml = XMLFragmentGenerator::fragmentFor($newPath[1]);

                // check if xpath [] are nested
                $search = '/(\/\w*:\w*)\[(.*?)]/';
                preg_match($search, $newPath[1], $match);
                preg_match($search, $match[2], $secondMatch);
                // first part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    $nested = $match[2];

                    $nestedXml = XMLFragmentGenerator::fragmentFor($nested);

                    // object xpath without nested element []
                    $newPath[1] = str_replace('[' . $match[2] . ']', '', $newPath[1]);

                    $xml = XMLFragmentGenerator::fragmentFor($newPath[1]);
                }

                // FIXME: XMLFragmentGenerator does not generate namespace declarations,
                // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
                // since it is about child elements that are then added to the overall XML.
                libxml_use_internal_errors(true);
                $docXML = new DOMDocument();
                $docXML->loadXML($xml);
                libxml_use_internal_errors(false);

                $domXPath = XPath::create($this->xmlData);

                // second part nested xpath
                if ($match[2] && $secondMatch[2]) {

                    // import node from nested
                    // FIXME: XMLFragmentGenerator does not generate namespace declarations,
                    // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
                    // since it is about child elements that are then added to the overall XML.
                    libxml_use_internal_errors(true);
                    $docXMLNested = new DOMDocument();
                    $docXMLNested->loadXML($nestedXml);
                    libxml_use_internal_errors(false);

                    $xPath = XPath::create($docXML);

                    $nodeList = $xPath->query($match[1]);
                    $node = $nodeList->item(0);

                    $importNode = $docXML->importNode($docXMLNested->getElementsByTagName("mods")->item(0)->firstChild, true);

                    $node->appendChild($importNode);
                }

                $domNode = $domXPath->query('/data/' . $path);
                $node = $docXML->documentElement;

                $nodeAppendModsData = $this->xmlData->importNode($node, true);
                $domNode->item($domNode->length - 1)->appendChild($nodeAppendModsData);
            } else {
                // first xpath doesn't exist
                // parse first xpath part
                $xml1 = '<data' . $this->namespaceString . '>' . XMLFragmentGenerator::fragmentFor($newPath[0]) . '</data>';

                $doc1 = new DOMDocument();
                $doc1->loadXML($xml1);

                $domXPath = XPath::create($doc1);

                $domNode = $domXPath->query('//' . $path);

                // parse second xpath part
                $xml2 = '<data' . $this->namespaceString . '>' . XMLFragmentGenerator::fragmentFor($path . $newPath[1]) . '</data>';

                // check if xpath [] are nested
                $search = '/(\/\w*:?\w*)\[(.*)]/';
                preg_match($search, $newPath[1], $match);
                preg_match($search, $match[2], $secondMatch);

                // first part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    $nested = $match[2];

                    $nestedXml = '<data' . $this->namespaceString . '>' . XMLFragmentGenerator::fragmentFor($nested) . '</data>';

                    // object xpath without nested element []
                    $newPath[1] = str_replace('[' . $match[2] . ']', '', $newPath[1]);

                    $xml2 = '<data' . $this->namespaceString . '>' . XMLFragmentGenerator::fragmentFor($path . $newPath[1]) . '</data>';
                }

                $doc2 = new DOMDocument();
                $doc2->loadXML($xml2);

                $domXPath2 = XPath::create($doc2);

                // second part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    // import node from nested
                    $docXMLNested = new DOMDocument();
                    $docXMLNested->loadXML($nestedXml);

                    $xPath = XPath::create($doc2);
                    $nodeList = $xPath->query('//' . $path . $match[1]);
                    $node = $nodeList->item(0);

                    $importNode = $doc2->importNode($docXMLNested->documentElement, true);

                    $node->appendChild($importNode);
                }
                $domNode2 = $domXPath2->query('//' . $path)->item(0)->childNodes->item(0);

                // merge xml nodes
                $nodeToBeAppended = $doc1->importNode($domNode2, true);
                $domNode->item(0)->appendChild($nodeToBeAppended);

                // add to modsData (merge not required)
                // get mods tag
                foreach ($this->xmlData->childNodes as $childNode) {
                    // Skip comments inside the xml.
                    if ($childNode instanceof \DOMElement) {
                        $firstChild = $childNode;
                        break;
                    }
                }
                $firstItem = $doc1->documentElement->firstChild;
                $nodeAppendModsData = $this->xmlData->importNode($firstItem, true);
                if (isset($firstChild)) {
                    $firstChild->appendChild($nodeAppendModsData);
                }

                return $doc1->saveXML();
            }
        } else {
            // attribute only
            $xml = XMLFragmentGenerator::fragmentFor($xPath);

            // FIXME: XMLFragmentGenerator does not generate namespace declarations,
            // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
            // since it is about child elements that are then added to the overall XML.
            libxml_use_internal_errors(true);
            $docXML = new DOMDocument();
            $docXML->loadXML($xml);
            libxml_use_internal_errors(false);

            $domXPath = XPath::create($this->xmlData);
            $domNode = $domXPath->query('/data');

            $node = $docXML->documentElement;

            $nodeAppendModsData = $this->xmlData->importNode($node, true);
            $domNode->item($domNode->length - 1)->appendChild($nodeAppendModsData);

            return $docXML->saveXML();
        }

        return $this->xmlData->saveXML();
    }

    public function setDomDocument(DOMDocument $document)
    {
        $this->xmlData = $document;
    }

}
