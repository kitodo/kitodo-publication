<?php
namespace EWW\Dpf\Controller;

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
 * API to return METS dissemination and Attachments from Fedora.
 * Also renders METS XML for preview. Structure of the URIs totally
 * depend on proper RealURL configuration.
 *
 * Example:
 *
 * 1. METS from Fedora
 *   http://localhost/api/qucosa:1234/mets/
 *
 *   This always returns METS which is supplemented with additional information.
 *   The embedded MODS record is not the original MODS as it is stored in the
 *   repository datastream.
 *
 * 2. Attachment from Fedora
 *   http://localhost/api/qucosa:1234/attachment/ATT-0/
 *
 * 3. METS from Kitodo.Publication (this extension)
 *   http://localhost/api/3/preview/
 *
 * 4. DataCite from Kitodo.Publication (this extension)
 *
 * @author    Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author    Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author    Florian Rügamer <florian.ruegamer@slub-dresden.de>
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * GetFileController
 */
class GetFileController extends \EWW\Dpf\Controller\AbstractController
{

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository;

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @inject
     */
    protected $clientConfigurationManager;

    public function attachmentAction()
    {

        $piVars = GeneralUtility::_GP('tx_dpf'); // get GET params from powermail

        $fedoraHost = $this->clientConfigurationManager->getFedoraHost();

        switch ($piVars['action']) {
            case 'mets':
                $path = rtrim('http://' . $fedoraHost,"/").'/fedora/objects/'.$piVars['qid'].'/methods/qucosa:SDef/getMETSDissemination?supplement=yes';
                break;

            case 'preview':

                $document = $this->documentRepository->findByUid($piVars['qid']);

                if ($document) {

                    $metsXml = $this->buildMetsXml($document);
                    $this->response->setHeader('Content-Type', 'text/xml; charset=UTF-8');
                    return $metsXml;

                } else {

                    $this->response->setStatus(404);
                    return 'No such document';

                }

            case 'attachment':

                $qid = $piVars['qid'];

                $attachment = $piVars['attachment'];

                if (is_numeric($piVars['qid'])) {

                    // qid is local uid
                    $document = $this->documentRepository->findByUid($piVars['qid']);

                    $files = $document->getCurrentFileData();

                    foreach ($files['download'] as $id => $file) {

                        if ($file['id'] == $attachment) {

                            $path = $file['path'];

                            $contentType = $file['type'];

                            break;

                        }
                    }

                } else {

                    $path = rtrim('http://' . $fedoraHost, "/") . '/fedora/objects/' . $qid . '/datastreams/' . $attachment . '/content';

                }

                if (empty($path)) {
                    $this->response->setStatus(404);
                    return 'No file found';
                }

                break;

            case 'dataCite':

                $qid = $piVars['qid'];
                $source = explode(':', $qid);
                if($source[0] == 'qucosa') {

                    $path = rtrim('http://' . $fedoraHost,"/").'/fedora/objects/'.$piVars['qid'].'/methods/qucosa:SDef/getMETSDissemination?supplement=yes';
                    $metsXml = str_replace('&', '&amp;', file_get_contents($path));
                    $dataCiteXml = $this->buildDataCiteXml($metsXml);

                } elseif($document = $this->documentRepository->findByUid($piVars['qid'])) {

                    $metsXml = str_replace('&', '&amp;', $this->buildMetsXml($document));
                    $dataCiteXml = $this->buildDataCiteXml($metsXml);

                } else {

                    $this->response->setStatus(404);
                    return 'No such document';

                }
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->loadXML($dataCiteXml);
                $title = $dom->getElementsByTagName('title')[0];

                $this->response->setHeader('Content-Disposition', 'attachment; filename="' . self::sanitizeFilename($title->nodeValue) . '.DataCite.xml"');
                $this->response->setHeader('Content-Type', 'text/xml; charset=UTF-8');
                return $dataCiteXml;

                break;

            default:

                $this->response->setStatus(404);

                return 'No such action';
        }

        // get remote header and set it before passtrough
        $headers = get_headers($path);

        if (FALSE === $headers) {
            $this->response->setStatus(500);
            return 'Error while fetching headers';
        }

        $contentDispFlag = false;
        $contentTypeFlag = false;

        foreach ($headers as $value) {

            if (FALSE !== stripos($value, 'Content-Disposition')) {
                header($value);
                $contentDispFlag = true;
                continue;
            }

            if (FALSE !== stripos($value, 'Content-Type')) {
                header($value);
                $contentTypeFlag = true;
                continue;
            }

            if (FALSE !== stripos($value, 'Content-Length')) {
                header($value);
                continue;
            }
        }

        if (!$contentDispFlag) {
            header('Content-Disposition: attachment');
        }

        if (!$contentTypeFlag) {
            header('Content-Type: ' . $contentType);
        }

        if ($stream = fopen($path, 'r')) {

            // close active session if any
            session_write_close();

            // stop output buffering
            ob_end_clean();

            fpassthru($stream);

            fclose($stream);

            // Hard exit PHP script to avoid sending TYPO3 framework HTTP artifacts
            exit;

        } else {
            $this->response->setStatus(500);
            return 'Error while opening stream';
        }

    }

