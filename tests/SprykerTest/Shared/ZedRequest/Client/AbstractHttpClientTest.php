<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\ZedRequest\Client;

use Codeception\Test\Unit;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Spryker\Service\UtilNetwork\UtilNetworkService;
use Spryker\Shared\Config\Config;
use Spryker\Shared\ZedRequest\Client\Exception\RequestException;
use Spryker\Shared\ZedRequest\Client\ResponseInterface;
use Spryker\Shared\ZedRequest\ZedRequestConstants;
use SprykerTest\Shared\ZedRequest\Client\Fixture\AbstractHttpClient;
use SprykerTest\Shared\ZedRequest\Client\Fixture\Transfer;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group ZedRequest
 * @group Client
 * @group AbstractHttpClientTest
 * Add your own group annotations below this line
 */
class AbstractHttpClientTest extends Unit
{
    /**
     * @var string
     */
    public const TRANSFER_VALUE = 'catface';

    public function testTransmissionWithBase64Disabled(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest', 'isBase64TransferEncodingEnabled']);
        $client->method('isBase64TransferEncodingEnabled')->willReturn(false);

        $responseBody = (string)json_encode([
            ResponseInterface::TRANSFER => ['key' => static::TRANSFER_VALUE],
            ResponseInterface::TRANSFER_CLASSNAME => Transfer::class,
        ]);
        $client->method('sendRequest')->willReturn(new Response(200, [], $responseBody));

        $response = $client->request('?foo=bar');
        $this->assertSame(static::TRANSFER_VALUE, $response->getTransfer()->getKey());
    }

    public function testTransmissionWithBase64Enabled(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest', 'isBase64TransferEncodingEnabled']);
        $client->method('isBase64TransferEncodingEnabled')->willReturn(true);

        $responseBody = base64_encode((string)json_encode([
            ResponseInterface::TRANSFER => ['key' => static::TRANSFER_VALUE],
            ResponseInterface::TRANSFER_CLASSNAME => Transfer::class,
        ]));
        $client->method('sendRequest')->willReturn(new Response(200, [], $responseBody));

        $response = $client->request('?foo=bar');
        $this->assertSame(static::TRANSFER_VALUE, $response->getTransfer()->getKey());
    }

    public function testRequestShouldLogExceptionWhenRequestExceptionOccures(): void
    {
        $abstractRequest = $this->getAbstractRequestMock(['sendRequest', 'logException']);
        $requestInterfaceMock = $this->getMockBuilder(RequestInterface::class)->getMock();
        $abstractRequest->expects($this->once())->method('sendRequest')->willThrowException(new GuzzleRequestException('Request exception test', $requestInterfaceMock));
        $abstractRequest->expects($this->once())->method('logException');

        $this->expectException(RequestException::class);
        $abstractRequest->request('?foo=bar');
    }

    /**
     * TE-8584: the template hardcoded the label "Stacktrace:" and appended the gateway response body after it.
     * The body is never a stacktrace, and every reader of a pasted record looked for one that was not there.
     *
     * @return void
     */
    public function testRequestExceptionMessageDoesNotClaimToContainAStacktrace(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);

        $message = $client->buildRequestExceptionMessagePublic(
            new Psr7Request('POST', 'https://backend-gateway.example.com/persistent-cart/gateway/sync-storage-quote'),
            new Response(500, [], 'irrelevant body'),
        );

