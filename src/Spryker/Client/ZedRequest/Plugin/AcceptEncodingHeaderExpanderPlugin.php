<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\ZedRequest\Plugin;

use Spryker\Client\ZedRequestExtension\Dependency\Plugin\HeaderExpanderPluginInterface;
use Symfony\Component\HttpFoundation\Request;

class AcceptEncodingHeaderExpanderPlugin implements HeaderExpanderPluginInterface
{
    public function expandHeader(array $headers): array
    {
        $headers['Accept-Encoding'] = Request::createFromGlobals()->headers->get('Accept-Encoding', '');

        return $headers;
    }
}
