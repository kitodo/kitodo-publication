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

class DataCiteXml
{
    /**
     * Maps tx_dpf_domain_model_documenttype.name to the DataCite
     * resourceTypeGeneral controlled vocabulary (DataCite Metadata
     * Schema 4.x). Unknown/unmapped document types fall back to "Text".
     *
     * @var array
     */
    private static $resourceTypeGeneralMap = [
        'text' => 'Text',
        'preprint' => 'Preprint',
        'proceeding' => 'ConferenceProceeding',
        'report' => 'Report',
        'article' => 'JournalArticle',
        'in_proceeding' => 'ConferencePaper',
        'contained_work' => 'BookChapter',
        'monograph' => 'Book',
        'periodical' => 'Journal',
        'doctoral_thesis' => 'Dissertation',
        'habilitation_thesis' => 'Dissertation',
        'master_thesis' => 'Dissertation',
        'bachelor_thesis' => 'Dissertation',
        'diploma_thesis' => 'Dissertation',
        'magister_thesis' => 'Dissertation',
        'issue' => 'Collection',
        'series' => 'Collection',
        'multivolume_work' => 'Collection',
        'musical_notation' => 'Text',
        'lecture' => 'Text',
        'paper' => 'Text',
        'research_paper' => 'Text',
        'conferencePoster' => 'Text',
    ];

    /**
     * Contributor roles (MARC relator code => DataCite contributorType).
     * Names carrying any other role code are treated as creators or ignored.
     *
     * @var array
     */
    private static $contributorRoleMap = [
        'edt' => 'Editor',
        'ctb' => 'Other',
        'ths' => 'Supervisor',
    ];

    /**
     * MARC relator codes for corporate names that qualify as creator when no
     * personal author exists. Provider/repository roles (e.g. "prv") are
     * deliberately excluded -- hosting a record doesn't make it its creator.
     *
     * @var string[]
     */
    private static $corporateCreatorRoles = ['aut', 'cmp', 'edt', 'cre'];

    private static $relatedItemRelationMap = [
        'host' => 'IsPartOf',
        'series' => 'IsPartOf',
        'otherVersion' => 'IsVersionOf',
    ];

    private static $identifierTypeMap = [
        'doi' => 'DOI',
        'urn' => 'URN',
        'qucosa:urn' => 'URN',
        'isbn' => 'ISBN',
        'issn' => 'ISSN',
    ];

    /**
     * Generates DataCite.xml from a given METS.xml
     *
     * @param string $metsXml
     * @return string $dataCiteXml
     */
    public static function convertFromMetsXml($metsXml)
    {
        $mets = new \DOMDocument('1.0', 'UTF-8');
        XPath::loadXml($mets, $metsXml);
        $xpath = XPath::create($mets);

        $dataCiteXml = new \DOMDocument('1.0', 'UTF-8');
        $dataCiteXml->preserveWhiteSpace = false;
        $dataCiteXml->formatOutput = true;

        $resource = $dataCiteXml->createElementNS('http://datacite.org/schema/kernel-4', 'resource');
        $resource->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:xsi',
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $resource->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://datacite.org/schema/kernel-4 http://schema.datacite.org/meta/kernel-4/metadata.xsd'
        );
        $dataCiteXml->appendChild($resource);

        self::appendIdentifier($dataCiteXml, $resource, $xpath);
        $creatorNodes = self::appendCreators($dataCiteXml, $resource, $xpath);
        self::appendTitles($dataCiteXml, $resource, $xpath);
        self::appendPublisher($dataCiteXml, $resource, $xpath);
        self::appendPublicationYear($dataCiteXml, $resource, $xpath);
        self::appendSubjects($dataCiteXml, $resource, $xpath);
        self::appendContributors($dataCiteXml, $resource, $xpath, $creatorNodes);
        self::appendDates($dataCiteXml, $resource, $xpath);
        self::appendLanguage($dataCiteXml, $resource, $xpath);
        self::appendResourceType($dataCiteXml, $resource, $xpath);
        self::appendAlternateIdentifiers($dataCiteXml, $resource, $xpath);
        self::appendRelatedIdentifiers($dataCiteXml, $resource, $xpath);
        self::appendDescriptions($dataCiteXml, $resource, $xpath);

