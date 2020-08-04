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
 * BibTexMetadata
 */
class BibTexMetadata extends ExternalMetadata
{

    /**
     * @return string
     * @throws \Exception
     */
    public function getTitle(): string
    {
        $node = $this->getDataXpath()->query('/response/title');

        if ($node->length == 1) {
            return $node->item(0)->nodeValue;
        }

        return '';
    }

    /**
     * @return array
     */
    public function getPersons(): array
    {
        $xpath = $this->getDataXpath();

        $personList = [];

        $nodes = $xpath->query('/response/author');

        foreach ($nodes as $person) {

            $name = ['family' => '', 'given' => ''];

            $family =  $xpath->query('family', $person);
            if ($family->length > 0) {
                $name['family'] = $family->item(0)->nodeValue;
            }

            $given =  $xpath->query('given', $person);
            if ($given->length > 0) {
                $name['given'] = $given->item(0)->nodeValue;
            }

            $personList[] = $name;
        }

        return $personList;
    }

    /**
     * @return string
     */
    public function getYear(): string
    {
        $xpath = $this->getDataXpath();

        $node = $xpath->query('/response/year');

        if ($node->length == 1) {
            return $node->item(0)->nodeValue;
        }

        return '';
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getPublicationType(): string
    {
        $node = $this->getDataXpath()->query('/response/_type');

        if ($node->length == 1) {
            return $node->item(0)->nodeValue;
        }

        return '';
    }

}
