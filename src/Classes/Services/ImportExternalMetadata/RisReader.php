<?php
namespace EWW\Dpf\Services\ImportExternalMetadata;

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

use Symfony\Component\Serializer\Encoder\XmlEncoder;


/**
 * Class RisReader
 *
 * A reader for the Web of Science RIS-Format
 *
 * @package EWW\Dpf\Services\ImportExternalMetadata
 */
class RisReader
{
    public static $tagMap = [
        'FN' => 'File Name',
        'VR' => 'Version Number',
        'PT' => 'Publication Type',
        'AU' => 'Author',
        'AF' => 'Author Full Name',
        'BA' => 'Book Author',
        'BF' => 'Book Author Full Name',
        'CA' => 'Group Author',
        'GP' => 'Book Group Author',
        'BE' => 'Editor',
        'TI' => 'Document Title',
        'SO' => 'Publication Name',
        'SE' => 'Book Series Title',
        'BS' => 'Book Series Subtitle',
        'LA' => 'Language',
        'DT' => 'Document Type',
        'CT' => 'Conference Title',
        'CY' => 'Conference Date',
        'CL' => 'Conference Location',
        'SP' => 'Conference Sponsors',
        'HO' => 'Conference Host',
        'DE' => 'Author Keywords',
        'ID' => 'Keywords Plus',
        'AB' => 'Abstract',
        'C1' => 'Author Address',
        'RP' => 'Reprint Address',
        'EM' => 'E-mail Address',
        'RI' => 'ResearcherID Number',
        'OI' => 'ORCID Identifier',
        'FU' => 'Funding Agency and Grant Number',
        'FX' => 'Funding Text',
        'CR' => 'Cited References',
        'NR' => 'Cited Reference Count',
        'TC' => 'WoS Times Cited Count',
        'Z9' => 'Total Times Cited Count',
        'U1' => 'Usage Count las 180 days',
        'U2' => 'Usage Count since 2013',
        'PU' => 'Publisher',
        'PI' => 'Publisher City',
        'PA' => 'Publisher Address',
        'SN' => 'ISSN',
        'EI' => 'eISSN',
        'BN' => 'ISBN',
        'J9' => 'Character-29 Source Abbreviation',
        'JI' => 'ISO Source Abbreviation',
        'PD' => 'Publication Date',
        'PY' => 'Year Published',
        'VL' => 'Volume',
        'IS' => 'Issue',
        'SI' => 'Special Issue',
        'PN' => 'Part Number',
        'SU' => 'Supplement',
        'MA' => 'Meeting Abstract',
        'BP' => 'Beginning Page',
        'EP' => 'Ending Page',
        'AR' => 'Article Number',
        'DI' => 'DOI',
        'D2' => 'Book DOI',
        'EA' => 'Early access date',
        'EY' => 'Early access year',
        'PG' => 'Page Count',
        'P2' => 'Chapter Count',
        'WC' => 'WoS Categories',
        'SC' => 'Research Areas',
        'GA' => 'Document Delivery Number',
        'PM' => 'PubMed ID',
        'UT' => 'Accession Number',
        'OA' => 'Open Access Indicator',
        'HP' => 'ESI Hot Paper',
        'HC' => 'ESI Highly Cited Paper',
        'DA' => 'Date generated',
        'ER' => 'End of Record',
        'EF' => 'End of File'
    ];


    public static $publicationTypes = [
        'J' => 'Journal',
        'B' => 'Book',
        'S' => 'Series',
        'P' => 'Patent'
    ];

    /**
     * Gets the full tag name
     *
     * @param string $tag
     */
    public static function tagToTagName($tag)
    {
        return str_replace(" ", "-", strtolower(self::$tagMap[$tag]));
    }

