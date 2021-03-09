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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Services\Transformer\DocumentTransformer;

/**
 * MetsExporter
 */
class MetsExporter
{
    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
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
     * objId
     * @var string
     */
    protected $objId = '';


    /**
     * Constructor
     */
    public function __construct()
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        $this->documentTypeRepository = $objectManager->get(DocumentTypeRepository::class);

        $namespaceConfiguration = explode(";",$this->clientConfigurationManager->getNamespaces());


        foreach ($namespaceConfiguration as $key => $value) {
            $namespace = explode("=", $value);
            $this->namespaceString .= ' xmlns:' . $namespace[0] . '="' . $namespace[1] . '"';
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

    public function transformInputXML($xml) {
        $docTypeInput = $this->clientConfigurationManager->getTypeXpathInput();

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);

        $domXPath = \EWW\Dpf\Helper\XPath::create($domDocument);

        $domXPath->registerNamespace('mods', "http://www.loc.gov/mods/v3");
        $domXPath->registerNamespace('slub', "http://slub-dresden.de/");
        $domXPath->registerNamespace('foaf', "http://xmlns.com/foaf/0.1/");
        $domXPath->registerNamespace('person', "http://www.w3.org/ns/person#");
        $domXPath->registerNamespace('rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");

        $documentTypeName = $domXPath->query('//' . $docTypeInput)->item(0)->nodeValue;

        $documentType = $this->documentTypeRepository->findOneByName($documentTypeName);

        $transformationFile = $documentType->getTransformationFileInput()->current();
        if ($transformationFile != NULL) {
            $filePath = $transformationFile->getFile()->getOriginalResource()->getIdentifier();
            $documentTransformer = new DocumentTransformer();

            $transformedXml = $documentTransformer->transform(Environment::getPublicPath() . '/fileadmin' . $filePath, $xml);
        } else {
            // return generated xml if no transformation file is present
            $transformedXml = $xml;
        }

        return $transformedXml;
    }

    /**
     * @param $document
     * @return string The transformed xml
     */
    public function getTransformedOutputXML($document)
    {
        $documentType = $document->getDocumentType();
        $transformationFile = $documentType->getTransformationFileOutput()->current();
        if ($transformationFile != NULL) {
            $filePath = $transformationFile->getFile()->getOriginalResource()->getIdentifier();
            $documentTransformer = new DocumentTransformer();

            $transformedXml = $documentTransformer->transform(
                Environment::getPublicPath() . '/fileadmin' . $filePath, $this->getXMLData()
            );
        } else {
            // return generated xml if no transformation file is present
            $transformedXml = $this->getXMLData();
        }

        return $transformedXml;
    }


    /**
     * build mods from form array
     * @param array $array structured form data array
     */
    public function buildXmlFromForm($array)
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

            if (!empty($value)) {
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
                $firstChild = $this->xmlData->firstChild;
                $firstItem = $doc1->documentElement->firstChild;

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

    /**
     * sets the file data and generates file xml
     * @param string $value
     */
    public function setFileData($value = '')
    {
        $this->files = $value;
        $this->generateFileXML();
    }

    /**
     * generates the internal xml format for files
     */
    public function generateFileXML() {

        $fileXpathConfiguration = $this->clientConfigurationManager->getFileXpath();

        foreach ($this->files as $key => $fileGrp) {
            foreach ($fileGrp as $file) {

                $this->customXPath($fileXpathConfiguration . '/href', true, $file["path"]);
                $this->customXPath($fileXpathConfiguration . '%mimetype', false, $file["type"]);
                $this->customXPath($fileXpathConfiguration . '%title', false, $file["title"]);
                $this->customXPath($fileXpathConfiguration . '%download', false, $file["download"]);
                $this->customXPath($fileXpathConfiguration . '%archive', false, $file["archive"]);
                $this->customXPath($fileXpathConfiguration . '%use', false, $file["use"]);
                $this->customXPath($fileXpathConfiguration . '%id', false, $file["id"]);
                $this->customXPath($fileXpathConfiguration . '%hasFLocat', false, $file["hasFLocat"]);

            }
        }
    }

//    public function loopFiles($array, $domElement, $domDocument)
//    {
//        $i = 0;
//        // set xml for uploded files
//        foreach ($array as $key => $value) {
//            $file = $domDocument->createElement('mets:file');
//            $file->setAttribute('ID', $value['id']);
//            if ($value['use'] == 'DELETE') {
//                $file->setAttribute('USE', $value['use']);
//                $domElement->appendChild($file);
//            } else {
//                $file->setAttribute('MIMETYPE', $value['type']);
//
//                if ($value['use']) {
//                    $file->setAttribute('USE', $value['use']);
//                }
//
//                if ($value['title']) {
//                    $file->setAttribute('mext:LABEL', $value['title']);
//                }
//
//                $domElement->appendChild($file);
//                $domElementFLocat = $domElement->childNodes->item($i);
//
//                if ($value['hasFLocat']) {
//                    $fLocat = $domDocument->createElement('mets:FLocat');
//                    $fLocat->setAttribute('LOCTYPE', 'URL');
//                    $fLocat->setAttribute('xlink:href', $value['path']);
//                    $fLocat->setAttribute('xmlns:xlink', "http://www.w3.org/1999/xlink");
//                    $domElementFLocat->appendChild($fLocat);
//                }
//
//            }
//
//            $i++;
//        }
//    }

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

//    public function set_SlubInfo($value = '')
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