    private static function sanitizeFilename($filename)
    {
        // remove anything which isn't a word, whitespace, number or any of the following caracters -_~,;[]().
        $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
        // turn diacritical characters to ASCII
        setlocale(LC_ALL, 'en_US.utf8');
        $filename = iconv('utf-8', 'us-ascii//TRANSLIT', trim($filename));
        // replace whitespaces with underscore
        $filename = preg_replace('/\s+/', '_', $filename);

        return $filename;
    }

    private function buildMetsXml($document)
    {

        $exporter = new \EWW\Dpf\Services\MetsExporter();
        $fileData = $document->getCurrentFileData();
        $exporter->setFileData($fileData);
        $exporter->setMods($document->getXmlData());
        $exporter->setSlubInfo($document->getSlubInfoData());

        if (empty($document->getObjectIdentifier())) {

            $exporter->setObjId($document->getUid());

        } else {

            $exporter->setObjId($document->getObjectIdentifier());

        }

        $exporter->buildMets();
        $metsXml = $exporter->getMetsData();

        return $metsXml;
    }

    private function buildDataCiteXml($metsXml)
    {

        $metsXml = simplexml_load_string($metsXml, NULL, NULL, "http://www.w3.org/2001/XMLSchema-instance");
        $metsXml->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
        $metsXml->registerXPathNamespace('slub', 'http://slub-dresden.de/');

        // doi
        $metsDoi = $metsXml->xpath("//mods:identifier[@type='qucosa:doi']");
        if(!empty($metsDoi)) {
            $dataCiteDoi = $metsDoi[0];
        } else {
            $dataCiteDoi = '10.1000/1'; // http://www.doi.org/index.html as default
        }

        // creators
        $metsCreator = $metsXml->xpath("//mods:name[@type='personal']");
        $dataCiteCreator = array();
        foreach($metsCreator as $creator)
        {
            $creator->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
            $names       = array();
            $givenName   = $creator->xpath(".//mods:namePart[@type='given']");
            $familyName  = $creator->xpath(".//mods:namePart[@type='family']");
            $creatorName = $creator->xpath(".//mods:namePart[@type='displayForm']");
            if(empty($creatorName)) {
                if(!empty($givenName) && !empty($familyName)) {
                    $creatorName = "<creatorName>{$familyName[0]}, {$givenName[0]}</creatorName>";
                } else {
                    $creatorName = "";
                }
            } else {
                $creatorName = "<creatorName>{$creatorName[0]}</creatorName>";
            }
            $givenName  = (!empty($givenName)) ? "<givenName>{$givenName[0]}</givenName>" : "";
            $familyName = (!empty($familyName)) ? "<familyName>{$familyName[0]}</familyName>" : "";
            array_push($names, $creatorName, $givenName, $familyName);
            $names = implode("", $names);
            array_push($dataCiteCreator, "<creator>{$names}</creator>");
        };
        $dataCiteCreator = implode('', array_unique($dataCiteCreator));

        // title
        $metsTitle = $metsXml->xpath("//mods:titleInfo[@usage='primary']/mods:title");
        $dataCiteTitle = (!empty($metsTitle)) ? "<title>{$metsTitle[0]}</title>" : "";

        // subtitles
        $metsSubTitles = $metsXml->xpath("//mods:titleInfo[@usage='primary']/mods:subTitle");
        foreach($metsSubTitles as $title) {
            $dataCiteTitle .= (!empty($title)) ? "<title titleType=\"Subtitle\">{$title}</title>" : "";
        }

        // publisher
        $metsPublisher = $metsXml->xpath("//mods:name[@type='corporate'][@displayLabel='mapping-hack-other']/mods:namePart");
        $dataCitePublisher = (!empty($metsPublisher)) ? $metsPublisher[0] : "";

        // publication year
        $metsPublicationYear = $metsXml->xpath("//mods:originInfo[@eventType='publication']/mods:dateIssued");
        if(!empty($metsPublicationYear)) {
            $dataCitePublicationYear = $metsPublicationYear[0];
        } else {
            $metsPublicationYear = $metsXml->xpath("//mods:originInfo/mods:dateIssued");
            $dataCitePublicationYear = (!empty($metsPublicationYear)) ? $metsPublicationYear[0] : "";
        }
        if(strlen($dataCitePublicationYear) != 4) {
            $dataCitePublicationYear = substr($dataCitePublicationYear, 0, 4);
        }

        // subjects
        $metsSubjects = $metsXml->xpath("//mods:classification[@authority='z']");
        $dataCiteSubjects = '';
        foreach(GeneralUtility::trimExplode(',', $metsSubjects[0]) as $subject) {
            $dataCiteSubjects .= "<subject>{$subject}</subject>";
        }

        // language
        $metsLanguage = $metsXml->xpath("//mods:language/mods:languageTerm[@authority='iso639-2b'][@type='code']");
		$dataCiteLanguage = \EWW\Dpf\Helper\LanguageCode::convertFrom6392Bto6391($metsLanguage[0]);

        // description
        $metsDescription = $metsXml->xpath("//mods:abstract[@type='summary']");
        $dataCiteDescription = (!empty($metsDescription)) ? "<description descriptionType=\"Abstract\">{$metsDescription[0]}</description>" : "";

        // resource type
        $slubResourceType = $metsXml->xpath("//slub:documentType");
        $dataCiteResourceType = (!empty($slubResourceType)) ? $slubResourceType[0] : "";

        $xml = simplexml_load_string(<<< XML
<?xml version="1.0" encoding="UTF-8"?>
<resource xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://datacite.org/schema/kernel-4" xsi:schemaLocation="http://datacite.org/schema/kernel-4 http://schema.datacite.org/meta/kernel-4/metadata.xsd">
    <identifier identifierType="DOI">{$dataCiteDoi}</identifier>
    <creators>{$dataCiteCreator}</creators>
    <titles>{$dataCiteTitle}</titles>
    <publisher>{$dataCitePublisher}</publisher>
    <publicationYear>{$dataCitePublicationYear}</publicationYear>
    <subjects>{$dataCiteSubjects}</subjects>
    <language>{$dataCiteLanguage}</language>
    <descriptions>{$dataCiteDescription}</descriptions>
    <resourceType resourceTypeGeneral="Text">{$dataCiteResourceType}</resourceType>
</resource>
XML
        );

        $dataCiteXml = new \DOMDocument('1.0', 'UTF-8');
        $dataCiteXml->preserveWhiteSpace = false;
        $dataCiteXml->formatOutput = true;
        $dataCiteXml->loadXML($xml->asXML());

        return($dataCiteXml->saveXML());
    }
}

