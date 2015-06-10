<?php
namespace EWW\Dpf\Services;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
           
            
    /**
     * slub xml data
     * @var DOMDocument
     */
    protected $slubData = '';

    
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
    						xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-5.xsd"
    						version="3.5">';

        $this->modsHeader .= '</mods:mods>';

        $this->modsData = new \DOMDocument();
        $this->modsData->loadXML($this->modsHeader);

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
        $nodeAppendModsData = $modsWrap->importNode($this->slubData->firstChild, true);
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
        $modsWrap->encoding = 'UTF-8';

        $this->metsData = $modsWrap;

        // print_r($modsWrap->saveXML());
        // return $modsWrap->saveXML();
    }

    /**
     * Wrapping xml with mods header
     * @param  xml $xml xml data which should be wrapped with mods
     * @return xml wrapped xml
     */
    public function wrapMods($xml)
    {
        $newXML = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/" 
            xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns" 
            xmlns:foaf="http://xmlns.com/foaf/0.1/"
            xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-5.xsd"
            version="3.5">';

        $newXML .= $xml;
        $newXML .= '</mods:mods>';

        return $newXML;
    }
                                  
    /**
     * build mods from form array
     * @param array $array structured form data array
     */
    public function buildModsFromForm($array)
    {
        // Build xml mods from form fields
        // loop each group
        foreach ($array['metadata'] as $key => $group) {
            //groups
            $mapping = $group['mapping'];
            // $mapping = substr($mapping, 10);

            $values = $group['values'];
            $attributes = $group['attributes'];

            $attributeXPath = '';
            foreach ($attributes as $attribute) {
                $attributeXPath .= '['.$attribute['mapping'].'="'.$attribute['value'].'"]';
            }

            // mods extension
            if ($group['modsExtensionMapping']) {
                $counter = sprintf("%'03d", $this->counter);
                $attributeXPath .= '[@ID="QUCOSA_'.$counter.'"]';
            }
            
            $i = 0;
            // loop each object
            foreach ($values as $value) {

                if ($value['modsExtension']) {
                    // mods extension
                    $counter = sprintf("%'03d", $this->counter);
                    $attributeXPath = '[@'.$group['modsExtensionReference'].'="#QUCOSA_'.$counter.'"]';

                    $path = $group['modsExtensionMapping'].$attributeXPath.'%/'.$value['mapping'];

                    $xml = $this->customXPath($path, true, $value['value']);
                } else {
                    $path = $mapping.$attributeXPath.'%/'.$value['mapping'];
                    // print_r($path);print_r("\n");

                    if ($i == 0) {
                        $newGroupFlag = true;
                    } else {
                        $newGroupFlag = false;
                    }

                    $xml = $this->customXPath($path, $newGroupFlag, $value['value']);
                    $i++;

                }
                
            }
            if ($group['modsExtensionMapping']) {
                $this->counter++;
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
    public function customXPath($xPath, $newGroupFlag = false, $value = '')
    {

        // Explode xPath
        $newPath = explode('%', $xPath);

        $praedicateFlag = false;
        $explodedXPath = explode('[', $newPath[0]);
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
            $newPath[1] = $newPath[1].'="'.$value.'"';
        }

        $modsDataXPath = new \DOMXpath($this->modsData);

        if (!$newGroupFlag && $modsDataXPath->query('/mods:mods/'.$newPath[0])->length > 0) {
            // first xpath path exist

            // build xml from second xpath part
            $xml = $this->parseXPath($newPath[1]);

            $docXML = new \DOMDocument();
            $docXML->loadXML($this->wrapMods($xml));

            $domXPath = new \DOMXpath($this->modsData);
            $domNode = $domXPath->query('/mods:mods/'.$path);

            $domNodeList = $docXML->getElementsByTagName("mods");

            $node = $domNodeList->item(0)->firstChild;

            $nodeAppendModsData = $this->modsData->importNode($node, true);
            $domNode->item($domNode->length-1)->appendChild($nodeAppendModsData);
        } else {
            // first xpath doesnt exist
            // parse first xpath part
            $xml1 = $this->parseXPath($newPath[0]);

            $doc1 = new \DOMDocument();
            $doc1->loadXML($this->wrapMods($xml1));

            $domXPath = new \DOMXpath($doc1);
            $domNode = $domXPath->query('/mods:mods/'.$path);

            // parse second xpath part
            $xml2 = $this->parseXPath($path.$newPath[1]);

            $doc2 = new \DOMDocument();
            $doc2->loadXML($this->wrapMods($xml2));

            $domXPath2 = new \DOMXpath($doc2);
            $domNode2 = $domXPath2->query('/mods:mods/'.$path)->item(0)->childNodes->item(0);

            // $node = $doc2->getElementsByTagName("name")->item(0)->childNodes->item(0); //DOMNode

            // merge xml nodes
            $nodeToBeAppended = $doc1->importNode($domNode2, true);
            // $doc1->documentElement->appendChild($nodeToBeAppended);
            $domNode->item(0)->appendChild($nodeToBeAppended);

            // add to modsData (merge not required)
            // get mods tag
            $firstChild = $this->modsData->firstChild;
            $firstItem = $doc1->getElementsByTagName('mods')->item(0)->firstChild;

            $nodeAppendModsData = $this->modsData->importNode($firstItem, true);
            $firstChild->appendChild($nodeAppendModsData);

            return $doc1->saveXML();
        }

        return $this->modsData->saveXML();
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
        $domDocument->loadXML($value);
        $this->modsData = $domDocument;
    }

    // public function slubInfo($value = '')
    // {
    //     # NOT IMPLEMENTED YET
        
    //     $slub = '<mets:amdSec ID="AMD_000">
    //     <mets:rightsMD ID="RIGHTS_000">
    //     <mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="SLUBRIGHTS" MIMETYPE="application/vnd.slub-info+xml">
    //     <mets:xmlData>
    //     <slub:info xmlns:slub="http://slub-dresden.de/">
    //       <slub:documentType>'.$value['documentType'].'</slub:documentType>
    //       <slub:submitter>
    //         <slub:name>Frau Administrator</slub:name>
    //         <slub:contact>mailto:qucosa.admin@slub-dresden.de</slub:contact>
    //       </slub:submitter>

    //       <slub:project>OpenAire Project Name</slub:project>
    //       <slub:client>Mandant</slub:client>
    //       <slub:rights>
    //         <slub:license valueURI="URI der Lizenz">Lizenzangabe</slub:license>
    //         <slub:embargo encoding="iso8601">2014-11-26</slub:embargo>
    //         <slub:accessDNB>true</slub:accessDNB>
    //         <slub:accessPOD>true</slub:accessPOD>
    //       </slub:rights>
    //     </slub:info>
    //     </mets:xmlData>
    //     </mets:mdWrap>
    //     </mets:rightsMD>
    //     </mets:amdSec>';
                  
    //     $domDocument = new \DOMDocument();
    //     $domDocument->loadXML($slub);
    //     $this->slubData = $domDocument;
    // }

    public function setFileData($value = '')
    {
        $this->files = $value;
    }

    /**
     * Builds the xml fileSection part if files are uploaded
     * @return xml
     */
    public function buildFileSection()
    {
        // Build xml Mets:fileSec

        if (count($this->files) > 0) {
            $domDocument = new \DOMDocument();
            $domDocument->loadXML($this->metsHeader);

            $domElement = $domDocument->firstChild;

            $fileSec = $domDocument->createElement('mets:fileSec');
            $domElement->appendChild($fileSec);

            $domElement = $domElement->firstChild;

            $fileGrp = $domDocument->createElement('mets:fileGrp');
            $fileGrp->setAttribute('USE', 'ORIGINAL');
            $domElement->appendChild($fileGrp);

            $domElement = $domElement->firstChild;

            $i = 0;
            // set xml for uploded files
            foreach ($this->files as $key => $value) {
                $file = $domDocument->createElement('mets:file');
                $file->setAttribute('ID', $value['id']);
                if ($value['use']) {
                    $file->setAttribute('USE', $value['use']);
                    $domElement->appendChild($file);
                } else {
                    $file->setAttribute('MIMETYPE', $value['type']);
                                                             
                    $domElement->appendChild($file);
                    $domElementFLocat = $domElement->childNodes->item($i);
                    // print_r($domElement->childNodes->item(0));

                    $fLocat = $domDocument->createElement('mets:FLocat');
                    $fLocat->setAttribute('LOCTYPE', 'URL');
                    $fLocat->setAttribute('xlink:href', $value['path']);
                    $fLocat->setAttribute('xmlns:xlin', "http://www.w3.org/1999/xlink");
                    if ($value['title']) {
                        $fLocat->setAttribute('xlin:title', $value['title']);
                    }

                    $domElementFLocat->appendChild($fLocat);
                }
                                             
                $i++;
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
        if (count($this->files) > 0) {
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

            $i = 0;
            // set xml for uploded files
            foreach ($this->files as $key => $value) {
                $fptr = $domDocument->createElement('mets:fptr');
                $fptr->setAttribute('FILEID', $value['id']);
                $domElement->appendChild($fptr);

                $i++;
            }

            return $domDocument;
        }
    }

    /**
     * Builds the xml slubInfo part
     * @param  Array $array Array with slub information
     * @return xml        xml slubInfo
     */
    public function setSlubInfo($array)
    {
        // <mets:amdSec ID="AMD_000"><mets:rightsMD ID="RIGHTS_000"><mets:mdWrap MDTYPE="OTHER" OTHERMDTYPE="SLUBRIGHTS" MIMETYPE="application/vnd.slub-info+xml"><mets:xmlData>
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<mets:amdSec ID="AMD_000"></mets:amdSec>');

        $domWrapElement = $domDocument->firstChild;

        $wrapDocumentRights = $domDocument->createElement('mets:rightsMD');
        $wrapDocumentRights->setAttribute('ID', 'RIGHTS_000');
                       
        $domWrapElement->appendChild($wrapDocumentRights);

        $domWrapElement = $domWrapElement->firstChild;
        
        $wrapDocumentMD = $domDocument->createElement('mets:mdWrap');
        $wrapDocumentMD->setAttribute('MDTYPE', 'OTHER');
        $wrapDocumentMD->setAttribute('OTHERMDTYPE', 'SLUBRIGHTS');
        $wrapDocumentMD->setAttribute('MIMETYPE', 'application/vnd.slub-info+xml');

        $domWrapElement->appendChild($wrapDocumentMD);
        
        $domWrapElement = $domWrapElement->firstChild;
        
        $wrapDocumentData = $domDocument->createElement('mets:xmlData');
        $domWrapElement->appendChild($wrapDocumentData);

        $domWrapElement = $domWrapElement->firstChild;


        // $domDocument = new \DOMDocument();
        // $domDocument->loadXML('<slub:info xmlns:slub="http://slub-dresden.de/></slub:info>');
         
        $domSlub = $domDocument->createElement('slub:info');
        $domSlub->setAttribute('xmlns:slub', 'http://slub-dresden.de/');

        $domWrapElement->appendChild($domSlub);
        
        $domWrapElement = $domWrapElement->firstChild;
        
        $domElement = $domWrapElement;

        $submitter = $domDocument->createElement('slub:submitter');
        $domElement->appendChild($submitter);
        
        $documentType = $domDocument->createElement('slub:documentType', $array['documentType']);
        $domElement->appendChild($documentType);
                
        $project = $domDocument->createElement('slub:project', $array['project']);
        $domElement->appendChild($project);

        $client = $domDocument->createElement('slub:client', $array['client']);
        $domElement->appendChild($client);

        $rights = $domDocument->createElement('slub:rights');
        $domElement->appendChild($rights);

        $domElementRights = $domElement->lastChild;


        // submitter second level
        $domElement = $domElement->firstChild;

        $name = $domDocument->createElement('slub:name', $array['name']);
        $domElement->appendChild($name);

        $contact = $domDocument->createElement('slub:contact', $array['contact']);
        $domElement->appendChild($contact);


        //rights second level
        $domElement = $domElementRights;

        $license = $domDocument->createElement('slub:license');
        $license->setAttribute('valueURI', $array['']);
        $domElement->appendChild($license);

        $embargo = $domDocument->createElement('slub:embargo', $array['embargo']);
        $embargo->setAttribute('encoding', 'iso8601');
        $domElement->appendChild($embargo);

        $accessDNB = $domDocument->createElement('slub:accessDNB', $array['accessDNB']);
        $domElement->appendChild($accessDNB);

        $accessPOD = $domDocument->createElement('slub:accessPOD', $array['accessPOD']);
        $domElement->appendChild($accessPOD);


        // append wrapped element
        // $domWrapElement->appendChild($domDocument);

        $this->slubData = $domDocument;

        // return $domDocument;

    }


    /**
     * returns the mods xml string
     * @return string mods xml
     */
    public function getSlubData()
    {
        return $this->slubData->saveXML();
    }
}
