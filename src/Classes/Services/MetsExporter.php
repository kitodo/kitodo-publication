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
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Services\Transformer\DocumentTransformer;

/**
 * MetsExporter
 */
class MetsExporter
{
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
     * objId
     * @var string
     */
    protected $objId = '';


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->xmlHeader = '<kitodopublication></kitodopublication>';

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
     * @param $document
     * @return string The transformed xml
     */
    public function getTransformedXML($document)
    {
        $documentType = $document->getDocumentType();
        $transformationFile = $documentType->getTransformationFile()->current();
        $filePath = $transformationFile->getFile()->getOriginalResource()->getIdentifier();

        $documentTransformer = new DocumentTransformer();

        $transformedXml = $documentTransformer->transform(PATH_site . 'fileadmin' . $filePath, $this->getXMLData());

        return $transformedXml;
    }


    /**
     * build mods from form array
     * @param array $array structured form data array
     */
    public function buildModsFromForm($array)
    {
        $this->xmlData = $this->xmlData;
        // Build xml mods from form fields
        // loop each group
        foreach ($array['metadata'] as $key => $group) {
            //groups
            $mapping = $group['mapping'];

            $values     = $group['values'];
            $attributes = $group['attributes'];

            $attributeXPath     = '';
            foreach ($attributes as $attribute) {
                $attributeXPath .= '[' . $attribute['mapping'] . '="' . $attribute['value'] . '"]';
            }

            $i = 0;
            // loop each object
            if (!empty($values)) {
                foreach ($values as $value) {
                    $path = $mapping . $attributeXPath . '%/' . $value['mapping'];

                    if ($i == 0) {
                        $newGroupFlag = true;
                    } else {
                        $newGroupFlag = false;
                    }

                    $xml = $this->customXPath($path, $newGroupFlag, $value['value']);
                    $i++;

                }
            } else {
                if (!empty($attributeXPath)) {
                    $path = $mapping . $attributeXPath;
                    $xml  = $this->customXPath($path, true, '', true);
                }
            }
        }

        $this->files = $array['files'];
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

            if (!empty($value)) {
                $newPath[1] = $newPath[1] . '="' . $value . '"';
            }

            $modsDataXPath = \EWW\Dpf\Helper\XPath::create($this->xmlData);

            if (!$newGroupFlag && $modsDataXPath->query('/kitodopublication/' . $newPath[0])->length > 0) {
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
                
                $docXML = new \DOMDocument();
                $docXML->loadXML($xml);

                $domXPath = \EWW\Dpf\Helper\XPath::create($this->xmlData);

                // second part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    // import node from nested
                    $docXMLNested = new \DOMDocument();
                    $docXMLNested->loadXML($nestedXml);

                    $xPath = \EWW\Dpf\Helper\XPath::create($docXML);

                    $nodeList = $xPath->query($match[1]);
                    $node = $nodeList->item(0);

                    $importNode = $docXML->importNode($docXMLNested->getElementsByTagName("mods")->item(0)->firstChild, true);

                    $node->appendChild($importNode);
                }

                $domNode = $domXPath->query('/kitodopublication/' . $path);
                $node = $docXML->documentElement;

                $nodeAppendModsData = $this->xmlData->importNode($node, true);
                $domNode->item($domNode->length - 1)->appendChild($nodeAppendModsData);
            } else {
                // first xpath doesn't exist
                // parse first xpath part
                $xml1 = $this->parseXPath($newPath[0]);

                $doc1 = new \DOMDocument();
                $doc1->loadXML($xml1);

                $domXPath = \EWW\Dpf\Helper\XPath::create($doc1);

                $domNode = $domXPath->query('//' . $path);

                // parse second xpath part
                $xml2 = $this->parseXPath($path . $newPath[1]);

                // check if xpath [] are nested
                $search = '/(\/\w*:?\w*)\[(.*)\]/';
                preg_match($search, $newPath[1], $match);
                preg_match($search, $match[2], $secondMatch);

                // first part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    $nested = $match[2];

                    $nestedXml = $this->parseXPath($nested);

                    // object xpath without nested element []
                    $newPath[1] = str_replace('['.$match[2].']', '', $newPath[1]);

                    $xml2 = $this->parseXPath($path . $newPath[1]);
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
                $firstChild = $this->xmlData->firstChild;
                $firstItem = $doc1->documentElement;

                $nodeAppendModsData = $this->xmlData->importNode($firstItem, true);
                $firstChild->appendChild($nodeAppendModsData);

                return $doc1->saveXML();
            }
        } else {
            // attribute only
            $xml = $this->parseXPath($xPath);

            $docXML = new \DOMDocument();
            $docXML->loadXML($xml);

            $domXPath = \EWW\Dpf\Helper\XPath::create($this->xmlData);
            $domNode  = $domXPath->query('/kitodopublication');

            $domNodeList = $docXML->getElementsByTagName("mods");

            $node = $domNodeList->documentElement;

            $nodeAppendModsData = $this->xmlData->importNode($node, true);
            $domNode->item($domNode->length - 1)->appendChild($nodeAppendModsData);

            return $docXML->saveXML();
        }

        return $this->xmlData->saveXML();
    }


    public function setMods($value = '')
    {
        $domDocument = new \DOMDocument();
        if (is_null(@$domDocument->loadXML($value))) {
            throw new \Exception("Couldn't load MODS data");
        }
        $this->modsData = $domDocument;
    }

    public function setFileData($value = '')
    {
        $this->files = $value;
    }

    public function loopFiles($array, $domElement, $domDocument)
    {
        $i = 0;
        // set xml for uploded files
        foreach ($array as $key => $value) {
            $file = $domDocument->createElement('mets:file');
            $file->setAttribute('ID', $value['id']);
            if ($value['use'] == 'DELETE') {
                $file->setAttribute('USE', $value['use']);
                $domElement->appendChild($file);
            } else {
                $file->setAttribute('MIMETYPE', $value['type']);

                if ($value['use']) {
                    $file->setAttribute('USE', $value['use']);
                }

                if ($value['title']) {
                    $file->setAttribute('mext:LABEL', $value['title']);
                }

                $domElement->appendChild($file);
                $domElementFLocat = $domElement->childNodes->item($i);

                if ($value['hasFLocat']) {
                    $fLocat = $domDocument->createElement('mets:FLocat');
                    $fLocat->setAttribute('LOCTYPE', 'URL');
                    $fLocat->setAttribute('xlink:href', $value['path']);
                    $fLocat->setAttribute('xmlns:xlink', "http://www.w3.org/1999/xlink");
                    $domElementFLocat->appendChild($fLocat);
                }

            }

            $i++;
        }
    }