    /**
     * @param string $filePath
     * @return array
     */
    protected function readFile($filePath, $contentOnly = false)
    {
        $separator = "\r\n";

        if ($contentOnly) {
            $line = strtok($filePath, $separator);
        } else {
            $flags = FILE_SKIP_EMPTY_LINES | FILE_TEXT;
            $lines = file($filePath, $flags);
        }

        $currentTag = '';
        $risRecords = [];
        $risRecord = [];
        $recordIndex = 0;

        if ($contentOnly) {

            while ($line !== false) {
                if (mb_detect_encoding($line) == 'UTF-8') {
                    $line = utf8_decode($line);
                    if (strpos($line, '?') === 0) {
                        $line = substr($line, 1);
                    }
                }

                $tempTag = trim(substr($line, 0, 2));
                if ($tempTag == 'EF') {
                    // End of file
                    break;
                }

                if ($tempTag == 'ER') {
                    $risRecords[$recordIndex] = $risRecord;
                    $risRecord = [];
                    $recordIndex += 1;
                } else {
                    if ($tempTag) {
                        $currentTag = $tempTag;
                    }

                    $line = substr($line, 2);

                    if ($currentTag && array_key_exists($currentTag, self::$tagMap)) {
                        $risRecord[$currentTag][] = trim($line);
                    }
                }
                $line = strtok($separator);
            }

        } else {
            foreach($lines as $line) {

                if (mb_detect_encoding($line) == 'UTF-8') {
                    $line = utf8_decode($line);
                    if (strpos($line, '?') === 0) {
                        $line = substr($line, 1);
                    }
                }

                $tempTag = trim(substr($line, 0, 2));
                if ($tempTag == 'EF') {
                    // End of file
                    break;
                }

                if ($tempTag == 'ER') {
                    $risRecords[$recordIndex] = $risRecord;
                    $risRecord = [];
                    $recordIndex += 1;
                } else {
                    if ($tempTag) {
                        $currentTag = $tempTag;
                    }

                    $line = substr($line, 2);

                    if ($currentTag && array_key_exists($currentTag, self::$tagMap)) {
                        $risRecord[$currentTag][] = trim($line);
                    }
                }
            }
        }

        return $risRecords;
    }

    public function createRisRecords() {

    }

    public function parseFile($filePath, $contentOnly = false)
    {
        $risRecords = $this->readFile($filePath, $contentOnly);
        $risEntries = [];

        foreach ($risRecords as $risRecord) {

            $risEntry = [];

            foreach ($risRecord as $tag => $risFieldValues) {

                if (in_array($tag, ['AF','AU','BA','BF','CA','GP','BE'])) {
                    // Authors
                    foreach ($risFieldValues as $fieldValue) {

                        list($family, $given, $suffix) = array_map('trim', explode(',', $fieldValue));

                        $affiliations = [];
                        if ($tag == 'AF') {
                            if (array_key_exists('C1', $risRecord)) {
                                $c1 = $risRecord['C1'];
                                foreach ($c1 as $affiliation) {
                                    if (
                                        preg_match(
                                            "/^\[.*?(".$fieldValue.").*?\](.*)/u", trim($affiliation),
                                            $matches
                                        )
                                    ) {
                                        $affiliations[] = $matches[2];
                                    }
                                }
                            }
                        }

                        if ($family || $given || $suffix || $affiliations) {
                            $risEntry[$tag][] = [
                                'family' => $family,
                                'given' => $given,
                                'suffix' => $suffix,
                                'affiliation' => $affiliations
                            ];
                        }
                    }
                } else {
                    $value = implode(" ", $risFieldValues);

                    if ($tag == 'PT') {
                        if (array_key_exists($value, self::$publicationTypes)) {
                            $value = strtolower(self::$publicationTypes[$value]);
                        } else {
                            $value = 'unknown';
                        }
                    }

                    $risEntry[$tag] = $value;
                }

            }

            $risEntries[] = $risEntry;
        }
        //die("dfds");
        return $risEntries;
    }

    /**
     * @param array $risRecord
     */
    public function risRecordToXML($risRecord)
    {
        $encoder = new XmlEncoder();
        $record = [];
        foreach ($risRecord as $tag => $fieldValues) {
            switch ($tag) {
                case 'AF':
                    $record[self::tagToTagName('AU')] = $fieldValues;
                    break;
                case 'BF':
                    $record[self::tagToTagName('BA')] = $fieldValues;
                    break;
                case 'AU':
                    if (!array_key_exists('AF', $risRecord) || empty($risRecord['AF'])) {
                        $record[self::tagToTagName($tag)] = $fieldValues;
                    }
                    break;
                case 'BA':
                    if (!array_key_exists('BF', $risRecord) || empty($risRecord['BF'])) {
                        $record[self::tagToTagName($tag)] = $fieldValues;
                    }
                    break;
                default:
                    $record[self::tagToTagName($tag)] = $fieldValues;
                    break;
            }
        }

        return $encoder->encode($record, 'xml');
    }

}