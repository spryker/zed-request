<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ZedRequest\Business\Reader;

use Spryker\Shared\ZedRequest\Client\AbstractRequest;
use Spryker\Zed\ZedRequest\Business\Client\Request as ZedRequest;
use Spryker\Zed\ZedRequest\ZedRequestConfig;
use Symfony\Component\HttpFoundation\Request;

class ZedRequestReader implements ZedRequestReaderInterface
{
    public function __construct(
        protected Request $request,
        protected ZedRequestConfig $config,
    ) {
    }

    public function getCurrentZedRequest(): AbstractRequest
    {
        /** @phpstan-var string */
        $content = $this->request->getContent();
        $content = $this->decodeTransferContent($content);
        $transferValues = json_decode($content, true);

        return new ZedRequest($transferValues);
    }

    protected function decodeTransferContent(string $content): string
    {
        if (!$this->config->isBase64TransferEncodingEnabled()) {
            return $content;
        }

        return (string)base64_decode($content);
    }
}
