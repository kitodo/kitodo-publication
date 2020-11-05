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
    /**
     * metadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
     * @inject
     */
    protected $metadataObjectRepository = null;

    /**
     * metadatGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @inject
     */
    protected $metadataGroupRepository = null;


    protected $modsDom;

    public function __construct($modsXml)
    {
        $this->setModsXml($modsXml);
    }

    public function setModsXml($modsXml)
    {
        $modsDom = new \DOMDocument();
        if (!empty($modsXml)) {

            $modsXml = preg_replace(
                "/<mods:mods.*?>/",
                '<mods:mods xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                .'xmlns:mods="http://www.loc.gov/mods/v3" '
                .'xmlns:slub="http://slub-dresden.de/" '
                .'xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" '
                .'xmlns:foaf="http://xmlns.com/foaf/0.1/" '
                .'xmlns:person="http://www.w3.org/ns/person#" '
                .'xsi:schemaLocation="http://www.loc.gov/mods/v3 '
                .'http://www.loc.gov/standards/mods/v3/mods-3-7.xsd" version="3.7">',
                $modsXml
            );

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


    public function setTitle($title)
    {
        $titleNode = $this->getModsXpath()->query('/mods:mods/mods:titleInfo[@usage="primary"]/mods:title');

        if ($titleNode->length == 0) {
            $titleNode = $this->getModsXpath()->query("/mods:mods/mods:titleInfo/mods:title");
        }

        if ($titleNode->length > 0) {
            $titleNode->item(0)->nodeValue = $title;
        }
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
     * @param string $role
     * @return array
     */
    public function getPersons($role = '')
    {
        $xpath = $this->getModsXpath();
        $personNodes = $xpath->query('/mods:mods/mods:name[@type="personal"]');

        $persons = [];

        foreach ($personNodes as $key => $personNode) {

            $familyNode = $xpath->query('mods:namePart[@type="family"]', $personNode);

            $givenNode = $xpath->query('mods:namePart[@type="given"]', $personNode);

            $roleNode = $xpath->query('mods:role/mods:roleTerm[@type="code"]', $personNode);

            $identifierNode = $xpath->query('mods:nameIdentifier[@type="FOBID"]', $personNode);

            $affiliationNodes  = $xpath->query('mods:affiliation', $personNode);
            $affiliationIdentifierNodes  = $xpath->query(
                'mods:nameIdentifier[@type="ScopusAuthorID"][@typeURI="http://www.scopus.com/authid"]',
                $personNode
            );

            $person['affiliations'] = [];
            foreach ($affiliationNodes as $key => $affiliationNode) {
                $person['affiliations'][] = $affiliationNode->nodeValue;
            }

            $person['affiliationIdentifiers'] = [];
            foreach ($affiliationIdentifierNodes as $key => $affiliationIdentifierNode) {
                $person['affiliationIdentifiers'][] = $affiliationIdentifierNode->nodeValue;
            }

            $given = '';
            $family = '';

            if ($givenNode->length > 0) {
                $given = $givenNode->item(0)->nodeValue;
            }

            if ($familyNode->length > 0) {
                $family = $familyNode->item(0)->nodeValue;
            }

            $person['given'] = trim($given);
            $person['family'] = trim($family);

            $name = [];
            if ($person['given']) {
                $name[] = $person['given'];
            }
            if ($person['family']) {
                $name[] = $person['family'];
            }

            $person['name'] = implode(' ', $name);

            $person['role'] = '';
            if ($roleNode->length > 0) {
                $person['role'] = $roleNode->item(0)->nodeValue;
            }

            $person['fobId'] = '';
            if ($identifierNode->length > 0) {
                $person['fobId'] = $identifierNode->item(0)->nodeValue;
            }

            $person['index'] = $key;

            $persons[] = $person;
        }

        if ($role) {
            $result = [];
            foreach ($persons as $person) {
                if ($person['role'] == $role)
                $result[] = $person;
            }
            return $result;
        } else {
            return $persons;
        }
    }


    /**
     * Get all related FOB-IDs
     *
     * @return array
     */
    public function getFobIdentifiers(): array
    {
        $xpath = $this->getModsXpath();

        $nodes = $xpath->query('/mods:mods/mods:name[@type="personal"]');

        $identifiers = [];

        foreach ($nodes as $key => $node) {

            $identifierNode = $xpath->query('mods:nameIdentifier[@type="FOBID"]', $node);

            if ($identifierNode->length > 0) {
                $identifiers[] = $identifierNode->item(0)->nodeValue;
            }
        }

        return $identifiers;
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

    /**
     * @return string
     */
    public function getSourceDetails()
    {
        $data = [];

        $dataNodes[] = $this->getModsXpath()->query('/mods:mods/mods:relatedItem[@type="original"]');

        $dataNodes[] = $this->getModsXpath()->query(
            '/mods:mods/mods:part[@type="article"]/mods:detail/mods:number'
        );

        $dataNodes[] = $this->getModsXpath()->query('/mods:mods/mods:part[@type="section"]');

        foreach ($dataNodes as $dataNode) {
            foreach ($dataNode as $node) {
                if ($node->hasChildNodes()) {
                    foreach ($node->childNodes as $n) {
                        $data[] = preg_replace('/\s+/', ' ', $n->textContent);
                    }
                } else {
                    $data[] = preg_replace('/\s+/', ' ', $node->textContent);
                }
            }
        }

        $output = trim(implode(' ', $data));
        $output = preg_replace('/\s+/ ', ' ', $output);
        return $output;
    }
}
