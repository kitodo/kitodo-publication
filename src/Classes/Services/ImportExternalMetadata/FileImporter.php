<?php
namespace EWW\Dpf\Services\ImportExternalMetadata;

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

use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\ExternalMetadata;

interface FileImporter
{
    /**
     * Returns the list of all publication types
     *
     * @return array
     */
    public static function types();

    /**
     * @param string $xml
     * @param DocumentType $documentType
     * @return array|null $metadataXml
     *
    */
    public function transformToInternalXml($xml, $documentType);

    /**
     * @return bool
     */
    public function hasMandatoryErrors();

    /**
     * @return array
     */
    public function getMandatoryErrors();

    /**
     * @param string $filePath
     * @param string $mandatoryFieldSettings
     * @param bool $contentOnly Determines if $file is a path or content as a string
     * @return array
     */
    public function loadFile($filePath, $mandatoryFieldSettings, $contentOnly = false);

    /**
     * @param ExternalMetadata $metadata
     * @param DocumentType $documentType
     * @return Document
     * @throws \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException
     */
    public function import($metadata, $documentType = null);

}