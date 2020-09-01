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
 * Document
 */
class DepositLicenseLog extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var string
     */
    protected $licenceUri = '';

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $objectIdentifier = '';

    /**
     * @var string
     */
    protected $processNumber = '';

    /**
     * @var string
     */
    protected $urn = '';

    /**
     * @var string
     */
    protected $fileNames = '';

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getLicenceUri(): string
    {
        return $this->licenceUri;
    }

    /**
     * @param string $licenceUri
     */
    public function setLicenceUri(string $licenceUri): void
    {
        $this->licenceUri = $licenceUri;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getObjectIdentifier(): string
    {
        return $this->objectIdentifier;
    }

    /**
     * @param string $objectIdentifier
     */
    public function setObjectIdentifier(string $objectIdentifier): void
    {
        $this->objectIdentifier = $objectIdentifier;
    }

    /**
     * @return string
     */
    public function getProcessNumber(): string
    {
        return $this->processNumber;
    }

    /**
     * @param string $processNumber
     */
    public function setProcessNumber(string $processNumber): void
    {
        $this->processNumber = $processNumber;
    }

    /**
     * @return string
     */
    public function getUrn(): string
    {
        return $this->urn;
    }

    /**
     * @param string $urn
     */
    public function setUrn(string $urn): void
    {
        $this->urn = $urn;
    }

    /**
     * @return string
     */
    public function getFileNames(): string
    {
        return $this->fileNames;
    }

    /**
     * @param string $fileNames
     */
    public function setFileNames(string $fileNames): void
    {
        $this->fileNames = $fileNames;
    }
}
