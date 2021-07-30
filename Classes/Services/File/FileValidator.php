<?php
namespace EWW\Dpf\Services\File;

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

use EWW\Dpf\Domain\Model\FileValidationResult;
use EWW\Dpf\Domain\Model\FileValidationResults;

class FileValidator implements FileValidatorInterface
{
    /**
     * @param string $url
     * @param string $mimeType
     * @param bool $download
     * @param bool $archive
     * @return FileValidationResults
     */
    public function validate(string $url, string $mimeType, bool $download, bool $archive)
    {
        $fileValidationResults = new FileValidationResults();

        // TODO: Start call validation api


        // Example results:


        $fileValidationResults->addResult(
            new FileValidationResult(
                FileValidationResult::OK,
                "File is ok"
            )
        );

        /*
        $fileValidationResults->addResult(
            new FileValidationResult(
            FileValidationResult::ERROR,
            "Invalid file.",
             "no_pdfa"
            )
        );

        $fileValidationResults->addResult(
            new FileValidationResult(
                FileValidationResult::WARNING,
                "A warning."
            )
        );
        */

        // TODO: End call validation api

        return $fileValidationResults;
    }
}
