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

class PublicationIdentifier
{
    /**
     * Determines whether the identifier is a DOI, ISBN or PMID.
     *
     * @param $identifier
     * @return null|string
     */
    static function determineIdentifierType($identifier)
    {
        // FIXME: Wild guessing happening here. Determining actually requires to parse identifier and
        // check if the given string correpsponds to the actual rules and invalid identifiers must be rejected.

        // DOI
        if (strpos($identifier, '10.') === 0) {
            return 'DOI';
        }

        // ISBN
        $length = strlen(str_replace(['-', ' '], '', $identifier));

        if ($length === 13) {
            if (strpos($identifier, '978') === 0 || strpos($identifier, '979') === 0) {
                return 'ISBN';
            }
        }

        if ($length === 10) {
            return 'ISBN';
        }

        $length = strlen(trim($identifier));
        if ($length === 9) {
            if (strpos($identifier, '-') === 4) {
                return 'ISSN';
            }
        }

        // PMID
        if (is_numeric($identifier) && intval($identifier) == $identifier) {
            if (strlen($identifier) < 10) {
                return 'PMID';
            }
        }

        return null;
    }
}
