<?php
namespace EWW\Dpf\Services;

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
use EWW\Dpf\Domain\Repository\DocumentTypeRepository;
use EWW\Dpf\Helper\XSLTransformator;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Services\Transformer\DocumentTransformer;

/**
 * ParserGenerator
 */
class ParserGenerator
{
    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     *
     */
    protected $clientConfigurationManager;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     */
    protected $documentTypeRepository = null;

    /**
     * formData
     *
     * @var array
     */
    protected $formData = array();

    /**
     * files from form
     * @var array
     */
    protected $files = array();

    /**
     * metsData
     *
     * @var  DOMDocument
     */
    protected $metsData = '';

    /**
     * xml data
     * @var DOMDocument
     */
    protected $xmlData = '';

    /**
     * xml header
     * @var string
     */
    protected $xmlHeader = '';

    /**
     * xPathXMLGenerator
     * @var object
     */
    protected $parser = null;

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
     * @param int $clientPid
     */
    public function __construct($clientPid = 0)
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        if ($clientPid) {
            $this->clientConfigurationManager->setConfigurationPid($clientPid);
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

        $this->xmlHeader = '<data' . $this->namespaceString . '></data>';

        $this->xmlData =  new \DOMDocument();
        $this->xmlData->loadXML($this->xmlHeader);

        // Parser
        include_once 'XPathXMLGenerator.php';

        $this->parser = new XPathXMLGenerator();
    }

    /**
     * returns the mods xml string
     * @return string mods xml
     */
    public function getXMLData()
    {
        $xml = $this->xmlData->saveXML();
        $xml = preg_replace("/eww=\"\d-\d-\d\"/", '${1}${2}${3}', $xml);

        return $xml;
    }

