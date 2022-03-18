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
    protected $primaryFileMandatory;

    /**
     * @var string
     */
    protected $reservedFedoraPid;

    /**
     *
     * @var string
     */
    protected $fedoraPid;

    /**
     *
     * @var array
     */
    protected $files;

    /**
     *
     * @var string
     */
    protected $objectState;

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
     * @var bool
     */
    protected $temporary;

    /**
     * @var string
     */
    protected $comment = '';

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
    public function getPrimaryFileMandatory()
    {
        return $this->primaryFileMandatory;
    }

    /**
     *
     * @param boolean $primaryFileMandatory
     */
    public function setPrimaryFileMandatory($primaryFileMandatory)
    {
        $this->primaryFileMandatory = boolval($primaryFileMandatory);
    }

    /**
     *
     * @return string
     */
    public function getReservedFedoraPid()
    {
        return $this->reservedFedoraPid;
    }

    /**
     *
     * @param string $reservedFedoraPid
     */
    public function setReservedFedoraPid($reservedFedoraPid)
    {
        $this->reservedFedoraPid = $reservedFedoraPid;
    }

    /**
     *
     * @param string
     */
    public function getFedoraPid()
    {
        return $this->fedoraPid;
    }

    /**
     *
     * @param string $fedoraPid
     */
    public function setFedoraPid($fedoraPid)
    {
        $this->fedoraPid = $fedoraPid;
    }

    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return bool
     */
    public function hasFiles()
    {
        return is_array($this->files) && !empty($this->files);
    }

    public function setFiles($files)
    {
        $this->files = $files;
    }

    public function addFile($file)
    {
        $this->files[] = $file;
    }

    /**
     * @return bool
     */
    public function getValid()
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     */
    public function setValid($valid)
    {
        $this->valid = boolval($valid);
    }

    public function getFileNames()
    {
        $fileNames = array();
        foreach ($this->getFiles() as $file) {
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

    /**
     * Returns if a document is a temporary document.
     *
     * @return bool
     */
    public function isTemporary()
    {
        return $this->temporary;
    }

    /**
     * Sets if a document is a temporary document or not.
     * @param bool $temporary
     */
    public function setTemporary($temporary)
    {
        $this->temporary = boolval($temporary);
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }
}
