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
class MetsExporter {

	/**
	 * formData
	 *
	 * @var array
	 */
	protected $formData = array();

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
	protected $sxe = NULL;

	/**
	 * xPathXMLGenerator
	 * @var object
	 */
	protected $parser = NULL;

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
		include_once('xPathXMLGenerator.php');

		$this->parser = new xPathXMLGenerator();
	}

	/**
	 * returns the mets xml string
	 * @return string mets xml
	 */
	public function getMetsData()
	{
		return $this->metsData->saveXML();
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

		// add filesection
		$nodeAppendModsData = $modsWrap->importNode($fileSection->firstChild->firstChild, true);
		$modsWrap->firstChild->appendChild($nodeAppendModsData);

		// add structure map
		$nodeAppendModsData = $modsWrap->importNode($structureMap->firstChild->firstChild, true);
		$modsWrap->firstChild->appendChild($nodeAppendModsData);

		$modsWrap->formatOutput = true;
		$modsWrap->encoding = 'UTF-8';

		// print_r($modsWrap->saveXML());
		return $modsWrap->saveXML();
		

    	
	}

	/**
	 * Wrapping xml with mods header
	 * @param  [type] $xml [description]
	 * @return [type]      [description]
	 */
	public function wrapMods($xml)
	{
		$newXML = '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:mods="http://www.loc.gov/mods/v3" xmlns:slub="http://slub-dresden.de/"
    xsi:schemaLocation="http://www.loc.gov/mods/v3 http://www.loc.gov/standards/mods/v3/mods-3-5.xsd"
    version="3.5">';

    	$newXML .= $xml;
    	$newXML .= '</mods:mods>';

    	return $newXML;
	}
        
        /**
         * 
         * @param array $array
         */
	public function buildModsFromForm($array)
	{
		// Build xml mods from form fields
		// print_r($this->formData);

		// $this->walkFormDataRecursive($array);

		foreach($array['metadata'] as $key => $group) {
			//groups

			$mapping = $group['mapping'];
			$mapping = substr($mapping, 10);

			$values = $group['values'];
			$attributes = $group['attributes'];

			$attributeXPath = '';
			foreach($attributes as $attribute) {
				$attributeXPath .= '['.$attribute['mapping'].'="'.$attribute['value'].'"]';
			}

			foreach($values as $value){
				$path = $mapping.$attributeXPath.'#/'.$value['mapping'];
				// print_r($path);print_r("\n");
				$xml = $this->customXPath($path, $value['value']);
			}

		}


	}

	public function parseXPath($xPath)
	{
		//
		$xml = $this->parser->parse($xPath);

		return $xml;
	}


	/**
	 * Customized xPath parser
	 * @param  [type] $xPath [description]
	 * @param  SimpleXMLElement $sxe   SimpleXMLElement
	 * @return [type]        [description]
	 */
	public function customXPath($xPath, $value = '')
	{
		// Explode xPath
		$newPath = explode('#', $xPath);
		
		$praedicateFlag = false;
		$explodedXPath = explode('[', $newPath[0]);
		if(count($explodedXPath) > 1) {

			// praedicate is given
			// $path = $explodedXPath[0]; 
			// $path = $newPath[0]; // TODO unterscheidung attribut und pfad innerhalb eines prädikats
			
			if(substr($explodedXPath[1], 0, 1) == "@" ) {
				$path = $newPath[0];
			} else {
				$path = $explodedXPath[0];
			}

			$praedicateFlag = true;

		} else {

			$path = $newPath[0];

		}

		if(!empty($value)) {
			$newPath[1] = $newPath[1].'="'.$value.'"';
		}

		$modsDataXPath = new \DOMXpath($this->modsData);

		if($modsDataXPath->query('/mods:mods'.$newPath[0])->length > 0) {
			// first xpath path exist

			// build xml from second xpath part
			$xml = $this->parseXPath($newPath[1]);

			$docXML = new \DOMDocument();
			$docXML->loadXML($this->wrapMods($xml));

			$domXPath = new \DOMXpath($this->modsData);
			$domNode = $domXPath->query('/mods:mods'.$path);

			$node = $docXML->getElementsByTagName("mods")->item(0)->firstChild;

			$nodeAppendModsData = $this->modsData->importNode($node, true);
			$domNode->item(0)->appendChild($nodeAppendModsData);


		} else {
			// first xpath doesnt exist
			// parse first xpath part
			$xml1 = $this->parseXPath($newPath[0]);


			$doc1 = new \DOMDocument();
			$doc1->loadXML($this->wrapMods($xml1));

			$domXPath = new \DOMXpath($doc1);
			$domNode = $domXPath->query('/mods:mods'.$path);


			// parse second xpath part
			$xml2 = $this->parseXPath($path.$newPath[1]);

			$doc2 = new \DOMDocument();
			$doc2->loadXML($this->wrapMods($xml2));

			$domXPath2 = new \DOMXpath($doc2);
			$domNode2 = $domXPath2->query('/mods:mods'.$path)->item(0)->childNodes->item(0);			

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

		$dmdSec = $domDocument->createElement('mets:dmdSec', '');
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
	 * Builds the xml fileSection part
	 * @return xml
	 */
	public function buildFileSection()
	{
		// Build xml Mets:fileSec
		
		$domDocument = new \DOMDocument();
		$domDocument->loadXML($this->metsHeader);

		$domElement = $domDocument->firstChild;

		$fileSec = $domDocument->createElement('mets:fileSec');
		$domElement->appendChild($fileSec);

		$domElement = $domElement->firstChild;

		$fileGrp = $domDocument->createElement('mets:fileGrp');
		$domElement->appendChild($fileGrp);

		$domElement = $domElement->firstChild;

		$i = 0;
		// set xml for uploded files
		foreach ($this->formData[0]['files'] as $key => $value) {
			// convert counter to string (FILE_000)
			$counter = (string) $i;
			
			if(strlen($counter) == 1){

				$fileId = '00'.$counter;

			} else if(strlen($counter) == 2){

				$fileId = '0'.$counter;

			}

			$file = $domDocument->createElement('mets:file');
			$file->setAttribute('ID', 'FILE_'.$fileId);
			$file->setAttribute('MIMETYPE', 'application/pdf');
			$domElement->appendChild($file);

			$domElementFLocat = $domElement->childNodes->item($i);
			// print_r($domElement->childNodes->item(0));

			$fLocat = $domDocument->createElement('mets:FLocat');
			$fLocat->setAttribute('LOCTYPE', 'URL');
			$fLocat->setAttribute('xlink:href', $value);
			$domElementFLocat->appendChild($fLocat);


			$i++;
		}


		return $domDocument;

	}

	/**
	 * Builds the xml structMap part
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
		$domElement->appendChild($div);

		$domElement = $domElement->firstChild;

		$i = 0;
		// set xml for uploded files
		foreach ($this->formData[0]['files'] as $key => $value) {
			// convert counter to string (FILE_000)
			$counter = (string) $i;
			
			if(strlen($counter) == 1){

				$fileId = '00'.$counter;

			} else if(strlen($counter) == 2){

				$fileId = '0'.$counter;

			}
			
			$fptr = $domDocument->createElement('mets:fptr');
			$fptr->setAttribute('FILEID', 'FILE_'.$fileId);
			$domElement->appendChild($fptr);

			$i++;
		}

		return $domDocument;


	}

	public function buildTestDataArray()
	{
		// build test data array
		
		$post = array();

		// title example
		$fields['12'] = 'TitelXY';
		$fields['13'] = 'SubTitelXY';
		$group['titleInfo'] = $fields;


		$fields2['14'] = 'ger';
		$group['language'] = $fields2;


		$roleTerm['15'] = 'author';
		$role['role'] = $roleTerm;
		$name['namePart'] = $role;
		$group['name'] = $name;

		// files
		$files[] = 'test.pdf';
		$files[] = 'document.pdf';
		$files[] = 'tof.pdf';
		$group['files'] = $files;


		$post['0'] = $group;

		$this->formData = $post;

		//var_dump($post);
	}

}