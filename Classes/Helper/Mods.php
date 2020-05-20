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

class Mods
{

    protected $modsDom;

    public function __construct($modsXml)
    {
        $this->setModsXml($modsXml);
    }

    public function setModsXml($modsXml)
    {
        $modsDom = new \DOMDocument();
        if (!empty($modsXml)) {
            if (is_null(@$modsDom->loadXML($modsXml))) {
                throw new \Exception("Couldn't load MODS data!");
            }
        }
        $this->modsDom = $modsDom;
    }

    public function getModsXml()
    {
        return $this->modsDom->saveXML();
    }

    public function getModsXpath()
    {
        $xpath = \EWW\Dpf\Helper\XPath::create($this->modsDom);
        return $xpath;
    }

    public function getTitle()
    {
        $titleNode = $this->getModsXpath()->query('/mods:mods/mods:titleInfo[@usage="primary"]/mods:title');

        if ($titleNode->length == 0) {
            $titleNode = $this->getModsXpath()->query("/mods:mods/mods:titleInfo/mods:title");
        }
        return $titleNode->item(0)->nodeValue;
    }

    public function getAuthors()
    {
        return $this->getPersons("aut");
    }

    public function getPublishers()
    {
        return $this->getPersons("edt");
    }

    /**
     * Get persons of the given role
     *
     * @param $role
     * @return array
     */
    protected function getPersons($role)
    {
        $xpath = $this->getModsXpath();

        $authorNode = $xpath->query('/mods:mods/mods:name[mods:role/mods:roleTerm[@type="code"]="'.$role.'"]');

        $authors = array();

        foreach ($authorNode as $key => $author) {

            $familyNodes = $xpath->query('mods:namePart[@type="family"]', $author);

            $givenNodes = $xpath->query('mods:namePart[@type="given"]', $author);

            $name = array();

            if ($givenNodes->length > 0) {
                $name[] = $givenNodes->item(0)->nodeValue;
            }

            if ($familyNodes->length > 0) {
                $name[] = $familyNodes->item(0)->nodeValue;
            }

            $authors[$key] = implode(" ", $name);
        }

        return $authors;
    }

    public function setDateIssued($date)
    {

        $originInfo = $this->getModsXpath()->query('/mods:mods/mods:originInfo[@eventType="distribution"]');

        if ($originInfo->length > 0) {
            $dateIssued = $this->getModsXpath()->query('mods:dateIssued[@encoding="iso8601"][@keyDate="yes"]', $originInfo->item(0));

            if ($dateIssued->length == 0) {
                $newDateIssued = $this->modsDom->createElement('mods:dateIssued');
                $newDateIssued->setAttribute('encoding', 'iso8601');
                $newDateIssued->setAttribute('keyDate', 'yes');
                $newDateIssued->nodeValue = $date;
                $originInfo->item(0)->appendChild($newDateIssued);
            } else {
                $dateIssued->item(0)->nodeValue = $date;
            }

        } else {

            $rootNode = $this->getModsXpath()->query('/mods:mods');

            if ($rootNode->length == 1) {
                $newOriginInfo = $this->modsDom->createElement('mods:originInfo');
                $newOriginInfo->setAttribute('eventType', 'distribution');
                $rootNode->item(0)->appendChild($newOriginInfo);

                $newDateIssued = $this->modsDom->createElement('mods:dateIssued');
                $newDateIssued->setAttribute('encoding', 'iso8601');
                $newDateIssued->setAttribute('keyDate', 'yes');
                $newDateIssued->nodeValue = $date;
                $newOriginInfo->appendChild($newDateIssued);
            } else {
                throw new \Exception('Invalid xml data.');
            }

        }

    }


    public function getPublishingYear()
    {
        $year = $this->getModsXpath()->query('/mods:mods/mods:originInfo[@eventType="publication"]/mods:dateIssued[@encoding="iso8601"]');
        if ($year->length > 0) {
            return $year->item(0)->nodeValue;
        }

        return null;
    }

    public function getOriginalSourceTitle()
    {
        $node= $this->getModsXpath()->query('/mods:mods/mods:relatedItem[@type="original"]/mods:titleInfo/mods:title');
        if ($node->length > 0) {
            return $node->item(0)->nodeValue;
        }

        return null;
    }

    public function getDateIssued()
    {

        $dateIssued = $this->getModsXpath()->query('/mods:mods/mods:originInfo[@eventType="distribution"]/mods:dateIssued[@encoding="iso8601"][@keyDate="yes"]');
        if ($dateIssued->length > 0) {
            return $dateIssued->item(0)->nodeValue;
        }

        return null;
    }

    public function removeDateIssued()
    {

        $dateIssued = $this->getModsXpath()->query('/mods:mods/mods:originInfo[@eventType="distribution"]/mods:dateIssued[@encoding="iso8601"][@keyDate="yes"]');
        if ($dateIssued->length > 0) {
            $dateIssued->item(0)->parentNode->removeChild($dateIssued->item(0));
        }

    }

    public function hasQucosaUrn()
    {
        $urnNodeList = $this->getModsXpath()->query('/mods:mods/mods:identifier[@type="qucosa:urn"]');

        $hasUrn = false;

        foreach ($urnNodeList as $urnNode) {
            $value  = $urnNode->nodeValue;
            $hasUrn = $hasUrn || !empty($value);
        }

        return $hasUrn;
    }

    public function getQucosaUrn()
    {
        $urnNodeList = $this->getModsXpath()->query('/mods:mods/mods:identifier[@type="qucosa:urn"]');
        $urnList     = '';

        if ($urnNodeList != null) {
            foreach ($urnNodeList as $urnNode) {
                $urnList = $urnNode->nodeValue;
            }
        }
        return $urnList;
    }

    public function addQucosaUrn($urn)
    {
        $rootNode = $this->getModsXpath()->query('/mods:mods');

        if ($rootNode->length == 1) {
            $newUrn = $this->modsDom->createElement('mods:identifier');
            $newUrn->setAttribute('type', 'qucosa:urn');
            $newUrn->nodeValue = $urn;
            $rootNode->item(0)->appendChild($newUrn);
        } else {
            throw new \Exception('Invalid xml data.');
        }

    }

    public function clearAllUrn()
    {
        $urnNodeList = $this->getModsXpath()->query('/mods:mods/mods:identifier[@type="urn"]');

        foreach ($urnNodeList as $urnNode) {
            $urnNode->parentNode->removeChild($urnNode);
        }

        $urnNodeList = $this->getModsXpath()->query('/mods:mods/mods:identifier[@type="qucosa:urn"]');
        foreach ($urnNodeList as $urnNode) {
            $urnNode->parentNode->removeChild($urnNode);
        }
    }

}
