<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\ZedRequest\Client;

use Codeception\Test\Unit;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
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
