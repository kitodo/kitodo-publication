<?php
namespace EWW\Dpf\Services\Metadata;

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
 * Core MODS metadata extraction, ported verbatim from
 * \Kitodo\Dlf\Format\Mods (kitodo/presentation v3.3.4, GPL).
 *
 * Seeds author/place/year (+ sorting variants) before the configurable
 * XPath rules from tx_dpf_metadata run — same order as DLF.
 */
class ModsCoreExtractor
{
    /**
     * This extracts the essential MODS metadata from XML
     *
     * @param \SimpleXMLElement $xml The XML to extract the metadata from
     * @param array &$metadata The metadata array to fill
     *
     * @return void
     */
    public function extractMetadata(\SimpleXMLElement $xml, array &$metadata)
    {
        $xml->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
        // Get "author" and "author_sorting".
        $authors = $xml->xpath('./mods:name[./mods:role/mods:roleTerm[@type="code" and @authority="marcrelator"]="aut"]');
        // Get "author" and "author_sorting" again if that was to sophisticated.
        if (empty($authors)) {
            // Get all names which do not have any role term assigned and assume these are authors.
            $authors = $xml->xpath('./mods:name[not(./mods:role)]');
        }
        if (!empty($authors)) {
            for ($i = 0, $j = count($authors); $i < $j; $i++) {
                $authors[$i]->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
                // Check if there is a display form.
                if (($displayForm = $authors[$i]->xpath('./mods:displayForm'))) {
                    $metadata['author'][$i] = (string) $displayForm[0];
                } elseif (($nameParts = $authors[$i]->xpath('./mods:namePart'))) {
                    $name = [];
                    $k = 4;
                    foreach ($nameParts as $namePart) {
                        if (
                            isset($namePart['type'])
                            && (string) $namePart['type'] == 'family'
                        ) {
                            $name[0] = (string) $namePart;
                        } elseif (
                            isset($namePart['type'])
                            && (string) $namePart['type'] == 'given'
                        ) {
                            $name[1] = (string) $namePart;
                        } elseif (
                            isset($namePart['type'])
                            && (string) $namePart['type'] == 'termsOfAddress'
                        ) {
                            $name[2] = (string) $namePart;
                        } elseif (
                            isset($namePart['type'])
                            && (string) $namePart['type'] == 'date'
                        ) {
                            $name[3] = (string) $namePart;
                        } else {
                            $name[$k] = (string) $namePart;
                        }
                        $k++;
                    }
                    ksort($name);
                    $metadata['author'][$i] = trim(implode(', ', $name));
                }
                // Append "valueURI" to name using Unicode unit separator.
                if (isset($authors[$i]['valueURI'])) {
                    $metadata['author'][$i] .= chr(31) . (string) $authors[$i]['valueURI'];
                }
            }
        }
        // Get "place" and "place_sorting".
        $places = $xml->xpath('./mods:originInfo[not(./mods:edition="[Electronic ed.]")]/mods:place/mods:placeTerm');
        // Get "place" and "place_sorting" again if that was to sophisticated.
        if (empty($places)) {
            // Get all places and assume these are places of publication.
            $places = $xml->xpath('./mods:originInfo/mods:place/mods:placeTerm');
        }
        if (!empty($places)) {
            foreach ($places as $place) {
                $metadata['place'][] = (string) $place;
                if (empty($metadata['place_sorting'][0])) {
                    $metadata['place_sorting'][0] = preg_replace('/[[:punct:]]/', '', (string) $place);
                }
            }
        }
        // Get "year_sorting".
        if (($years_sorting = $xml->xpath('./mods:originInfo[not(./mods:edition="[Electronic ed.]")]/mods:dateOther[@type="order" and @encoding="w3cdtf"]'))) {
            foreach ($years_sorting as $year_sorting) {
                $metadata['year_sorting'][0] = intval($year_sorting);
            }
        }
        // Get "year" and "year_sorting" if not specified separately.
        $years = $xml->xpath('./mods:originInfo[not(./mods:edition="[Electronic ed.]")]/mods:dateIssued[@keyDate="yes"]');
        // Get "year" and "year_sorting" again if that was to sophisticated.
        if (empty($years)) {
            // Get all dates and assume these are dates of publication.
            $years = $xml->xpath('./mods:originInfo/mods:dateIssued');
        }
        if (!empty($years)) {
            foreach ($years as $year) {
                $metadata['year'][] = (string) $year;
                if (empty($metadata['year_sorting'][0])) {
                    $year_sorting = str_ireplace('x', '5', preg_replace('/[^\d.x]/i', '', (string) $year));
                    if (
                        strpos($year_sorting, '.')
                        || strlen($year_sorting) < 3
                    ) {
                        $year_sorting = ((intval(trim($year_sorting, '.')) - 1) * 100) + 50;
                    }
                    $metadata['year_sorting'][0] = intval($year_sorting);
                }
            }
        }
    }
}