        $this->assertStringNotContainsString('Stacktrace', $message);
    }

    public function testRequestExceptionMessageCarriesMethodTargetAndStatus(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);
        $url = 'https://backend-gateway.example.com/persistent-cart/gateway/sync-storage-quote';

        $message = $client->buildRequestExceptionMessagePublic(
            new Psr7Request('POST', $url),
            new Response(500, [], 'irrelevant body'),
        );

        $this->assertStringContainsString('[target] POST ' . $url, $message);
        $this->assertStringContainsString('[status] 500 Internal Server Error', $message);
    }

    /**
     * The request id is the join key between this record and the gateway's own log record, and it was
     * absent from the message, so nobody knew what to search the gateway log for.
     *
     * @return void
     */
    public function testRequestExceptionMessageCarriesTheRequestIdSentToTheGateway(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);

        $message = $client->buildRequestExceptionMessagePublic(
            new Psr7Request(
                'POST',
                'https://backend-gateway.example.com/gateway/action',
                ['X-Request-ID' => '3bfa3635'],
            ),
            new Response(500, [], 'irrelevant body'),
        );

        $this->assertStringContainsString('[requestId] 3bfa3635', $message);
    }

    public function testRequestExceptionMessageMarksTheRequestIdAsMissingWhenNoHeaderWasSent(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);

        $message = $client->buildRequestExceptionMessagePublic(
            new Psr7Request('POST', 'https://backend-gateway.example.com/gateway/action'),
            new Response(500, [], 'irrelevant body'),
        );

        $this->assertStringContainsString('[requestId] <not set>', $message);
    }

    /**
     * The case from the reported incident: the gateway answered 500 with a zero-length body, so the old
     * message ended at "Error: Stacktrace:" with nothing after it and read as though no error existed.
     *
     * @return void
     */
    public function testRequestExceptionMessageStatesAnEmptyGatewayBodyExplicitly(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);

        $message = $client->buildRequestExceptionMessagePublic(
            new Psr7Request('POST', 'https://backend-gateway.example.com/gateway/action'),
            new Response(500, [], ''),
        );

        $this->assertStringContainsString('[gateway response body] <empty, 0 bytes>', $message);
        $this->assertStringContainsString('terminated before the error handler', $message);
    }

    public function testRequestExceptionMessageStatesWhenNoResponseWasReceivedAtAll(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);

        $message = $client->buildRequestExceptionMessagePublic(
            new Psr7Request('POST', 'https://backend-gateway.example.com/gateway/action'),
            null,
        );

        $this->assertStringContainsString('[status] <no response>', $message);
        $this->assertStringContainsString('[gateway response body] <no response received>', $message);
    }

    /**
     * A whoops page or a large HTML error page used to be copied into the log message in full.
     *
     * @return void
     */
    public function testRequestExceptionMessageTruncatesALargeGatewayBody(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);
        $body = str_repeat('x', 5000);

        $message = $client->buildRequestExceptionMessagePublic(
            new Psr7Request('POST', 'https://backend-gateway.example.com/gateway/action'),
            new Response(500, [], $body),
        );

        $this->assertStringContainsString('(truncated, 5000 bytes total)', $message);
        $this->assertLessThan(5000, strlen($message));
    }

    public function testRequestExceptionMessageKeepsASmallGatewayBodyVerbatim(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);
        $body = '{"class":"RuntimeException","message":"Quote not found"}';

        $message = $client->buildRequestExceptionMessagePublic(
            new Psr7Request('POST', 'https://backend-gateway.example.com/gateway/action'),
            new Response(500, [], $body),
        );

        $this->assertStringContainsString($body, $message);
        $this->assertStringNotContainsString('truncated', $message);
    }

    /**
     * A URI omits a default port, which used to render as "host:" followed by nothing.
     *
     * @return void
     */
    public function testRequestPortFallsBackToTheSchemeDefaultWhenTheUriOmitsIt(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);

        $this->assertSame(443, $client->getRequestPortPublic(
            (new Psr7Request('POST', 'https://backend-gateway.example.com/gateway/action'))->getUri(),
        ));
        $this->assertSame(80, $client->getRequestPortPublic(
            (new Psr7Request('POST', 'http://backend-gateway.example.com/gateway/action'))->getUri(),
        ));
        $this->assertSame(8443, $client->getRequestPortPublic(
            (new Psr7Request('POST', 'https://backend-gateway.example.com:8443/gateway/action'))->getUri(),
        ));
    }

    /**
     * 64 of 66 real occurrences on growermarketplace-dev over 60 days were a 502 carrying nginx's own error
     * page, meaning the request never reached the gateway application and no application record exists.
     * Saying so stops the search for a log line that was never written.
     *
     * @return void
     */
    public function testRequestExceptionMessageFlagsAProxyStatusAsNotReachingTheApplication(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);

        foreach ([502, 503, 504] as $statusCode) {
            $message = $client->buildRequestExceptionMessagePublic(
                new Psr7Request('POST', 'https://backend-gateway.example.com/gateway/action'),
                new Response($statusCode, [], '<html><head><title>502 Bad Gateway</title></head></html>'),
            );

            $this->assertStringContainsString('a proxy status', $message);
            $this->assertStringContainsString('never reached the gateway application', $message);
        }
    }

    public function testRequestExceptionMessageDoesNotFlagAnApplicationStatusAsAProxyStatus(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);

        $message = $client->buildRequestExceptionMessagePublic(
            new Psr7Request('POST', 'https://backend-gateway.example.com/gateway/action'),
            new Response(500, [], 'irrelevant body'),
        );

        $this->assertStringContainsString('[status] 500 Internal Server Error', $message);
        $this->assertStringNotContainsString('a proxy status', $message);
    }

    /**
     * An empty body must not be reported as "nothing was logged". On the one occurrence traceable in
     * CloudWatch (2026-07-08, requestId 4ce0c973) the gateway had logged two CRITICAL records 42 ms
     * earlier; the body was empty because rendering ZED_ERROR_PAGE failed on a missing file.
     *
     * @return void
     */
    public function testRequestExceptionMessagePointsAtTheGatewayLogWhenTheBodyIsEmpty(): void
    {
        $client = $this->getAbstractRequestMock(['sendRequest']);

        $message = $client->buildRequestExceptionMessagePublic(
            new Psr7Request('POST', 'https://backend-gateway.example.com/gateway/action', ['X-Request-ID' => '4ce0c973']),
            new Response(500, [], ''),
        );

        $this->assertStringContainsString('does not mean the gateway logged nothing', $message);
        $this->assertStringContainsString('ZED_ERROR_PAGE', $message);
    }

    /**
     * @param array<string> $methods
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\SprykerTest\Shared\ZedRequest\Client\Fixture\AbstractHttpClient
     */
    protected function getAbstractRequestMock(array $methods): AbstractHttpClient
    {
        $baseUrl = Config::get(ZedRequestConstants::BASE_URL_ZED_API);
        $url = $baseUrl . '/';

        $utilNetworkService = new UtilNetworkService();

        return $this->getMockBuilder(AbstractHttpClient::class)
            ->onlyMethods($methods)
            ->setConstructorArgs([$url, $utilNetworkService])
            ->getMock();
    }
}
