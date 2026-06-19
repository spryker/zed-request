<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ZedRequest\Business\Reader;

use Codeception\Test\Unit;
use Spryker\Zed\ZedRequest\Business\Reader\ZedRequestReader;
use Spryker\Zed\ZedRequest\ZedRequestConfig;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ZedRequest
 * @group Business
 * @group Reader
 * @group ZedRequestReaderTest
 * Add your own group annotations below this line
 */
class ZedRequestReaderTest extends Unit
{
    /**
     * @var string
     */
    protected const SESSION_ID = 'test-session-id';

    public function testGetCurrentZedRequestParsesPlainJsonBodyWhenBase64IsDisabled(): void
    {
        $body = (string)json_encode(['sessionId' => static::SESSION_ID]);
        $httpRequest = HttpRequest::create('/', 'POST', [], [], [], [], $body);

        $reader = new ZedRequestReader($httpRequest, $this->createConfigMock(false));

        $zedRequest = $reader->getCurrentZedRequest();

        $this->assertSame(static::SESSION_ID, $zedRequest->getSessionId());
    }

    public function testGetCurrentZedRequestDecodesBase64BodyWhenBase64IsEnabled(): void
    {
        $body = base64_encode((string)json_encode(['sessionId' => static::SESSION_ID]));
        $httpRequest = HttpRequest::create('/', 'POST', [], [], [], [], $body);

        $reader = new ZedRequestReader($httpRequest, $this->createConfigMock(true));

        $zedRequest = $reader->getCurrentZedRequest();

        $this->assertSame(static::SESSION_ID, $zedRequest->getSessionId());
    }

    protected function createConfigMock(bool $isBase64Enabled): ZedRequestConfig
    {
        $configMock = $this->getMockBuilder(ZedRequestConfig::class)
            ->onlyMethods(['isBase64TransferEncodingEnabled'])
            ->getMock();
        $configMock->method('isBase64TransferEncodingEnabled')->willReturn($isBase64Enabled);

        return $configMock;
    }
}
