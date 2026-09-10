<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\ZedRequest\Client\Fixture;

use Psr\Http\Message\RequestInterface as MessageRequestInterface;
use Psr\Http\Message\ResponseInterface as MessageResponseInterface;
use Psr\Http\Message\UriInterface;
use Spryker\Shared\ZedRequest\Client\AbstractHttpClient as SharedAbstractHttpClient;

class AbstractHttpClient extends SharedAbstractHttpClient
{
    /**
     * @var array<string, string>
     */
    protected array $testHeaders = [];

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->testHeaders;
    }

    /**
     * @param array<string, string> $testHeaders
     *
     * @return void
     */
    public function setTestHeaders(array $testHeaders): void
    {
        $this->testHeaders = $testHeaders;
    }

    public function buildRequestExceptionMessagePublic(
        MessageRequestInterface $request,
        ?MessageResponseInterface $response
    ): string {
        return $this->buildRequestExceptionMessage($request, $response);
    }

    public function getRequestPortPublic(UriInterface $uri): int
    {
        return $this->getRequestPort($uri);
    }
}
