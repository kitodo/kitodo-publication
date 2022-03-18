<?php

namespace EWW\Dpf\Services\Storage\Fedora\Exception;

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
 *
 */

use Exception;
use GuzzleHttp\Exception\GuzzleException;

class FedoraException extends Exception
{
    const UNEXPECTED_RESPONSE = 1000;
    const NO_CONNECTION       = 1010;
    const ALREADY_EXISTS      = 1020;
    const TOMBSTONE_FOUND     = 1030;
    const NOTHING_FOUND       = 1040;
    const NO_TRANSACTION      = 1050;
    const INVALID_FILE        = 1060;
    const NOT_AUTHORIZED      = 1070;

    /**
     * @param string $message
     * @param int|null $code
     * @param string|null $requestUri
     * @param int|null $responseStatus
     * @return FedoraException
     */
    public static function create(
        string $message, int $code = null, string $requestUri = null, int $responseStatus = null
    )
    {
        $message .= $requestUri ?  ' [Uri: ' . $requestUri . ']' : '';
        $message .= $requestUri ?  ' [Http status: ' . $responseStatus . ']' : '';

        return new self($message, $code);
    }
}
