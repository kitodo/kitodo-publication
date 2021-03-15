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

interface MetadataMandatoryInterface
{
    const MANDATORY = "1";
    const MANDATORY_FILE_ONLY = "FILE_ONLY";

    const MANDATORY_VALUES = [
        'mandatory' => self::MANDATORY,
        'mandatory_file_only' => self::MANDATORY_FILE_ONLY
    ];

    public function getMandatory();
    public function setMandatory($mandatory);
}