<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ZedRequest\Communication\Plugin;

use Codeception\Test\Unit;
use ReflectionObject;
use Spryker\Client\ZedRequest\Client\Response as ClientResponse;
use Spryker\Shared\ZedRequest\Client\ResponseInterface;
use Spryker\Shared\ZedRequest\ZedRequestConstants;
use Spryker\Zed\ZedRequest\Business\Model\Repeater;
use Spryker\Zed\ZedRequest\Communication\Plugin\TransferObject\TransferServer as CoreTransferServer;
use SprykerTest\Zed\ZedRequest\Communication\Plugin\Fixture\TransferServer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ZedRequest
 * @group Communication
 * @group Plugin
 * @group TransferServerTest
 * Add your own group annotations below this line
 */
class TransferServerTest extends Unit
{
    /**
     * @var string
     */
    protected const SESSION_ID = 'test-session-id';

    /**
     * @var \SprykerTest\Zed\ZedRequest\ZedRequestCommunicationTester
     */
    protected $tester;

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->resetTransferServerSingleton(CoreTransferServer::getInstance());
    }

    public function testSendReturnsPlainJsonWhenBase64IsDisabled(): void
    {
        $this->tester->setConfig(ZedRequestConstants::TRANSFER_BASE64_ENCODING_ENABLED, false);
        $server = $this->createTransferServerInstance();
        $server->setResponse($this->createSuccessResponse());

        $response = $server->send();
        $content = json_decode($response->getContent(), true);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertTrue($content[ResponseInterface::SUCCESS]);
    }

    public function testSendReturnsBase64EncodedJsonWhenBase64IsEnabled(): void
    {
        $this->tester->setConfig(ZedRequestConstants::TRANSFER_BASE64_ENCODING_ENABLED, true);
        $server = $this->createTransferServerInstance();
        $server->setResponse($this->createSuccessResponse());

        $response = $server->send();
        $content = json_decode((string)base64_decode($response->getContent(), true), true);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertTrue($content[ResponseInterface::SUCCESS]);
    }

    public function testGetRequestParsesPlainJsonBodyWhenBase64IsDisabled(): void
    {
        $this->tester->setConfig(ZedRequestConstants::TRANSFER_BASE64_ENCODING_ENABLED, false);

        $body = (string)json_encode(['sessionId' => static::SESSION_ID]);
        $server = $this->createTransferServerInstance();
        $server->setRequest(HttpRequest::create('/', 'POST', [], [], [], [], $body));

        $this->assertSame(static::SESSION_ID, $server->getRequest()->getSessionId());
    }

    public function testGetRequestDecodesBase64BodyWhenBase64IsEnabled(): void
    {
        $this->tester->setConfig(ZedRequestConstants::TRANSFER_BASE64_ENCODING_ENABLED, true);

        $body = base64_encode((string)json_encode(['sessionId' => static::SESSION_ID]));
        $server = $this->createTransferServerInstance();
        $server->setRequest(HttpRequest::create('/', 'POST', [], [], [], [], $body));

        $this->assertSame(static::SESSION_ID, $server->getRequest()->getSessionId());
    }

    protected function createSuccessResponse(): ClientResponse
    {
        $response = new ClientResponse();
        $response->fromArray([ResponseInterface::SUCCESS => true]);

        return $response;
    }

    protected function createTransferServerInstance(): TransferServer
    {
        $this->resetTransferServerSingleton(CoreTransferServer::getInstance());

        $repeaterMock = $this->getMockBuilder(Repeater::class)
            ->disableOriginalConstructor()
            ->getMock();

        return TransferServer::getInstance($repeaterMock);
    }

    protected function resetTransferServerSingleton(CoreTransferServer $transferServer): void
    {
        $refObject = new ReflectionObject($transferServer);
        $refProperty = $refObject->getProperty('instance');
        $refProperty->setAccessible(true);
        $refProperty->setValue(null);
    }
}
