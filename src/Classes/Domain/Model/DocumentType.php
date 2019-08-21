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
 * DocumentType
 */
class DocumentType extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * displayName
     *
     * @var string
     */
    protected $displayName = '';

    /**
     * virtual
     *
     * @var boolean
     */
    protected $virtual = false;

    /**
     * metadataPage
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataPage>
     * @cascade remove
     */
    protected $metadataPage = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @cascade remove
     */
    protected $transformationFile = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @cascade remove
     */
    protected $crossrefTransformation = null;

    /**
     * @var string
     */
    protected $crossrefTypes = '';


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @cascade remove
     */
    protected $dataciteTransformation = null;

    /**
     * @var string
     */
    protected $dataciteTypes = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @cascade remove
     */
    protected $k10plusTransformation = null;

    /**
     * @var string
     */
    protected $k10plusTypes = '';


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @cascade remove
     */
    protected $pubmedTransformation = null;

    /**
     * @var string
     */
    protected $pubmedTypes = '';


    /**
     * __construct
     */
    public function __construct()
    {
        //Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties
     *
     * @return void
     */
    protected function initStorageObjects()
    {
        $this->metadataPage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the displayName
     *
     * @return string $displayName
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Sets the displayName
     *
     * @param string $displayName
     * @return void
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * Adds a MetadataPage
     *
     * @param \EWW\Dpf\Domain\Model\MetadataPage $metadataPage
     * @return void
     */
    public function addMetadataPage(\EWW\Dpf\Domain\Model\MetadataPage $metadataPage)
    {
        $this->metadataPage->attach($metadataPage);
    }

    /**
     * Removes a MetadataPage
     *
     * @param \EWW\Dpf\Domain\Model\MetadataPage $metadataPageToRemove The MetadataPage to be removed
     * @return void
     */
    public function removeMetadataPage(\EWW\Dpf\Domain\Model\MetadataPage $metadataPageToRemove)
    {
        $this->metadataPage->detach($metadataPageToRemove);
    }

    /**
     * Returns the metadataPage
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataPage> $metadataPage
     */
    public function getMetadataPage()
    {
        return $this->metadataPage;
    }

    /**
     * Sets the metadataPage
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataPage> $metadataPage
     * @return void
     */
    public function setMetadataPage(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $metadataPage)
    {
        $this->metadataPage = $metadataPage;
    }

    /**
     * Alias for function getMetadataPage()
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataPage> $metadataPage
     */
    public function getChildren()
    {
        return $this->getMetadataPage();
    }

    /**
     * Returns the virtual
     *
     * @return boolean
     */
    public function getVirtual()
    {
        return $this->virtual;
    }

    /**
     * Sets the virtual
     *
     * @param boolean $virtual
     * @return void
     */
    public function setVirtual($virtual)
    {
        $this->virtual = $virtual;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile> $transformationFile
     */
    public function getTransformationFile()
    {
        return $this->transformationFile;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile> $transformationFile
     */
    public function setTransformationFile(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $transformationFile)
    {
        $this->transformationFile = $transformationFile;
    }



    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getCrossrefTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->crossrefTransformation;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getDataciteTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->dataciteTransformation;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getK10plusTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->k10plusTransformation;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getPubmedTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->pubmedTransformation;
    }

}
