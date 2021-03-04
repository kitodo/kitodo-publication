<?php
namespace EWW\Dpf\Domain\Model;

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

use EWW\Dpf\Services\ImportExternalMetadata\PubMedImporter;

/**
 * PubMedMetadata
 */
class PubMedMetadata extends ExternalMetadata
{
    public function getTitle(): string
    {
        $node = $this->getDataXpath()->query('/eSummaryResult/DocumentSummarySet/DocumentSummary/Title');

        if ($node->length == 1) {
            if ($node->item(0)->nodeValue) return $node->item(0)->nodeValue;
        }

        $node = $this->getDataXpath()->query('/eSummaryResult/DocumentSummarySet/DocumentSummary/BookTitle');

        if ($node->length == 1) {
            if ($node->item(0)->nodeValue) return $node->item(0)->nodeValue;
        }

        return '';
    }

    public function getPersons(): array
    {
        $xpath = $this->getDataXpath();

        $personList = [];

        $nodes = $xpath->query('/eSummaryResult/DocumentSummarySet/DocumentSummary/Authors/Author');

        foreach ($nodes as $person) {

            $name = ['family' => '', 'given' => ''];

            $namePartNodes =  $xpath->query('Name', $person);

            if ($namePartNodes->length > 0) {
                $name['family'] = $namePartNodes->item(0)->nodeValue;
            }

            $personList[] = $name;
        }

        return $personList;
    }

    public function getYear(): string
    {
        $xpath = $this->getDataXpath();

        $node = $xpath->query('/eSummaryResult/DocumentSummarySet/DocumentSummary/PubDate');

        if ($node->length > 0) {
            $year = $node->item(0)->nodeValue;

            return substr($year, 0, 4);
        }

        return '';
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getPublicationType(): string
    {
        $node = $this->getDataXpath()->query('/eSummaryResult/DocumentSummarySet/DocumentSummary/PubType/flag');

        // In PubMed a document can have more than one publication type.
        $types = [];
        if ($node->length == 1) {
            $types[] = $node->item(0)->nodeValue;
        } elseif ($node->length > 1) {
            foreach ($node as $typeNode) {
                $types[] = $typeNode->nodeValue;
            }
        }

        foreach ($types as $type) {
            // We are only interested in the first appearance of one of the defined PubMed types.
            // This is following the requirement description of the Ticket #716
            if (in_array($type, PubMedImporter::types())) {
                return $type;
            };
        }

        return '';
    }

}
