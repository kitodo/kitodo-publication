<?php
namespace EWW\Dpf\Services\Transfer;

/**
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

class FileId
{

    protected $id = 0;

    public function __construct($document)
    {

        $idList   = array();
        $this->id = 0;

        if (is_a($document->getFile(), '\TYPO3\CMS\Extbase\Persistence\ObjectStorage')) {
            foreach ($document->getFile() as $file) {
                $dsId = $file->getDatastreamIdentifier();
                if (!empty($dsId) && $dsId != \EWW\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER) {
                    $id       = explode("-", $dsId);
                    $idList[] = $id[1];
                }
            }
        } else {
        }

        if (!empty($idList)) {
            $this->id = max($idList);
        }

    }

    public function getId($file)
    {

        $fileId = $file->getDatastreamIdentifier();
        if (empty($fileId)) {
            if ($file->isPrimaryFile()) {
                return \EWW\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER;
            } else {
                $this->id = $this->id + 1;
                return \EWW\Dpf\Domain\Model\File::DATASTREAM_IDENTIFIER_PREFIX . $this->id;
            }
        } else {
            return $fileId;
        }

    }

}
