<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\ZedRequest\Plugin;

use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\ZedRequestExtension\Dependency\Plugin\HeaderExpanderPluginInterface;

/**
 * @method \Spryker\Client\ZedRequest\ZedRequestClient getClient()
 */
class RequestIdHeaderExpanderPlugin extends AbstractPlugin implements HeaderExpanderPluginInterface
{
    public function expandHeader(array $headers): array
    {
        $headers['X-Request-ID'] = $this->getClient()->getRequestId();

        return $headers;
    }
}
