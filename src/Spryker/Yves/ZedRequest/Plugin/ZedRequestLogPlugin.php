<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\ZedRequest\Plugin;

use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Shared\ZedRequest\Client\AbstractHttpClient;
use Spryker\Shared\ZedRequest\Client\Middleware\MiddlewareInterface;

class ZedRequestLogPlugin implements MiddlewareInterface
{
    use LoggerTrait;

    /**
     * @var string
     */
    public const NAME = 'guzzle request log middleware';

    /**
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @return callable
     */
    public function getCallable()
    {
        return Middleware::mapRequest(function (RequestInterface $request) {
            if ($request->hasHeader(AbstractHttpClient::HEADER_HOST_YVES)) {
                $this->getLogger()->info(sprintf(
                    'Transfer request [%s] %s',
                    $request->getMethod(),
                    $request->getRequestTarget(),
                ), ['guzzle-body' => $request->getBody()->getContents()]);
            }

            // Guzzle 7.11+ enforces a `protocols` allow-list (default ['http', 'https'])
            // inside CurlFactory and rejects requests with an empty scheme synchronously.
            // Production Zed callers always pass URIs with an explicit scheme; this
            // defensive normalization keeps the middleware robust against malformed input.
            if ($request->getUri()->getScheme() === '') {
                $request = $request->withUri($request->getUri()->withScheme('http'));
            }

            return $request;
        });
    }
}
