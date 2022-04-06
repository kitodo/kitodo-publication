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
     * @param mixed $identifier
     * @return bool
     */
    public static function isUid($identifier) {
        return is_numeric($identifier);
    }

    /**
     * @param mixed $identifier
     * @return bool
     */
    public static function isFedoraPid($identifier) {
        return preg_match("/^[a-zA-Z]+([:-]\d+)+$/", $identifier, $matches) > 0;
    }

    /**
     * @param mixed $identifier
     * @return bool
     */
    public static function isProcessNumber($identifier) {
        return preg_match("/^.+?-\d{1,2}-\d+$/", $identifier, $matches) > 0;
    }

}
