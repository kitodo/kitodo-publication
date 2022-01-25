<?php
namespace EWW\Dpf\Services\Storage\Exception;

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

use EWW\Dpf\Exceptions\DPFExceptionInterface;

class ConnectionException extends \Exception implements DPFExceptionInterface
{
    /**
     * @return string
     */
    public function messageLanguageKey() : string
    {
        return 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_transfer.repository_connection_error';
    }
}