    /**
     * build mods from form array
     * @param array $array structured form data array
     */
    public function buildXmlFromForm($array)
    {
        $fedoraNamespace = $this->clientConfigurationManager->getFedoraNamespace();

        $this->xmlData = $this->xmlData;
        // Build xml mods from form fields
        // loop each group
        foreach ($array['metadata'] as $key => $group) {
            //groups
            $mapping = $group['mapping'];

            $values     = $group['values'];
            $attributes = $group['attributes'];

            $attributeXPath     = '';
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
                $attributeXPath .= '[@ID="'.$fedoraNamespace.'_' . $counter . '"]';
            }

            $existsExtensionFlag = false;
            $i                   = 0;
            // loop each object
            if (!empty($values)) {
                //$values = empty($values)? [] : $values;
                foreach ($values as $value) {
                    if ($value['mapping'] != '.') {
                        $value['mapping'] .= '[@metadata-item-id='. '"' . $value['id'] . '"' .']';
                    }

                    if ($value['modsExtension']) {
                        $existsExtensionFlag = true;
                        // mods extension
                        $counter = sprintf("%'03d", $this->counter);
                        $referenceAttribute = $extensionAttribute . '[@' . $group['modsExtensionReference'] . '="' . $fedoraNamespace . '_' . $counter . '"]';

                        $path = $group['modsExtensionMapping'] . $referenceAttribute . '%/' . $value['mapping'];

                        $xml = $this->customXPath($path, false, $value['value']);
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
                $xPath = $group['modsExtensionMapping'] . $extensionAttribute . '[@' . $group['modsExtensionReference'] . '="'.$fedoraNamespace.'_' . $counter . '"]';
                $xml   = $this->customXPath($xPath, true, '', true);
            }
            if ($group['modsExtensionMapping']) {
                $this->counter++;
            }
        }
    }

    /**
     * get xml from xpath
     * @param  xpath $xPath xPath expression
     * @return xml
     */
    public function parseXPath($xPath)
    {

        $this->parser->generateXmlFromXPath($xPath);
        $xml = $this->parser->getXML();

        return $xml;
    }

    public function parseXPathWrapped($xPath)
    {
        $this->parser->generateXmlFromXPath($xPath);
        $xml = $this->parser->getXML();

        $xml = '<data' . $this->namespaceString . '>' . $xml . '</data>';

        return $xml;
    }

    /**
     * Customized xPath parser
     * @param  xpath  $xPath xpath expression
     * @param  string $value form value
     * @return xml    created xml
     */
    public function customXPath($xPath, $newGroupFlag = false, $value = '', $attributeOnly = false)
    {
        if (!$attributeOnly) {
            // Explode xPath
            $newPath = explode('%', $xPath);

            $praedicateFlag = false;
            $explodedXPath  = explode('[', $newPath[0]);
            if (count($explodedXPath) > 1) {
                // praedicate is given
                if (substr($explodedXPath[1], 0, 1) == "@") {
                    // attribute
                    $path = $newPath[0];
                } else {
                    // path
                    $path = $explodedXPath[0];
                }

                $praedicateFlag = true;
            } else {
                $path = $newPath[0];
            }

            if (isset($value) === true && $value !== '') {
                $newPath[1] = $newPath[1] . '="' . $value . '"';
            }

            $modsDataXPath = \EWW\Dpf\Helper\XPath::create($this->xmlData);

            if (!$newGroupFlag && $modsDataXPath->query('/data/' . $newPath[0])->length > 0) {
                // first xpath path exist

                // build xml from second xpath part
                $xml = $this->parseXPath($newPath[1]);

                // check if xpath [] are nested
                $search = '/(\/\w*:\w*)\[(.*)\]/';
                preg_match($search, $newPath[1], $match);
                preg_match($search, $match[2], $secondMatch);
                // first part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    $nested = $match[2];

                    $nestedXml = $this->parseXPath($nested);

                    // object xpath without nested element []
                    $newPath[1] = str_replace('['.$match[2].']', '', $newPath[1]);

                    $xml = $this->parseXPath($newPath[1]);

                }

                // FIXME: XPATHXmlGenerator XPATH does not generate any namespaces,
                // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
                // since it is about child elements that are then added to the overall XML.
                libxml_use_internal_errors(true);
                $docXML = new \DOMDocument();
                $docXML->loadXML($xml);
                libxml_use_internal_errors(false);

                $domXPath = \EWW\Dpf\Helper\XPath::create($this->xmlData);

                // second part nested xpath
                if ($match[2] && $secondMatch[2]) {

                    // import node from nested
                    // FIXME: XPATHXmlGenerator XPATH does not generate any namespaces,
                    // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
                    // since it is about child elements that are then added to the overall XML.
                    libxml_use_internal_errors(true);
                    $docXMLNested = new \DOMDocument();
                    $docXMLNested->loadXML($nestedXml);
                    libxml_use_internal_errors(false);

                    $xPath = \EWW\Dpf\Helper\XPath::create($docXML);

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
                $xml1 = $this->parseXPathWrapped($newPath[0]);

                $doc1 = new \DOMDocument();
                $doc1->loadXML($xml1);

                $domXPath = \EWW\Dpf\Helper\XPath::create($doc1);

                $domNode = $domXPath->query('//' . $path);

                // parse second xpath part
                $xml2 = $this->parseXPathWrapped($path . $newPath[1]);

                // check if xpath [] are nested
                $search = '/(\/\w*:?\w*)\[(.*)\]/';
                preg_match($search, $newPath[1], $match);
                preg_match($search, $match[2], $secondMatch);

                // first part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    $nested = $match[2];

                    $nestedXml = $this->parseXPathWrapped($nested);

                    // object xpath without nested element []
                    $newPath[1] = str_replace('['.$match[2].']', '', $newPath[1]);

                    $xml2 = $this->parseXPathWrapped($path . $newPath[1]);
                }

                $doc2 = new \DOMDocument();
                $doc2->loadXML($xml2);

                $domXPath2 = \EWW\Dpf\Helper\XPath::create($doc2);

                  // second part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    // import node from nested
                    $docXMLNested = new \DOMDocument();
                    $docXMLNested->loadXML($nestedXml);

                    $xPath = \EWW\Dpf\Helper\XPath::create($doc2);
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
                //$firstChild = $this->xmlData->childNodes->item(0);
                $firstItem = $doc1->documentElement->firstChild;
                $nodeAppendModsData = $this->xmlData->importNode($firstItem, true);
                $firstChild->appendChild($nodeAppendModsData);

                return $doc1->saveXML();
            }
        } else {
            // attribute only
            $xml = $this->parseXPath($xPath);

            // FIXME: XPATHXmlGenerator XPATH does not generate any namespaces,
            // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
            // since it is about child elements that are then added to the overall XML.
            libxml_use_internal_errors(true);
            $docXML = new \DOMDocument();
            $docXML->loadXML($xml);
            libxml_use_internal_errors(false);

            $domXPath = \EWW\Dpf\Helper\XPath::create($this->xmlData);
            $domNode  = $domXPath->query('/data');

            $node = $docXML->documentElement;

            $nodeAppendModsData = $this->xmlData->importNode($node, true);
            $domNode->item($domNode->length - 1)->appendChild($nodeAppendModsData);

            return $docXML->saveXML();
        }

        return $this->xmlData->saveXML();
    }

    public function setXML($value = '') {
        $domDocument = new \DOMDocument();
        if (is_null(@$domDocument->loadXML($value))) {
            throw new \Exception("Couldn't load MODS data");
        }
        $this->xmlData = $domDocument;
    }
}
