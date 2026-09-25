<?php
namespace Httpful;

use Psr\Http\Message\ResponseInterface;

/**
 * Drop-in replacement for nategood/httpful's Response.
 * @see Httpful\Request
 */
class Response
{
    /** @var mixed decoded JSON when the content type is JSON and autoParse is on, raw string otherwise */
    public $body;

    public string $raw_body;

    public string $content_type;

    public int $code;

    public static function fromPsrResponse(ResponseInterface $psrResponse, bool $autoParse): self
    {
        $response = new self();
        $response->raw_body = (string)$psrResponse->getBody();
        $response->code = $psrResponse->getStatusCode();
        $response->content_type = $psrResponse->getHeaderLine('Content-Type');

        if ($autoParse && stripos($response->content_type, 'json') !== false) {
            $decoded = json_decode($response->raw_body);
            $response->body = $decoded !== null ? $decoded : $response->raw_body;
        } else {
            $response->body = $response->raw_body;
        }

        return $response;
    }

    public function hasErrors(): bool
    {
        return $this->code >= 400;
    }

    /**
     * Several callers (PubMedImporter, K10plusImporter) pass the Response
     * itself where a string is expected (DOMDocument::loadXML($response),
     * ExternalMetadata::setData($response)), relying on implicit string
     * casting — matches nategood/httpful's own Response::__toString().
     */
    public function __toString(): string
    {
        return $this->raw_body;
    }
}
