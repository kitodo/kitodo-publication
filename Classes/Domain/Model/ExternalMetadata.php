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

use EWW\Dpf\Services\Xml\XPath;

/**
 * ExternalMetadata
 */
abstract class ExternalMetadata extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * feUser
     *
     * @var int
     */
    protected $feUser = 0;

    /**
     * @var string
     */
    protected $source = '';

    /**
     * data
     *
     * @var string
     */
    protected $data = '';

    /**
     * @var string
     */
    protected $publicationIdentifier = '';

    /**
     * @return string
     */

    public abstract function getTitle(): string;
    /**
     * @return array
     */
    public abstract function getPersons(): array;

    /**
     * @return string
     */
    public abstract function getPublicationType(): string;

    /**
     * @return string
     */
    public abstract function getYear(): string;

    /**
     * @return int
     */
    public function getFeUser(): int
    {
        return $this->feUser;
    }

    /**
     * @param int $feUser
     */
    public function setFeUser(int $feUser)
    {
        $this->feUser = $feUser;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source)
    {
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }


    /**
     * @param string $data
     */
    public function setData(string $data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getPublicationIdentifier(): string
    {
        return $this->publicationIdentifier;
    }

    /**
     * @param string $publicationIdentifier
     */
    public function setPublicationIdentifier(string $publicationIdentifier)
    {
        $this->publicationIdentifier = trim($publicationIdentifier);
    }

    /**
     * @return \DOMXPath
     * @throws \Exception
     */
    public function getDataXpath()
    {
        $dom = new \DOMDocument();
        if (is_null(@$dom->loadXML($this->data))) {
            throw new \Exception("Invalid XML: ".get_class($this));
        }
        return XPath::create($dom);
    }
}
