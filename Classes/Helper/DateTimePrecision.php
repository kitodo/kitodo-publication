<?php

namespace EWW\Dpf\Helper;

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

class DateTimePrecision
{

    /**
     * Reduce microsecond precision to value that can be handled by PHP 7.
     *
     * @param String dateTimeString Datetime string
     * @return String Precision reduced datetime string
     */
    public static function reducePrecision(String $dateTimeString)
    {
        return preg_replace('/(\.\d{6})\d+Z/', '$1Z', $dateTimeString);
    }
}