        return $dataCiteXml->saveXML();
    }

    private static function appendTextElement(
        \DOMDocument $dom,
        \DOMElement $parent,
        string $name,
        string $text,
        array $attributes = []
    ): \DOMElement {
        $element = $dom->createElement($name, self::escapeText($text));
        foreach ($attributes as $attribute => $value) {
            $element->setAttribute($attribute, $value);
        }
        $parent->appendChild($element);
        return $element;
    }

    /**
     * createElement() interprets its second argument as XML markup, so any
     * text going through it must be pre-escaped for the reserved characters.
     */
    private static function escapeText(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function queryText(\DOMXPath $xpath, string $expression, \DOMNode $context = null): string
    {
        $nodes = $context ? $xpath->query($expression, $context) : $xpath->query($expression);
        return ($nodes && $nodes->length > 0) ? trim($nodes->item(0)->nodeValue) : '';
    }

    /**
     * @return \DOMElement[] only the element nodes from the XPath result;
     *                       skips attribute/namespace nodes so callers can safely call getAttribute()
     */
    private static function queryElements(\DOMXPath $xpath, string $expression, \DOMNode $context = null): array
    {
        $elements = [];
        $nodes = $context ? $xpath->query($expression, $context) : $xpath->query($expression);
        foreach ($nodes ?: [] as $node) {
            if ($node instanceof \DOMElement) {
                $elements[] = $node;
            }
        }
        return $elements;
    }

    private static function appendIdentifier(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $doi = self::queryText($xpath, "//mods:identifier[@type='doi']");
        $doi = preg_replace('#^(?:https?://doi\.org/|doi:\s*)#i', '', $doi);
        self::appendTextElement($dom, $resource, 'identifier', $doi, ['identifierType' => 'DOI']);
    }

    private static function nameHasRole(\DOMXPath $xpath, \DOMNode $name, array $roles): bool
    {
        foreach ($xpath->query(".//mods:roleTerm[@type='code']", $name) as $roleTerm) {
            if (in_array(trim($roleTerm->nodeValue), $roles, true)) {
                return true;
            }
        }
        return false;
    }

    private static function buildNameData(\DOMXPath $xpath, \DOMElement $name): array
    {
        $givenName = self::queryText($xpath, ".//mods:namePart[@type='given']", $name);
        $familyName = self::queryText($xpath, ".//mods:namePart[@type='family']", $name);
        $displayForm = self::queryText($xpath, ".//mods:namePart[@type='displayForm']", $name);

        $isPersonal = $name->getAttribute('type') === 'personal';

        if ($isPersonal) {
            if ($displayForm !== '') {
                $fullName = $displayForm;
            } elseif ($givenName !== '' && $familyName !== '') {
                $fullName = "{$familyName}, {$givenName}";
            } else {
                $fullName = $familyName ?: $givenName;
            }
            $nameType = 'Personal';
        } else {
            $fullName = $displayForm ?: self::queryText($xpath, ".//mods:namePart[not(@type)]", $name);
            $nameType = 'Organizational';
        }

        return [
            'name' => $fullName,
            'nameType' => $nameType,
            'givenName' => $givenName,
            'familyName' => $familyName,
            'isPersonal' => $isPersonal,
        ];
    }

    private static function appendNameElement(
        \DOMDocument $dom,
        \DOMElement $parent,
        string $wrapperName,
        array $nameData,
        array $extraAttributes = []
    ): void {
        if ($nameData['name'] === '') {
            return;
        }
        $wrapper = $dom->createElement($wrapperName);
        foreach ($extraAttributes as $attribute => $value) {
            $wrapper->setAttribute($attribute, $value);
        }
        $nameElementName = ($wrapperName === 'creator') ? 'creatorName' : 'contributorName';
        self::appendTextElement($dom, $wrapper, $nameElementName, $nameData['name'], ['nameType' => $nameData['nameType']]);
        if ($nameData['isPersonal'] && $nameData['givenName'] !== '') {
            self::appendTextElement($dom, $wrapper, 'givenName', $nameData['givenName']);
        }
        if ($nameData['isPersonal'] && $nameData['familyName'] !== '') {
            self::appendTextElement($dom, $wrapper, 'familyName', $nameData['familyName']);
        }
        $parent->appendChild($wrapper);
    }

    /**
     * @return \DOMNode[] the mods:name nodes used as creators, so
     *                     appendContributors() can exclude them
     */
    private static function appendCreators(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): array
    {
        $creators = $dom->createElement('creators');
        $usedNames = [];
        $seen = [];

        foreach (self::queryElements($xpath, "//mods:name[@type='personal']") as $name) {
            if (!self::nameHasRole($xpath, $name, ['aut', 'cmp'])) {
                continue;
            }
            $nameData = self::buildNameData($xpath, $name);
            $key = $nameData['name'];
            if ($key === '' || isset($seen[$key])) {
                $usedNames[] = $name;
                continue;
            }
            $seen[$key] = true;
            self::appendNameElement($dom, $creators, 'creator', $nameData);
            $usedNames[] = $name;
        }

        // No personal author found (institution-only record): fall back to the
        // first corporate name actually responsible for the content. Provider/
        // repository roles (e.g. "prv") are excluded -- SLUB hosting the record
        // isn't its creator.
        if (empty($seen)) {
            foreach (self::queryElements($xpath, "//mods:name[@type='corporate']") as $name) {
                if (!self::nameHasRole($xpath, $name, self::$corporateCreatorRoles)) {
                    continue;
                }
                $nameData = self::buildNameData($xpath, $name);
                if ($nameData['name'] === '') {
                    continue;
                }
                self::appendNameElement($dom, $creators, 'creator', $nameData);
                $usedNames[] = $name;
                break;
            }
        }

        $resource->appendChild($creators);
        return $usedNames;
    }

    /**
     * @param \DOMNode[] $creatorNodes names already emitted as creators
     */
    private static function appendContributors(
        \DOMDocument $dom,
        \DOMElement $resource,
        \DOMXPath $xpath,
        array $creatorNodes
    ): void {
        $contributors = $dom->createElement('contributors');
        $hasContributors = false;

        foreach (self::queryElements($xpath, '//mods:name') as $name) {
            if (in_array($name, $creatorNodes, true)) {
                continue;
            }
            foreach (self::$contributorRoleMap as $role => $contributorType) {
                if (!self::nameHasRole($xpath, $name, [$role])) {
                    continue;
                }
                $nameData = self::buildNameData($xpath, $name);
                self::appendNameElement($dom, $contributors, 'contributor', $nameData, [
                    'contributorType' => $contributorType,
                ]);
                $hasContributors = true;
                break;
            }
        }

        if ($hasContributors) {
            $resource->appendChild($contributors);
        }
    }

    private static function appendTitles(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $titles = $dom->createElement('titles');

        $mainTitle = self::queryText($xpath, "//mods:titleInfo[@usage='primary']/mods:title");
        if ($mainTitle !== '') {
            self::appendTextElement($dom, $titles, 'title', $mainTitle);
        }

        foreach ($xpath->query("//mods:titleInfo[@usage='primary']/mods:subTitle") as $subTitle) {
            $text = trim($subTitle->nodeValue);
            if ($text !== '') {
                self::appendTextElement($dom, $titles, 'title', $text, ['titleType' => 'Subtitle']);
            }
        }

        $resource->appendChild($titles);
    }

    private static function appendPublisher(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $publisher = '';
        foreach ($xpath->query("//mods:name[@type='corporate']") as $corporation) {
            $role = self::queryText($xpath, ".//mods:roleTerm[@type='code']", $corporation);
            $name = self::queryText($xpath, './/mods:namePart', $corporation);
            if ($role === 'pbl') {
                $publisher = $name;
                break;
            } elseif ($role === 'dgg' || ($role === 'edt' && $publisher === '')) {
                $publisher = $name;
            }
        }
        self::appendTextElement($dom, $resource, 'publisher', $publisher);
    }

    private static function appendPublicationYear(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $date = self::queryText($xpath, "//mods:originInfo[@eventType='publication']/mods:dateIssued");
        if ($date === '') {
            $date = self::queryText($xpath, '//mods:originInfo/mods:dateIssued');
        }
        $year = '';
        if (preg_match('/(19|20)\d{2}/', $date, $matches)) {
            $year = $matches[0];
        }
        self::appendTextElement($dom, $resource, 'publicationYear', $year);
    }

    private static function appendSubjects(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $subjects = $dom->createElement('subjects');
        $hasSubjects = false;

        foreach ($xpath->query("//mods:classification[@authority='z']") as $classification) {
            foreach (explode(',', trim($classification->nodeValue)) as $subject) {
                $subject = trim($subject);
                if ($subject === '') {
                    continue;
                }
                self::appendTextElement($dom, $subjects, 'subject', $subject);
                $hasSubjects = true;
            }
        }

        foreach (self::queryElements($xpath, "//mods:classification[not(@authority='z')][normalize-space()]") as $classification) {
            $attributes = [];
            $authority = $classification->getAttribute('authority');
            if ($authority !== '') {
                $attributes['subjectScheme'] = $authority;
            }
            self::appendTextElement($dom, $subjects, 'subject', trim($classification->nodeValue), $attributes);
            $hasSubjects = true;
        }

        if ($hasSubjects) {
            $resource->appendChild($subjects);
        }
    }

    private static function appendDates(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $dates = $dom->createElement('dates');
        $hasDates = false;

        $issued = self::queryText($xpath, "//mods:originInfo[@eventType='publication']/mods:dateIssued");
        if ($issued !== '') {
            self::appendTextElement($dom, $dates, 'date', $issued, ['dateType' => 'Issued']);
            $hasDates = true;
        }

        $available = self::queryText($xpath, "//mods:originInfo[@eventType='distribution']/mods:dateIssued");
        if ($available !== '') {
            self::appendTextElement($dom, $dates, 'date', $available, ['dateType' => 'Available']);
            $hasDates = true;
        }

        if ($hasDates) {
            $resource->appendChild($dates);
        }
    }

    private static function appendLanguage(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $language = self::queryText(
            $xpath,
            "//mods:language/mods:languageTerm[@authority='iso639-2b'][@type='code']"
        );
        if ($language === '') {
            return;
        }
        $languageCode = LanguageCode::convertFrom6392Bto6391($language);
        self::appendTextElement($dom, $resource, 'language', $languageCode);
    }

    private static function appendResourceType(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $documentType = self::queryText($xpath, '//slub:documentType');
        if ($documentType === '') {
            return;
        }
        $resourceTypeGeneral = self::$resourceTypeGeneralMap[$documentType] ?? 'Text';
        self::appendTextElement($dom, $resource, 'resourceType', $documentType, [
            'resourceTypeGeneral' => $resourceTypeGeneral,
        ]);
    }

    private static function appendAlternateIdentifiers(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $alternateIdentifiers = $dom->createElement('alternateIdentifiers');
        $hasIdentifiers = false;

        $query = "//mods:identifier[not(@type='doi')][normalize-space()][not(ancestor::mods:relatedItem)]";
        foreach (self::queryElements($xpath, $query) as $identifier) {
            $type = strtolower($identifier->getAttribute('type'));
            $identifierType = self::$identifierTypeMap[$type] ?? ($identifier->getAttribute('type') ?: 'Other');
            self::appendTextElement($dom, $alternateIdentifiers, 'alternateIdentifier', trim($identifier->nodeValue), [
                'alternateIdentifierType' => $identifierType,
            ]);
            $hasIdentifiers = true;
        }

        if ($hasIdentifiers) {
            $resource->appendChild($alternateIdentifiers);
        }
    }

    private static function appendRelatedIdentifiers(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $relatedIdentifiers = $dom->createElement('relatedIdentifiers');
        $hasIdentifiers = false;

        foreach (self::$relatedItemRelationMap as $relatedItemType => $relationType) {
            $relatedItems = self::queryElements($xpath, "//mods:relatedItem[@type='{$relatedItemType}']");
            foreach ($relatedItems as $relatedItem) {
                foreach (self::queryElements($xpath, "mods:identifier[normalize-space()]", $relatedItem) as $identifier) {
                    $type = strtolower($identifier->getAttribute('type'));
                    if (!isset(self::$identifierTypeMap[$type])) {
                        continue;
                    }
                    self::appendTextElement(
                        $dom,
                        $relatedIdentifiers,
                        'relatedIdentifier',
                        trim($identifier->nodeValue),
                        [
                            'relatedIdentifierType' => self::$identifierTypeMap[$type],
                            'relationType' => $relationType,
                        ]
                    );
                    $hasIdentifiers = true;
                }
            }
        }

        if ($hasIdentifiers) {
            $resource->appendChild($relatedIdentifiers);
        }
    }

    private static function appendDescriptions(\DOMDocument $dom, \DOMElement $resource, \DOMXPath $xpath): void
    {
        $descriptions = $dom->createElement('descriptions');
        $hasDescriptions = false;

        foreach (self::queryElements($xpath, '//mods:abstract[normalize-space()]') as $abstract) {
            $attributes = ['descriptionType' => 'Abstract'];
            $lang = $abstract->getAttribute('xml:lang') ?: $abstract->getAttribute('lang');
            if ($lang !== '') {
                $attributes['xml:lang'] = $lang;
            }
            self::appendTextElement($dom, $descriptions, 'description', trim($abstract->nodeValue), $attributes);
            $hasDescriptions = true;
        }

        if ($hasDescriptions) {
            $resource->appendChild($descriptions);
        }
    }
}
