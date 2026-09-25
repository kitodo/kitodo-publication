<?php
namespace Httpful\Exception;

/**
 * Drop-in replacement for nategood/httpful's ConnectionErrorException.
 * nategood/httpful is unmaintained with an open, unfixed security advisory
 * (PKSA-4dtf-ym9h-t41j) and has been replaced by a Guzzle-backed shim under
 * this namespace. See Httpful\Request and Httpful\Response.
 */
class ConnectionErrorException extends \Exception
{
    private int $curlErrorNumber;

    public function __construct(string $message, int $curlErrorNumber = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->curlErrorNumber = $curlErrorNumber;
    }

    public function getCurlErrorNumber(): int
    {
        return $this->curlErrorNumber;
    }
}
