<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\ZedRequest\Logger;

use Spryker\Shared\ZedRequest\Dependency\Service\ZedRequestToUtilEncodingServiceInterface;

class ZedRequestInMemoryLogger implements ZedRequestLoggerInterface
{
    /**
     * @var array
     */
    protected static $logs = [];

    /**
     * @var \Spryker\Shared\ZedRequest\Dependency\Service\ZedRequestToUtilEncodingServiceInterface
     */
    protected $utilEncodingService;

    /**
     * @var string
     */
    protected $host;

    public function __construct(ZedRequestToUtilEncodingServiceInterface $utilEncodingService, string $host = '')
    {
        $this->utilEncodingService = $utilEncodingService;
        $this->host = $host;
    }

    public function log(string $url, array $payload, array $result, array $debug = []): void
    {
        static::$logs[] = [
            'destination' => $this->host ? ($this->host . $url) : $url,
            'payload' => $this->utilEncodingService->encodeJson($payload, JSON_PRETTY_PRINT) ?? '',
            'result' => $this->utilEncodingService->encodeJson($result, JSON_PRETTY_PRINT) ?? '',
            'debug' => $debug,
        ];
    }

    public function getLogs(): array
    {
        return static::$logs;
    }
}
