<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ZedRequest\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\ZedRequest\Communication\Plugin\GatewayControllerListenerPlugin;
use Spryker\Zed\ZedRequest\ZedRequestDependencyProvider;

/**
 * @method \Spryker\Zed\ZedRequest\ZedRequestConfig getConfig()
 * @method \Spryker\Zed\ZedRequest\Business\ZedRequestFacadeInterface getFacade()
 */
class ZedRequestCommunicationFactory extends AbstractCommunicationFactory
{
    /**
     * @return \Spryker\Zed\ZedRequest\Dependency\Facade\ZedRequestToMessengerInterface
     */
    public function getMessengerFacade()
    {
        return $this->getProvidedDependency(ZedRequestDependencyProvider::FACADE_MESSENGER);
    }

    /**
     * @deprecated Will be removed after dynamic multi-store is always enabled.
     *
     * @return \Spryker\Zed\ZedRequest\Dependency\Facade\ZedRequestToStoreInterface
     */
    public function getStore()
    {
        return $this->getProvidedDependency(ZedRequestDependencyProvider::STORE);
    }

    /**
     * @deprecated Will be removed after dynamic multi-store is always enabled.
     *
     * @return bool
     */
    public function getIsDynamicStoreModeEnabled(): bool
    {
        return $this->getProvidedDependency(ZedRequestDependencyProvider::DYNAMIC_STORE_MODE);
    }

    /**
     * @return \Spryker\Zed\ZedRequest\Communication\Plugin\GatewayControllerListenerInterface
     */
    public function createControllerListener()
    {
        return new GatewayControllerListenerPlugin();
    }
}