//    /**
//     * Builds the xml fileSection part if files are uploaded
//     * @return xml
//     */
//    public function buildFileSection()
//    {
//        if (empty($this->files['original']) && empty($this->files['download'])) {
//            return;
//        }
//
//        $domDocument = new \DOMDocument();
//        $domDocument->loadXML($this->metsHeader);
//
//        $domElement = $domDocument->firstChild;
//
//        $fileSec = $domDocument->createElement('mets:fileSec');
//        $domElement->appendChild($fileSec);
//
//        $domElement = $domElement->firstChild;
//
//        $fileSecElement = $domElement;
//
//        $fileGrpOriginal = $domDocument->createElement('mets:fileGrp');
//        $fileGrpOriginal->setAttribute('xmlns:mext', "http://slub-dresden.de/mets");
//        $fileGrpOriginal->setAttribute('USE', 'ORIGINAL');
//
//        // loop xml file entries
//        if (!empty($this->files['original'])) {
//            $this->loopFiles($this->files['original'], $fileGrpOriginal, $domDocument);
//            $domElement->appendChild($fileGrpOriginal);
//        }
//
//        // switch back to filesec element
//        $domElement = $fileSecElement;
//
//        $fileGrpDownload = $domDocument->createElement('mets:fileGrp');
//        $fileGrpDownload->setAttribute('xmlns:mext', "http://slub-dresden.de/mets");
//        $fileGrpDownload->setAttribute('USE', 'DOWNLOAD');
//
//        // loop xml
//        if (!empty($this->files['download'])) {
//            $this->loopFiles($this->files['download'], $fileGrpDownload, $domDocument);
//            $domElement->appendChild($fileGrpDownload);
//        }
//
//        return $domDocument;
//    }

//    public function setSlubInfo($value = '')
//    {
//        // build DOMDocument with slub xml
//        $domDocument = new \DOMDocument();
//        $domDocument->loadXML($value);
//        $this->slubData = $domDocument;
//    }

    /**
     *
     * @param string $objId
     * @return void
     */
    public function setObjId($objId)
    {
        $this->objId = $objId;
    }
}
