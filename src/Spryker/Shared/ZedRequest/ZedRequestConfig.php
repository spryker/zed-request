<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\ZedRequest;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class ZedRequestConfig extends AbstractSharedConfig
{
    /**
     * Specification:
     * - Returns true when base64 encoding is enabled for gateway request and response body.
     *
     * @api
     */
    public function isBase64TransferEncodingEnabled(): bool
    {
        return $this->get(ZedRequestConstants::TRANSFER_BASE64_ENCODING_ENABLED, false);
    }
}
