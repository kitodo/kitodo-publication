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
     * mods xml data
     * @var DOMDocument
     */
    protected $modsData = '';

    protected $xmlData = '';

    /**
     * slub xml data
     * @var DOMDocument
     */
    protected $slubData = '';

    protected $slubMetsData = '';

    /**
     * metsHeader
     * @var string
     */
    protected $metsHeader = '';

    /**
     * mods xml header
     * @var string
     */
    protected $modsHeader = '';

    protected $slubHeader = '';

    /**
     * simpleXMLElement
     */
    protected $sxe = null;

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
        // mets data beginning
        $this->metsHeader = '<mets:mets xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                            xmlns:mets="http://www.loc.gov/METS/" xmlns:xlink="http://www.w3.org/1999/xlink"
                            xsi:schemaLocation="http://www.loc.gov/METS/ http://www.loc.gov/standards/mets/version19/mets.v1-9.xsd">';

        // Mets structure end
        $this->metsHeader .= '</mets:mets>';

        $this->modsHeader = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/"
            xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
            xmlns:foaf="http://xmlns.com/foaf/0.1/"
            xmlns:person="http://www.w3.org/ns/person#"
            xmlns:xlink="http://www.w3.org/1999/xlink"
            xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-6.xsd"
            version="3.6">';

        $this->modsHeader .= '</mods:mods>';

        $this->modsData = new \DOMDocument();
        $this->modsData->loadXML($this->modsHeader);

        $this->slubHeader = '<slub:info xmlns:slub="http://slub-dresden.de/" xmlns:foaf="http://xmlns.com/foaf/0.1/">';
        $this->slubHeader .= '</slub:info>';

        $this->slubData = new \DOMDocument();
        $this->slubData->loadXML($this->slubHeader);

        // Constructor
        $this->sxe = new \SimpleXMLElement($this->metsHeader);

        // Parser
        include_once 'xPathXMLGenerator.php';

        $this->parser = new xPathXMLGenerator();
    }

    /**
     * returns the mets xml string
     * @return string mets xml
     */
    public function getMetsData()
    {
        $xml = $this->metsData->saveXML();

        $xml = preg_replace("/eww=\"\d-\d-\d\"/", '${1}${2}${3}', $xml);

        return $xml;
        // return $this->metsData->saveXML();
    }

    /**
     * returns the mods xml string
     * @return string mods xml
     */
    public function getModsData()
    {
        return $this->modsData->saveXML();
    }

    /**
     * Build mets data structure
     * @return string mets xml
     */
    public function buildMets()
    {
        // get mods domDocument
        $modsWrap = $this->buildModsWrap();
        // get mets filesection
        $fileSection = $this->buildFileSection();
        // get mets structuremap
        $structureMap = $this->buildStructureMap();

        $xmlData = $modsWrap->firstChild->firstChild->firstChild->firstChild;

        // import mods into mets
        $nodeAppendModsData = $modsWrap->importNode($this->modsData->firstChild, true);
        $xmlData->appendChild($nodeAppendModsData);

        // add SLUB data
        $nodeAppendModsData = $modsWrap->importNode($this->buildMetsSlub()->firstChild, true);
        $modsWrap->firstChild->appendChild($nodeAppendModsData);

        if ($fileSection) {
            // add filesection
            $nodeAppendModsData = $modsWrap->importNode($fileSection->firstChild->firstChild, true);
            $modsWrap->firstChild->appendChild($nodeAppendModsData);
        }

        if ($structureMap) {
            // add structure map
            $nodeAppendModsData = $modsWrap->importNode($structureMap->firstChild->firstChild, true);
            $modsWrap->firstChild->appendChild($nodeAppendModsData);
        }

        $modsWrap->formatOutput = true;
        $modsWrap->encoding     = 'UTF-8';

        $this->metsData = $modsWrap;
    }

    /**
     * Wrapping xml with mods header
     * @param  xml $xml xml data which should be wrapped with mods
     * @return xml wrapped xml
     */
    public function wrapMods($xml)
    {
        $newXML = $this->modsHeader;

        $newXML = str_replace("</mods:mods>", $xml . "</mods:mods>", $newXML);

        return $newXML;
    }

    public function wrapSlub($xml)
    {
        $newXML = $this->slubHeader;

        $newXML = str_replace("</slub:info>", $xml . "</slub:info>", $newXML);

        return $newXML;
    }

    /**
     * build mods from form array
     * @param array $array structured form data array
     */
    public function buildModsFromForm($array)
    {
        $this->xmlData = $this->modsData;
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
                $attributeXPath .= '[@ID="QUCOSA_' . $counter . '"]';
            }

            $existsExtensionFlag = false;
            $i                   = 0;
            // loop each object
            if (!empty($values)) {
                foreach ($values as $value) {

                    if ($value['modsExtension']) {
                        $existsExtensionFlag = true;
                        // mods extension
                        $counter            = sprintf("%'03d", $this->counter);
                        $referenceAttribute = $extensionAttribute . '[@' . $group['modsExtensionReference'] . '="QUCOSA_' . $counter . '"]';

                        $path = $group['modsExtensionMapping'] . $referenceAttribute . '%/' . $value['mapping'];

                        $xml = $this->customXPath($path, false, $value['value']);
                    } else {
                        $path = $mapping . $attributeXPath . '%/' . $value['mapping'];

                        if ($i == 0) {
                            $newGroupFlag = true;
                        } else {
                            $newGroupFlag = false;
                        }

                        $xml = $this->customXPath($path, $newGroupFlag, $value['value']);
                        $i++;

                    }

                }
            } else {
                if (!empty($attributeXPath)) {
                    $path = $mapping . $attributeXPath;
                    $xml  = $this->customXPath($path, true, '', true);
                }
            }
            if (!$existsExtensionFlag && $group['modsExtensionMapping']) {
                $xPath = $group['modsExtensionMapping'] . $extensionAttribute . '[@' . $group['modsExtensionReference'] . '="QUCOSA_' . $counter . '"]';
                $xml   = $this->customXPath($xPath, true, '', true);
            }
            if ($group['modsExtensionMapping']) {
                $this->counter++;
            }
        }

        $this->modsData = $this->xmlData;
        $this->files    = $array['files'];
    }

    /**
     * get xml from xpath
     * @param  xpath $xPath xPath expression
     * @return xml
     */
    public function parseXPath($xPath)
    {
        //
        $xml = $this->parser->parse($xPath);

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

            if (!$newGroupFlag && $modsDataXPath->query('/mods:mods/' . $newPath[0])->length > 0) {
                // first xpath path exist

                // build xml from second xpath part
                $xml = $this->parseXPath($newPath[1]);

                // check if xpath [] are nested
                $search = '/(\/\w*:\w*)\[(.*)\]/';
                preg_match($search, $newPath[1], $match);
                preg_match($search, $match[2], $secondMatch);
                // first part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    $nested = $match[2]; //  $match[1].'/'

                    $nestedXml = $this->parseXPath($nested);

                    // object xpath without nested element []
                    $newPath[1] = str_replace('['.$match[2].']', '', $newPath[1]);

                    $xml = $this->parseXPath($newPath[1]);

                }
                
                $docXML = new \DOMDocument();
                $docXML->loadXML($this->wrapMods($xml));

                $domXPath = \EWW\Dpf\Helper\XPath::create($this->xmlData);

                // second part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    // import node from nested
                    $docXMLNested = new \DOMDocument();
                    $docXMLNested->loadXML($this->wrapMods($nestedXml));

                    $xPath = \EWW\Dpf\Helper\XPath::create($docXML);

                    $nodeList = $xPath->query('/mods:mods' . $match[1]);
                    $node = $nodeList->item(0);

                    $importNode = $docXML->importNode($docXMLNested->getElementsByTagName("mods")->item(0)->firstChild, true);

                    $node->appendChild($importNode);
                }

                $domNode = $domXPath->query('/mods:mods/' . $path);

                $domNodeList = $docXML->getElementsByTagName("mods");

                $node = $domNodeList->item(0)->firstChild;

                $nodeAppendModsData = $this->xmlData->importNode($node, true);
                $domNode->item($domNode->length - 1)->appendChild($nodeAppendModsData);
            } else {
                // first xpath doesn't exist
                // parse first xpath part
                $xml1 = $this->parseXPath($newPath[0]);

                $doc1 = new \DOMDocument();
                $doc1->loadXML($this->wrapMods($xml1));

                $domXPath = \EWW\Dpf\Helper\XPath::create($doc1);

                $domNode = $domXPath->query('/mods:mods/' . $path);

                // parse second xpath part
                $xml2 = $this->parseXPath($path . $newPath[1]);

                // check if xpath [] are nested
                $search = '/(\/\w*:\w*)\[(.*)\]/';
                preg_match($search, $newPath[1], $match);
                preg_match($search, $match[2], $secondMatch);
                // first part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    $nested = $match[2]; //  $match[1].'/'

                    $nestedXml = $this->parseXPath($nested);

                    // object xpath without nested element []
                    $newPath[1] = str_replace('['.$match[2].']', '', $newPath[1]);

                    $xml2 = $this->parseXPath($path . $newPath[1]);
                }

                $doc2 = new \DOMDocument();
                $doc2->loadXML($this->wrapMods($xml2));

                $domXPath2 = \EWW\Dpf\Helper\XPath::create($doc2);

                  // second part nested xpath
                if ($match[2] && $secondMatch[2]) {
                    // import node from nested
                    $docXMLNested = new \DOMDocument();
                    $docXMLNested->loadXML($this->wrapMods($nestedXml));

                    $xPath = \EWW\Dpf\Helper\XPath::create($doc2);
                    $nodeList = $xPath->query('/mods:mods/' . $path . $match[1]);
                    $node = $nodeList->item(0);

                    $importNode = $doc2->importNode($docXMLNested->getElementsByTagName("mods")->item(0)->firstChild, true);

                    $node->appendChild($importNode);
                }

                $domNode2 = $domXPath2->query('/mods:mods/' . $path)->item(0)->childNodes->item(0);

                // merge xml nodes
                $nodeToBeAppended = $doc1->importNode($domNode2, true);

                $domNode->item(0)->appendChild($nodeToBeAppended);

                // add to modsData (merge not required)
                // get mods tag
                $firstChild = $this->xmlData->firstChild;
                $firstItem  = $doc1->getElementsByTagName('mods')->item(0)->firstChild;

                $nodeAppendModsData = $this->xmlData->importNode($firstItem, true);
                $firstChild->appendChild($nodeAppendModsData);

                return $doc1->saveXML();
            }
        } else {
            // attribute only
            $xml = $this->parseXPath($xPath);

            $docXML = new \DOMDocument();
            $docXML->loadXML($this->wrapMods($xml));

            $domXPath = \EWW\Dpf\Helper\XPath::create($this->xmlData);
            $domNode  = $domXPath->query('/mods:mods');

            $domNodeList = $docXML->getElementsByTagName("mods");

            $node = $domNodeList->item(0)->firstChild;

            $nodeAppendModsData = $this->xmlData->importNode($node, true);
            $domNode->item($domNode->length - 1)->appendChild($nodeAppendModsData);

            return $docXML->saveXML();
        }

        return $this->xmlData->saveXML();
    }

    public function customXPathSlub($xPath, $newGroupFlag = false, $value = '', $attributeOnly = false)
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

            if (!$newGroupFlag && $modsDataXPath->query('/slub:info/' . $newPath[0])->length > 0) {
                // first xpath path exist

                // build xml from second xpath part
                $xml = $this->parseXPath($newPath[1]);

                $docXML = new \DOMDocument();
                $docXML->loadXML($this->wrapSlub($xml));

                $domXPath = \EWW\Dpf\Helper\XPath::create($this->xmlData);
                $domNode  = $domXPath->query('/slub:info/' . $path);

                $domNodeList = $docXML->getElementsByTagName("info");

                $node = $domNodeList->item(0)->firstChild;

                $nodeAppendModsData = $this->xmlData->importNode($node, true);
                $domNode->item($domNode->length - 1)->appendChild($nodeAppendModsData);
            } else {
                // first xpath doesn't exist
                // parse first xpath part
                $xml1 = $this->parseXPath($newPath[0]);

                $doc1 = new \DOMDocument();
                if (is_null(@$doc1->loadXML($this->wrapSlub($xml1)))) {
                    throw new \Exception("Couldn't load xml in function customXPathSlub!");
                }

                $domXPath = \EWW\Dpf\Helper\XPath::create($doc1);
                $domNode  = $domXPath->query('/slub:info/' . $path);

                // parse second xpath part
                $xml2 = $this->parseXPath($path . $newPath[1]);

                $doc2 = new \DOMDocument();
                if (is_null(@$doc2->loadXML($this->wrapSlub($xml2)))) {
                    throw new \Exception("Couldn't load xml in customXPathSlub!");
                }

                $domXPath2 = \EWW\Dpf\Helper\XPath::create($doc2);

                // node that should be appended
                $domNode2 = $domXPath2->query('/slub:info/' . $path)->item(0)->childNodes->item(0);

                // merge xml nodes
                $nodeToBeAppended = $doc1->importNode($domNode2, true);

                $domNode->item(0)->appendChild($nodeToBeAppended);

                // add to modsData (merge not required)
                // get mods tag

                $firstChild = $this->xmlData->firstChild;
                $firstItem  = $doc1->getElementsByTagName('info')->item(0)->firstChild;

                $nodeAppendModsData = $this->xmlData->importNode($firstItem, true);
                $firstChild->appendChild($nodeAppendModsData);

                return $doc1->saveXML();
            }
        } else {
            // attribute only
            $xml = $this->parseXPath($xPath);

            $docXML = new \DOMDocument();
            $docXML->loadXML($this->wrapSlub($xml));

            $domXPath = \EWW\Dpf\Helper\XPath::create($this->xmlData);
            $domNode  = $domXPath->query('/slub:info');

            $domNodeList = $docXML->getElementsByTagName("info");

            $node = $domNodeList->item(0)->firstChild;

            $nodeAppendModsData = $this->xmlData->importNode($node, true);
            $domNode->item($domNode->length - 1)->appendChild($nodeAppendModsData);

            return $docXML->saveXML();
        }
        return $this->xmlData->saveXML();
    }

    /**
     * Builds the xml wrapping part for mods
     * @return xml
     */
    public function buildModsWrap()
    {
        // Build wrap for mod

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($this->metsHeader);

        // add objid
        if (!empty($this->objId)) {
            $domDocument->documentElement->setAttribute("OBJID", $this->objId);
        }

        $domElement = $domDocument->firstChild;

        $dmdSec = $domDocument->createElement('mets:dmdSec');
        $dmdSec->setAttribute('ID', 'DMD_000');

        $domElement->appendChild($dmdSec);

        // add mdWrap element
        $mdWrap = $domDocument->createElement('mets:mdWrap');
        $mdWrap->setAttribute('MDTYPE', 'MODS');

        $domElement = $domElement->firstChild;
        $domElement->appendChild($mdWrap);

        //add xmlData element
        $xmlData = $domDocument->createElement('mets:xmlData');

        $domElement = $domElement->firstChild;
        $domElement->appendChild($xmlData);

        return $domDocument;
    }

    /**
     * Builds xml amdSection
     * @return xml
     */
    public function buildAmdSection()
    {
        // Build xml amd:sec

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($this->metsHeader);

        $domElement = $domDocument->firstChild;

        $amdSec = $domDocument->createElement('mets:amdSec');
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
                // print_r($domElement->childNodes->item(0));

                if ($value['hasFLocat']) {
                    $fLocat = $domDocument->createElement('mets:FLocat');
                    $fLocat->setAttribute('LOCTYPE', 'URL');
                    $fLocat->setAttribute('xlink:href', $value['path']);
                    $fLocat->setAttribute('xmlns:xlink', "http://www.w3.org/1999/xlink");
                    //if ($value['title']) {
                    //    $fLocat->setAttribute('xlink:title', $value['title']);
                    //}
                    $domElementFLocat->appendChild($fLocat);
                }

            }

            $i++;
        }
    }

    /**
     * Builds the xml fileSection part if files are uploaded
     * @return xml
     */
    public function buildFileSection()
    {

        // Build xml Mets:fileSec

        if (count($this->files['original']) > 0 || count($this->files['download']) > 0) {
            $domDocument = new \DOMDocument();
            $domDocument->loadXML($this->metsHeader);

            $domElement = $domDocument->firstChild;

            $fileSec = $domDocument->createElement('mets:fileSec');
            $domElement->appendChild($fileSec);

            $domElement = $domElement->firstChild;

            $fileSecElement = $domElement;

            $fileGrpOriginal = $domDocument->createElement('mets:fileGrp');
            $fileGrpOriginal->setAttribute('xmlns:mext', "http://slub-dresden.de/mets");
            $fileGrpOriginal->setAttribute('USE', 'ORIGINAL');

            // loop xml file entries
            if (!empty($this->files['original'])) {
                $this->loopFiles($this->files['original'], $fileGrpOriginal, $domDocument);
                $domElement->appendChild($fileGrpOriginal);
            }

            // switch back to filesec element
            $domElement = $fileSecElement;

            $fileGrpDownload = $domDocument->createElement('mets:fileGrp');
            $fileGrpDownload->setAttribute('xmlns:mext', "http://slub-dresden.de/mets");
            $fileGrpDownload->setAttribute('USE', 'DOWNLOAD');

            // loop xml
            if (!empty($this->files['download'])) {
                $this->loopFiles($this->files['download'], $fileGrpDownload, $domDocument);
                $domElement->appendChild($fileGrpDownload);
            }

            return $domDocument;
        }
    }

    /**
     * Builds the xml structMap part if files are uploaded
     * @return xml
     */
    public function buildStructureMap()
    {
        // Build xml Mets:structMap

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($this->metsHeader);

        $domElement = $domDocument->firstChild;

        $structMap = $domDocument->createElement('mets:structMap');
        $structMap->setAttribute('TYPE', 'LOGICAL');
        $domElement->appendChild($structMap);

        $domElement = $domElement->firstChild;

        $div = $domDocument->createElement('mets:div');
        $div->setAttribute('DMDID', 'DMD_000');
        $div->setAttribute('ID', 'DMD_000');
        $domElement->appendChild($div);

        $domElement = $domElement->firstChild;

        if (count($this->files) > 0) {
            $i = 0;

            // set xml for uploded files
            foreach ($this->files as $filesGroup) {
                foreach ($filesGroup as $key => $value) {
                    $fptr = $domDocument->createElement('mets:fptr');
                    $fptr->setAttribute('FILEID', $value['id']);
                    $domElement->appendChild($fptr);

                    $i++;
                }
            }
        }

        return $domDocument;
    }

    public function setSlubInfo($value = '')
    {
        // build DOMDocument with slub xml
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($value);
        $this->slubData = $domDocument;
    }

    /**
     * Builds the xml slubInfo part
     * @param  Array $array Array with slub information
     * @return xml        xml slubInfo
     */
    public function buildMetsSlub()
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<mets:amdSec ID="AMD_000" xmlns:mets="http://www.loc.gov/METS/"></mets:amdSec>');
        $domWrapElement = $domDocument->firstChild;

        $wrapDocumentRights = $domDocument->createElement('mets:techMD');
        $wrapDocumentRights->setAttribute('ID', 'TECH_000');

        $domWrapElement->appendChild($wrapDocumentRights);

        $domWrapElement = $domWrapElement->firstChild;

        $wrapDocumentMD = $domDocument->createElement('mets:mdWrap');
        $wrapDocumentMD->setAttribute('MDTYPE', 'OTHER');
        $wrapDocumentMD->setAttribute('OTHERMDTYPE', 'SLUBINFO');
        $wrapDocumentMD->setAttribute('MIMETYPE', 'application/vnd.slub-info+xml');

        $domWrapElement->appendChild($wrapDocumentMD);

        $domWrapElement = $domWrapElement->firstChild;

        $wrapDocumentData = $domDocument->createElement('mets:xmlData');
        $domWrapElement->appendChild($wrapDocumentData);

        $domWrapElement = $domWrapElement->firstChild;

        $second = $this->slubData;

        foreach ($second->childNodes as $node) {
            $importNode = $domDocument->importNode($node, true);
            $domWrapElement->appendChild($importNode);
        }

        return $domDocument;

    }

    /**
     * returns the mods xml string
     * @return string mods xml
     */
    public function getSlubData()
    {
        return $this->slubData->saveXML();
    }

    /**
     *
     * @param string $slubInfoData
     */
    public function buildSlubInfoFromForm($slubInfoData, $documentType, $processNumber)
    {
        $this->xmlData = $this->slubData;
        if (is_array($slubInfoData['metadata'])) {
            foreach ($slubInfoData['metadata'] as $key => $group) {
                //groups
                $mapping = $group['mapping'];
                // $mapping = substr($mapping, 10);

                $values     = $group['values'];
                $attributes = $group['attributes'];

                $attributeXPath = '';
                foreach ($attributes as $attribute) {
                    $attributeXPath .= '[' . $attribute['mapping'] . '="' . $attribute['value'] . '"]';
                }

                // mods extension
                if ($group['modsExtensionMapping']) {
                    $counter = sprintf("%'03d", $this->counter);
                    $attributeXPath .= '[@ID="QUCOSA_' . $counter . '"]';
                }

                $i = 0;
                // loop each object
                if (!empty($values)) {

                    foreach ($values as $value) {

                        if ($value['modsExtension']) {
                            // mods extension
                            $counter            = sprintf("%'03d", $this->counter);
                            $referenceAttribute = '[@' . $group['modsExtensionReference'] . '="QUCOSA_' . $counter . '"]';

                            $path = $group['modsExtensionMapping'] . $referenceAttribute . '%/' . $value['mapping'];

                            $xml = $this->customXPathSlub($path, false, $value['value']);
                        } else {
                            $path = $mapping . $attributeXPath . '%/' . $value['mapping'];
                            // print_r($path);print_r("\n");

                            if ($i == 0) {
                                $newGroupFlag = true;
                            } else {
                                $newGroupFlag = false;
                            }

                            $xml = $this->customXPathSlub($path, $newGroupFlag, $value['value']);
                            $i++;

                        }

                    }

                } else {
                    if (!empty($attributeXPath)) {
                        $path = $mapping . $attributeXPath;
                        $xml  = $this->customXPathSlub($path, true, '', true);
                    }
                }

                if ($group['modsExtensionMapping']) {
                    $this->counter++;
                }
            }
        }
        $this->slubData = $this->xmlData;

        // set document type name in slub metadata
        $domElement = $this->slubData->firstChild;
        $type       = $this->slubData->createElement('slub:documentType', $documentType->getName());
        $domElement->appendChild($type);

        // set process number in slub metadata
        $domElement = $this->slubData->firstChild;
        $pNum = $this->slubData->createElement('slub:processNumber', $processNumber);
        $domElement->appendChild($pNum);

    }

    /**
     *
     * @return string slub info xml
     */
    public function getSlubInfoData()
    {
        return $this->slubData->saveXML();
    }

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
