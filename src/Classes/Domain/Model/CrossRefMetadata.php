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
 * CrossRefMetadata
 */
class CrossRefMetadata extends ExternalMetadata
{

    /**
     * @return string
     * @throws \Exception
     */
    public function getTitle(): string
    {
        $node = $this->getDataXpath()->query('/response/message/title');

        if ($node->length == 1) {
            return $node->item(0)->nodeValue;
        }

        return '';
    }

    /**
     * @return string
     */
    public function getPersons(): string
    {
        $xpath = $this->getDataXpath();

        $personList = [];

        $nodes = $xpath->query('/response/message/author');

        foreach ($nodes as $person) {

            $author = [];

            $family =  $xpath->query('family', $person);
            if ($family->length > 0) {
                $author[] = $family->item(0)->nodeValue;
            }

            $given =  $xpath->query('given', $person);
            if ($given->length > 0) {
                $author[] = $given->item(0)->nodeValue;
            }

            $personList[] = implode(', ', $author);
        }

        return implode('; ', $personList);
    }


    /**
     * @return string
     * @throws \Exception
     */
    public function getPublicationType(): string
    {
        $node = $this->getDataXpath()->query('/response/message/type');

        if ($node->length == 1) {
            return $node->item(0)->nodeValue;
        }

        return '';
    }

}
