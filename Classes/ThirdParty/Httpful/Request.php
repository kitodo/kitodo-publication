<?php
namespace Httpful;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Httpful\Exception\ConnectionErrorException;

/**
 * Drop-in replacement for nategood/httpful's Request, backed by Guzzle
 * (already a dpf dependency). nategood/httpful is unmaintained with an
 * open, unfixed security advisory (PKSA-4dtf-ym9h-t41j); this shim covers
 * only the API surface actually used under Classes/Services/**:
 * get/post, addHeader, expectsJson, autoParse, body, timeout, send.
 *
 * Callers are untouched — `use Httpful\Request;` still resolves here via
 * the "Httpful\\" psr-4 autoload entry in composer.json.
 */
class Request
{
    private static ?Client $httpClient = null;

    private string $method;
    private string $uri;
    private array $headers = [];
    private $bodyData;
    private ?int $timeoutSeconds = null;
    private bool $autoParse = true;

    private function __construct(string $method, string $uri)
    {
        $this->method = $method;
        $this->uri = $uri;
    }

    public static function get(string $uri): self
    {
        return new self('GET', $uri);
    }

    public static function post(string $uri): self
    {
        return new self('POST', $uri);
    }

    /**
     * Test seam only: inject a mock Guzzle client. Pass null to reset to
     * the default real client.
     */
    public static function setHttpClient(?Client $client): void
    {
        self::$httpClient = $client;
    }

    private static function client(): Client
    {
        if (self::$httpClient === null) {
            self::$httpClient = new Client();
        }

        return self::$httpClient;
    }

    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function expectsJson(): self
    {
        $this->headers['Accept'] = $this->headers['Accept'] ?? 'application/json';
        return $this;
    }

    public function autoParse(bool $autoParse): self
    {
        $this->autoParse = $autoParse;
        return $this;
    }

    public function body($data): self
    {
        $this->bodyData = $data;
        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->timeoutSeconds = $seconds;
        return $this;
    }

    public function send(): Response
    {
        $options = ['headers' => $this->headers];

        if ($this->bodyData !== null) {
            $options['body'] = $this->bodyData;
        }

        if ($this->timeoutSeconds !== null) {
            $options['timeout'] = $this->timeoutSeconds;
        }

        try {
            $psrResponse = self::client()->request($this->method, $this->uri, $options);
        } catch (RequestException $e) {
            if (!$e->hasResponse()) {
                $context = $e->getHandlerContext();
                throw new ConnectionErrorException(
                    $e->getMessage(),
                    (int)($context['errno'] ?? 0),
                    $e
                );
            }

            $psrResponse = $e->getResponse();
        }

        return Response::fromPsrResponse($psrResponse, $this->autoParse);
    }
}
