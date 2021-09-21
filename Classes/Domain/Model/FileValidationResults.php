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

class FileValidationResults
{
    /**
     * @var array
     */
    protected $fileValidationResults = [];

    /**
     * @return bool
     */
    public function isEverythingOk()
    {
        /** @var FileValidationResult $result */
        foreach ($this->fileValidationResults as $result) {
            if ($result->isError() || $result->isWarning()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getAllResults()
    {
        return $this->fileValidationResults;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        $errors = [];
        foreach ($this->fileValidationResults as $result) {
            if ($result->isError()) {
                $errors[] = $result;
            }
        }
        return $errors;
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        $warnings = [];
        foreach ($this->fileValidationResults as $result) {
            if ($result->isWarning()) {
                $warnings[] = $result;
            }
        }
        return $warnings;
    }

    /**
     * @param FileValidationResult $fileValidationResult
     */
    public function addResult(FileValidationResult $fileValidationResult)
    {
        $this->fileValidationResults[] = $fileValidationResult;
    }

}
