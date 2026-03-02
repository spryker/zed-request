<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ZedRequest;

use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use Spryker\Glue\Kernel\AbstractFactory;
use Spryker\Glue\ZedRequest\HealthCheck\HealthCheckInterface;
use Spryker\Glue\ZedRequest\HealthCheck\ZedRequestHealthCheck;
use Spryker\Glue\ZedRequest\WebProfiler\ZedRequestDataCollector;
use Spryker\Shared\ZedRequest\Dependency\Service\ZedRequestToUtilEncodingServiceInterface;
use Spryker\Shared\ZedRequest\Logger\ZedRequestInMemoryLogger;
use Spryker\Shared\ZedRequest\Logger\ZedRequestLoggerInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * @method \Spryker\Glue\ZedRequest\ZedRequestConfig getConfig()
 */
class ZedRequestFactory extends AbstractFactory
{
    public function createZedRequestHealthChecker(): HealthCheckInterface
    {
        return new ZedRequestHealthCheck(
            $this->getZedRequestClient(),
        );
    }

    public function getZedRequestClient(): ZedRequestClientInterface
    {
        return $this->getProvidedDependency(ZedRequestDependencyProvider::CLIENT_ZED_REQUEST);
    }

    public function createZedRequestDataCollector(): DataCollectorInterface
    {
        return new ZedRequestDataCollector(
            $this->createZedRequestLogger(),
        );
    }

    public function createZedRequestLogger(): ZedRequestLoggerInterface
    {
        return new ZedRequestInMemoryLogger(
            $this->getUtilEncodingService(),
        );
    }

    public function getUtilEncodingService(): ZedRequestToUtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(ZedRequestDependencyProvider::SERVICE_UTIL_ENCODING);
    }
}
