<?php
namespace EWW\Dpf\Tests\Unit\Vendor\Httpful;

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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use GuzzleHttp\Psr7\Response as GuzzlePsr7Response;
use Httpful\Exception\ConnectionErrorException;
use Httpful\Request;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Contract test for the Httpful\Request shim (Guzzle-backed replacement for
 * nategood/httpful, which is unmaintained with an open advisory and no
 * fixed version). Callers in Classes/Services/** use only the API surface
 * exercised below: get/post, addHeader, expectsJson, autoParse, body,
 * timeout, and the Response ->body/->raw_body/->content_type/->code/->hasErrors().
 */
class RequestTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Request::setHttpClient(null);
        parent::tearDown();
    }

    private function mockClient(array $responses): Client
    {
        $handlerStack = HandlerStack::create(new MockHandler($responses));
        return new Client(['handler' => $handlerStack]);
    }

    public function testGetDecodesJsonBodyByDefault()
    {
        Request::setHttpClient($this->mockClient([
            new GuzzlePsr7Response(200, ['Content-Type' => 'application/json'], '{"member":[{"id":1}]}'),
        ]));

        $response = Request::get('https://example.org/tit.jsonld?q=test')->send();

        self::assertSame(200, $response->code);
        self::assertSame(1, $response->body->member[0]->id);
        self::assertFalse($response->hasErrors());
    }

    public function testPostSendsBodyAndCustomHeaders()
    {
        $history = [];
        $handlerStack = HandlerStack::create(new MockHandler([
            new GuzzlePsr7Response(200, ['Content-Type' => 'application/json'], '{"data":{"staff":[]}}'),
        ]));
        $handlerStack->push(\GuzzleHttp\Middleware::history($history));
        Request::setHttpClient(new Client(['handler' => $handlerStack]));

        $response = Request::post('https://fis.example.org/graphql')
            ->addHeader('Content-Type', 'application/json')
            ->body('{"query":"{ staff { id } }"}')
            ->timeout(10)
            ->send();

        self::assertSame([], $response->body->data->staff);

        /** @var GuzzlePsr7Request $sentRequest */
        $sentRequest = $history[0]['request'];
        self::assertSame('POST', $sentRequest->getMethod());
        self::assertSame('application/json', $sentRequest->getHeaderLine('Content-Type'));
        self::assertSame('{"query":"{ staff { id } }"}', (string)$sentRequest->getBody());
    }

    public function testAutoParseFalseKeepsRawBody()
    {
        Request::setHttpClient($this->mockClient([
            new GuzzlePsr7Response(200, ['Content-Type' => 'application/json'], '{"foo":"bar"}'),
        ]));

        $response = Request::get('https://api.crossref.org/works/10.1/x')
            ->autoParse(false)
            ->send();

        self::assertSame('{"foo":"bar"}', $response->raw_body);
        self::assertSame('{"foo":"bar"}', $response->body);
        self::assertStringContainsString('application/json', $response->content_type);
    }

    public function testResponseCastsToRawBodyForXmlConsumers()
    {
        Request::setHttpClient($this->mockClient([
            new GuzzlePsr7Response(200, ['Content-Type' => 'text/xml'], '<mods>ok</mods>'),
        ]));

        $response = Request::get('https://sru.k10plus.de/gvk')->send();

        self::assertSame('<mods>ok</mods>', (string)$response);
    }

    public function testErrorStatusMarksHasErrorsButDoesNotThrow()
    {
        Request::setHttpClient($this->mockClient([
            new GuzzlePsr7Response(404, [], 'not found'),
        ]));

        $response = Request::get('https://example.org/missing')->send();

        self::assertSame(404, $response->code);
        self::assertTrue($response->hasErrors());
    }

    public function testConnectionFailureThrowsConnectionErrorException()
    {
        Request::setHttpClient($this->mockClient([
            new ConnectException('Could not resolve host', new GuzzlePsr7Request('GET', 'https://unreachable.invalid')),
        ]));

        $this->expectException(ConnectionErrorException::class);

        Request::get('https://unreachable.invalid')->send();
    }
}
