<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ZedRequest\Communication\Plugin\Fixture;

use Spryker\Zed\ZedRequest\Business\Client\Request;
use Spryker\Zed\ZedRequest\Communication\Plugin\TransferObject\TransferServer as CoreTransferServer;

class TransferServer extends CoreTransferServer
{
    /**
     * @var \Spryker\Zed\ZedRequest\Business\Client\Request
     */
    protected $fixtureRequest;

    /**
     * @param \Spryker\Zed\ZedRequest\Business\Client\Request $request
     *
     * @return $this
     */
    public function setFixtureRequest(Request $request)
    {
        $this->fixtureRequest = $request;

        return $this;
    }

    /**
     * @return \Spryker\Zed\ZedRequest\Business\Client\Request
     */
    public function getRequest(): Request
    {
        if ($this->fixtureRequest !== null) {
            return $this->fixtureRequest;
        }

        return parent::getRequest();
    }
}
