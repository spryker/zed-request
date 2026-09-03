<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\ZedRequest\WebProfiler;

use Spryker\Shared\ZedRequest\Logger\ZedRequestLoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Throwable;

class ZedRequestDataCollector extends DataCollector implements LateDataCollectorInterface
{
    /**
     * @var string
     */
    protected const COLLECTOR_NAME = 'zed_request';

    /**
     * @var \Spryker\Shared\ZedRequest\Logger\ZedRequestLoggerInterface
     */
    protected $zedRequestLogger;

    public function __construct(ZedRequestLoggerInterface $zedRequestLogger)
    {
        $this->zedRequestLogger = $zedRequestLogger;
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data['logs'] = $this->mapLogs();
    }

    /**
     * Runs on kernel.terminate, after StreamedResponse has executed its callback: without this the
     * profile is collected before the stream body runs and Zed requests made there are lost.
     */
    public function lateCollect(): void
    {
        $this->data['logs'] = $this->mapLogs();
    }

    /**
     * profiler_dump() requires VarDumper Data, so the logger's JSON strings are decoded and cloned here;
     * passing the raw string renders one unreadable scalar instead of a tree.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mapLogs(): array
    {
        $logs = [];

        foreach ($this->zedRequestLogger->getLogs() as $index => $log) {
            $logs[$index] = $log;
            $logs[$index]['payload'] = $this->cloneVar($this->decodeJson($log['payload'] ?? ''));
            $logs[$index]['result'] = $this->cloneVar($this->decodeJson($log['result'] ?? ''));
        }

        return $logs;
    }

    protected function decodeJson(string $json): mixed
    {
        $decoded = json_decode($json, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $json;
    }

    public function getName(): string
    {
        return static::COLLECTOR_NAME;
    }

    public function reset(): void
    {
        $this->data = [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLogs(): array
    {
        return $this->data['logs'];
    }
}
