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
class MetsExporter extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * formData
	 *
	 * @var array
	 */
	protected $formData = array();

	/**
	 * metsData
	 *
	 * @var  string
	 */
	protected $metsData = '';

	protected $modsData = '';

	/**
	 * metsHeader
	 * @var string
	 */
	protected $metsHeader = '';

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
		$this->buildMets();

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
		return $this->metsData;
	}


	/**
	 * Build mets data structure
	 * @return string mets xml
	 */
	public function buildMets()
	{
		// Try to build mets structure
		
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

    	// $this->metsData .= '<mets:dmdSec ID="DMD_000">
     //    					<mets:mdWrap MDTYPE="MODS">
     //        				  <mets:xmlData>';


        // Put mods xml here


        // $this->metsData .= '</mets:xmlData>
        // 					</mets:mdWrap>
    				// 		  </mets:dmdSec>';

    	// Put file sec here
    	// 
    	// 
    	

    	// Put structMap here
    	// 
    	//



    	
	}

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

	public function buildModsFromForm()
	{
		// Build xml mods from form fields
		// print_r($this->formData);

		$this->walkFormDataRecursive($this->formData);

	}

	public function parseXPath($xPath)
	{
		//
		$xml = $this->parser->parse($xPath);

		return $xml;
	}

	/**
	 * Walks the form data array recursive to build mods xml
	 * @param  array $array   Array from form
	 * @param  string $lastKey Last key from node
	 * @return [type]          [description]
	 */
	public function walkFormDataRecursive($array, $lastKey = '')
	{
		foreach ($array as $key => $value) {
			if(is_array($value)){
				$this->walkFormDataRecursive($value, $key);
			} else {
				if($lastKey != 'files'){
					// $key == metadataObjectId
					// get object
					// get xpath and try to build mods xml
					print_r($key);
					print_r($value);
				}
			}	
		}
	}


	/**
	 * [customXPath description]
	 * @param  [type] $xPath [description]
	 * @param  SimpleXMLElement $sxe   SimpleXMLElement
	 * @return [type]        [description]
	 */
	public function customXPath($xPath)
	{
		// Explode xPath
		$newPath = explode('#', $xPath);
		
		$praedicateFlag = false;
		$explodedXPath = explode('[', $newPath[0]);
		if(count($explodedXPath) > 1) {

			// praedicate is given
			$path = $explodedXPath[0]; 
			$praedicateFlag = true;

		} else {

			$path = $newPath[0];

		}

		$modsDataXPath = new \DOMXpath($this->modsData);

		if($modsDataXPath->query('/mods:mods'.$newPath[0])->length > 0) {
			
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
		// print_r($this->modsData->saveXML());
		return $this->modsData->saveXML();
		// print_r($newPath);
	}


	/**
	 * Builds the xml wrapping part for mods
	 * @return xml
	 */
	public function buildModsWrap()
	{
		// Build wrap for mods
		
		$sxe = new \SimpleXMLElement($this->metsHeader);

		$dmdSec = $sxe->addChild('dmdSec');
		$dmdSec->addAttribute('ID', 'DMD_000');

		$mdWrap = $dmdSec->addChild('mdWrap');
		$mdWrap->addAttribute('MDTYPE', 'MODS');

		$xmlData = $mdWrap->addChild('xmlData');

		return $sxe;

	}

	/**
	 * Builds the xml fileSection part
	 * @return xml
	 */
	public function buildFileSection()
	{
		// Build xml Mets:fileSec
		$sxe = new SimpleXMLElement($this->metsHeader);

		// file section
		$fileSec = $sxe->addChild('fileSec');

		$fileGrp = $fileSec->addChild('fileGrp');
		

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

			// set xml
			$file = $fileGrp->addChild('file');

			$file->addAttribute('ID', 'FILE_'.$fileId);
			$file->addAttribute('MIMETYPE', 'application/pdf');

			$FLocat = $file->addChild('FLocat');
			$FLocat->addAttribute('LOCTYPE', 'URL');
			$FLocat->addAttribute('xlink:href', $value);

			$i++;
		}		

		return $sxe[0]->asXML();
	}

	/**
	 * Builds the xml structMap part
	 * @return xml
	 */
	public function buildStructureMap()
	{
		// Build xml Mets:structMap
		
		$sxe = new SimpleXMLElement($this->metsHeader);

		$structMap = $sxe->addChild('structMap');
		$structMap->addAttribute('TYPE', 'LOGICAL');

		$div = $structMap->addChild('div');
		$div->addAttribute('DMDID', 'DMD_000');

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

			$fptr = $div->addChild('fptr');
			$fptr->addAttribute('FILEID', 'FILE_'.$fileId);

			$i++;
		}

		var_dump($sxe->asXML());

	}

	// public function addXMLTag($xPath, $name, $value)
	// {
	// 	// Add XML Tag to the defined xpath
	// 	$result = $this->sxe->xpath($xPath);
	// 	$result[0]->addChild($name, $value);
	// }


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

		var_dump($post);
	}
}