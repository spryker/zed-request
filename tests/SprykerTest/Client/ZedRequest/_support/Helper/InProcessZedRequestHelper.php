<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Client\ZedRequest\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use ReflectionProperty;
use Spryker\Client\ZedRequest\Client\ZedClient;
use Spryker\Client\ZedRequest\ZedRequestFactory;
use Spryker\Shared\ZedRequest\Client\AbstractZedClient;

/**
 * Routes every Client→Zed RPC of the test through {@see InProcessZedHttpClient} by pre-seeding
 * the client instance ZedRequestFactory caches statically — every module stub (WishlistStub, …)
 * resolves its ZedClient through that cache, so no module-level wiring changes are needed.
 */
class InProcessZedRequestHelper extends Module
{
    public function _before(TestInterface $test): void // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        $this->enableInProcessZedRequests();
    }

    public function _after(TestInterface $test): void // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        $this->resetZedRequestState();
    }

    public function enableInProcessZedRequests(): void
    {
        $this->resetZedRequestState();
        $this->setStaticProperty(ZedRequestFactory::class, 'zedClient', new ZedClient(new InProcessZedHttpClient()));
    }

    protected function resetZedRequestState(): void
    {
        $this->setStaticProperty(ZedRequestFactory::class, 'zedClient', null);
        $this->setStaticProperty(AbstractZedClient::class, 'lastResponse', null);
        $this->setStaticProperty(AbstractZedClient::class, 'statusMessages', [
            'infoMessages' => [],
            'errorMessages' => [],
            'successMessages' => [],
        ]);
    }

    protected function setStaticProperty(string $className, string $propertyName, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty($className, $propertyName);
        $reflectionProperty->setValue(null, $value);
    }
}
