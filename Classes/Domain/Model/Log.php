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

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Log extends AbstractEntity
{
    /**
     * @var string
     */
    protected $requestId = '';

    /**
     * @var float
     */
    protected $timeMicro = 0.0000;

    /**
     * @var string
     */
    protected $component = '';

    /**
     * @var int
     */
    protected $level = 0;

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var string
     */
    protected $data = '';

    /**
     * @var int
     */
    protected $clientId = 0;


    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
    }

    public function getTimeMicro(): float
    {
        return $this->timeMicro;
    }

    public function setTimeMicro(float $timeMicro): void
    {
        $this->timeMicro = $timeMicro;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function setComponent(string $component): void
    {
        $this->component = $component;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getDataArray() {
        $result = json_decode($this->data, true);
        if (!is_array($result)) {
            return [];
        }
        return $result;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function setClientId(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public static function getLogLevels() {
        $logLevels = [
            LogLevel::EMERGENCY => 'emergency',
            LogLevel::ALERT => 'alert',
            LogLevel::CRITICAL => 'critical',
            LogLevel::ERROR => 'error',
            LogLevel::WARNING => 'warning',
            LogLevel::NOTICE => 'notice',
            LogLevel::INFO => 'info',
            LogLevel::DEBUG => 'debug',
        ];
        return $logLevels;
    }

}
