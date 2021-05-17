<?php

namespace EWW\Dpf\Domain\Model;

use Exception;
use TypeError;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;

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

class DocumentForm extends AbstractFormElement
{

    /**
     * @var string CSRF token for this form
     */
    protected $csrfToken;

    /**
     *
     * @var integer
     */
    protected $documentUid;

    /**
     *
     * @var boolean
     */
    protected $virtual;

    /**
     *
     * @var string
     */
    protected $qucosaId;

    /**
     *
     * @var string
     */
    protected $qucosaUrn;

    /**
     *
     * @var \EWW\Dpf\Domain\Model\File
     */
    protected $primaryFile;

    /**
     *
     * @var array
     */
    protected $secondaryFiles;

    /**
     *
     * @var array
     */
    protected $deletedFiles;

    /**
     *
     * @var array
     */
    protected $newFiles;

    /**
     *
     * @var string
     */
    protected $objectState;

    /**
     *
     * @var boolean
     */
    protected $deleteDisabled;

    /**
     *
     * @var boolean
     */
    protected $saveDisabled;

    /**
     *
     * @var boolean
     */
    protected $valid = false;

    /**
     *
     * @var string
     */
    protected $processNumber;

    /**
     * Assign and persist CSRF token for later form validation.
     *
     * @param string $csrfToken
     */
    public function generateCsrfToken()
    {
        $formProtection = FormProtectionFactory::get();
        $this->csrfToken = $formProtection->generateToken('DocumentForm', 'construct', 'DocumentForm');
        $formProtection->persistSessionToken();
    }

    /**
     * Set the CSRF token for this form
     *
     * Used when creating a new instance from request form data.
     *
     * @param string $csrfToken CSRF token to set
     * @throws Exception if the given string is empty.
     * @throws TypeError if the given string is null
     */
    public function setCsrfToken(string $csrfToken)
    {
        if ($csrfToken === "")
        {
            throw new Exception("A forms CSRF token cannot be empty");
        }
        $this->csrfToken = $csrfToken;
    }


    /**
     * Returns the CSRF token of this form
     *
     * @return string CSRF token for this form
     */
    public function getCsrfToken()
    {
        return $this->csrfToken;
    }


    /**
     * Validates this forms assigned CSRF token with token stored in the TYPO3 session.
     *
     * @return bool True, is CSRF token is considered valid. False if the token is invalid or missing.
     */
    public function hasValidCsrfToken()
    {
        $formProtection = FormProtectionFactory::get();
        return $formProtection->validateToken($this->csrfToken, 'DocumentForm', 'construct', 'DocumentForm');
    }

    /**
     *
     * @return integer
     */
    public function getDocumentUid()
    {
        return $this->documentUid;
    }

    /**
     *
     * @param integer $documentUid
     */
    public function setDocumentUid($documentUid)
    {
        $this->documentUid = $documentUid;
    }

    /**
     *
     * @return boolean
     */
    public function getVirtual()
    {
        return $this->virtual;
    }

    /**
     *
     * @param boolean $virtual
     */
    public function setVirtual($virtual)
    {
        $this->virtual = $virtual;
    }

    /**
     *
     * @return string
     */
    public function getQucosaId()
    {
        return $this->qucosaId;
    }

    /**
     *
     * @param string $qucosaId
     */
    public function setQucosaId($qucosaId)
    {
        $this->qucosaId = $qucosaId;
    }

    /**
     *
     * @return string
     */
    public function getQucosaUrn()
    {
        return $this->qucosaUrn;
    }

    /**
     *
     * @param string $qucosaUrn
     */
    public function setQucosaUrn($qucosaUrn)
    {
        $this->qucosaUrn = $qucosaUrn;
    }

    /**
     *
     * @param type \EWW\Dpf\Domain\Model\File $primaryFile
     */
    public function setPrimaryFile($primaryFile)
    {
        $this->primaryFile = $primaryFile;
    }

    /**
     *
     * @return \EWW\Dpf\Domain\Model\File
     */
    public function getPrimaryFile()
    {
        return $this->primaryFile;
    }

    public function setSecondaryFiles($secondaryFiles)
    {
        $this->secondaryFiles = $secondaryFiles;
    }

    public function getSecondaryFiles()
    {
        return $this->secondaryFiles;
    }

    public function getDeletedFiles()
    {
        return $this->deletedFiles;
    }

    public function setDeletedFiles($deletedFiles)
    {
        $this->deletedFiles = $deletedFiles;
    }

    public function getNewFiles()
    {
        return $this->newFiles;
    }

    public function setNewFiles($newFiles)
    {
        $this->newFiles = $newFiles;
    }

    public function getDeleteDisabled()
    {
        return $this->deleteDisabled;
    }

    public function setDeleteDisabled($deleteDisabled)
    {
        $this->deleteDisabled = $deleteDisabled;
    }

    public function getSaveDisabled()
    {
        return $this->saveDisabled;
    }

    public function setSaveDisabled($saveDisabled)
    {
        $this->saveDisabled = $saveDisabled;
    }

    public function getValid()
    {
        return $this->valid;
    }

    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    public function getNewFileNames()
    {
        $fileNames = array();
        foreach ($this->getNewFiles() as $file) {
            $fileNames[] = $file->getTitle();
        }
        return $fileNames;
    }

    /**
     * Sets the process number
     *
     * @return string
     */
    public function getProcessNumber()
    {
        return $this->processNumber;
    }

    /**
     * Gets the process number
     *
     * @param string $processNumber
     */
    public function setProcessNumber($processNumber)
    {
        $this->processNumber = $processNumber;
    }

}
