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

class EmbargoDate
{

    /**
     * Parses an embargo end date. An incomplete date ends at the end of its
     * period: "2031" on 2031-12-31, "2027-02" on 2027-02-28. DateTime alone
     * would read a bare "2031" as the time 20:31 today.
     *
     * @param string $value "yyyy", "yyyy-mm", or any other format DateTime accepts
     * @return \DateTime
     */
    public static function parse(string $value): \DateTime
    {
        if (preg_match('/^\d{4}$/', $value)) {
            return new \DateTime($value . '-12-31');
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return (new \DateTime($value . '-01'))->modify('last day of this month');
        }

        return new \DateTime($value);
    }
}
