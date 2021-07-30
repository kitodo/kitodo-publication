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

class FileValidationResult
{
    const OK = 'OK';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';

    /**
     * @var string
     */
    protected $status = '';

    /**
     * @var string
     */
    protected $code = '';

    /**
     * @var string
     */
    protected $message = '';

    /**
     * FileValidationResults constructor.
     * @param string $status
     * @param string $message
     * @param string $code
     */
    public function __construct(string $status, string $message, string $code = '')
    {
        $this->status = $status;
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * @return bool
     */
    public function isError(): string
    {
        return $this->status === self::ERROR;
    }

    /**
     * @return bool
     */
    public function isWarning(): string
    {
        return $this->status === self::WARNING;
    }

    /**
     * @return bool
     */
    public function isOk(): string
    {
        return $this->status === self::OK;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

}
