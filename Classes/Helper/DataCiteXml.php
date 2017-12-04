<?php
namespace EWW\Dpf\Helper;

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
 
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataCiteXml
{

    /**
     * Generates DataCite.xml from a given METS.xml
     *
     * @param string $metsXml
     * @return string $dataCiteXml
     */
    public static function convertFromMetsXml($metsXml)
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
