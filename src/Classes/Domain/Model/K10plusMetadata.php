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

/**
 * K10plusMetadata
 */
class K10plusMetadata extends ExternalMetadata
{
    public function getTitle(): string
    {
        $node = $this->getDataXpath()->query('/mods:mods/mods:titleInfo[1]/mods:title[1]');

        $title = '';

        if ($node->length == 1) {
            $title .= $node->item(0)->nodeValue;
        }

        $node = $this->getDataXpath()->query('/mods:mods/mods:titleInfo[1]/mods:subTitle[1]');

        if ($node->length == 1) {
            $title .= " - ".$node->item(0)->nodeValue;
        }

        return $title;
    }

    public function getPersons(): string
    {
        $xpath = $this->getDataXpath();

        $personList = [];

        $nodes = $xpath->query('/mods:mods/mods:name[@type="personal"]');

        foreach ($nodes as $person) {

            $namePartNodes =  $xpath->query('mods:namePart', $person);

            if ($namePartNodes->length > 0) {
                $personList[] = $namePartNodes->item(0)->nodeValue;
            }
        }

        return implode('; ', $personList);
    }

    public function getPublicationType(): string
    {
        // The k10 plus catalog has no usable document type for the purpose of importing into kitodo.
        return '';
    }

}
