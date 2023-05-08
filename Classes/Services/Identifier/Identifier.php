<?php
namespace EWW\Dpf\Services\Identifier;

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

class Identifier
{
    /**
     * Check if the given identfier is an integer and as such
     * could be an Extbase record UID.
     *
     * @param mixed $identifier Identifier literal
     * @return bool True, if the given ID could be a UID.
     */
    public static function isUid($identifier) {
        return is_numeric($identifier);
    }

    /**
     * Check if the given identifier literal matches the process number format.
     *
     * @param mixed $identifier Identifier literal
     * @return bool True, is the literal matches a process number string
     */
    public static function isProcessNumber($identifier) {
        return preg_match("/^.+?-\d{1,2}-\d+$/", $identifier, $matches) > 0;
    }

}
