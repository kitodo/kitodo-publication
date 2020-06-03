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
 * DataCiteMetadata
 */
class DataCiteMetadata extends ExternalMetadata
{
    public function getTitle(): string
    {
        $node = $this->getDataXpath()->query('/response/data/attributes/titles/title');

        if ($node->length == 1) {
            return $node->item(0)->nodeValue;
        }

        return '';
    }

    public function getPersons(): string
    {
        $xpath = $this->getDataXpath();

        $personList = [];

        $nodes = $xpath->query('/response/data/attributes/creators');

        foreach ($nodes as $person) {

            $namePartNodes =  $xpath->query('name', $person);

            if ($namePartNodes->length > 0) {
                $personList[] = $namePartNodes->item(0)->nodeValue;
            }
        }

        return implode('; ', $personList);
    }

    public function getPublicationType(): string
    {
        $node = $this->getDataXpath()->query('/response/data/attributes/types/resourceTypeGeneral');

        if ($node->length == 1) {
            return $node->item(0)->nodeValue;
        }

        return '';
    }

}
