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

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Model\ExternalMetadata;

interface Importer
{
    /**
     * Returns the list of all publication types
     *
     * @return array
     */
    public static function types();


    /**
     * Returns the list of publication types: ( ['type','"type"'] ).
     * @param array $types
     * @return array
     */
    public static function typeItems($types);


    /**
     * @param string $identifier
     * @return ExternalMetadata|null
     */
    public function findByIdentifier($identifier);


    /**
     * @param string $query
     * @return mixed
     */
    public function search($query);


    /**
     * @param string $xml
     * @param DocumentType $documentType
     * @return array|null $metadataXml
     *
    */
    public function transformToInternalXml($xml, $documentType);


    /**
     * @param ExternalMetadata $metadata
     * @param DocumentType $documentType
     * @return Document
     * @throws \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException
     */
    public function import($metadata, $documentType = null);

}
