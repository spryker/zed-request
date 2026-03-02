<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\ZedRequest\Header\RequestId;

use Spryker\Service\UtilNetwork\UtilNetworkServiceInterface;

class RequestId implements RequestIdInterface
{
    /**
     * @var \Spryker\Service\UtilNetwork\UtilNetworkServiceInterface
     */
    protected $utilNetworkService;

    public function __construct(UtilNetworkServiceInterface $utilNetworkService)
    {
        $this->utilNetworkService = $utilNetworkService;
    }

    public function getRequestId(): string
    {
        return $this->utilNetworkService->getRequestId();
    }
}
