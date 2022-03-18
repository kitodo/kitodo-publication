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

class RetrieveDocumentException extends \Exception implements DPFExceptionInterface
{
    protected $messageLanguageKey = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_retrieve.failure';

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @return UpdateDocumentException
     */
    public static function create($message = "", $code = 0, Throwable $previous = null) : RetrieveDocumentException
    {
        return new self($message, $code, $previous);
    }

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @return UpdateDocumentException
     */
    public static function createNotFound($message = "", $code = 0, Throwable $previous = null) : RetrieveDocumentException
    {
        $exception = self::create($message, $code, $previous);
        $exception->messageLanguageKey = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_retrieve.notFoundFailure';
        return $exception;
    }

    /**
     * @return string
     */
    public function messageLanguageKey() : string
    {
        return $this->messageLanguageKey;
    }
}
