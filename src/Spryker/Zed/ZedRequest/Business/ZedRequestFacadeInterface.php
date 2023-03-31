<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ZedRequest\Business;

use Spryker\Shared\ZedRequest\Client\AbstractRequest;

/**
 * @method \Spryker\Zed\ZedRequest\Business\ZedRequestBusinessFactory getFactory()
 */
interface ZedRequestFacadeInterface
{
    /**
     * Specification:
     * - Method intended to be used in development environment only!
     * - Returns the request data from last yves to zed request
     * - If mvc given then it returns the data matching to the mvc argument
     * - mvc must be like `bundle_controller_action`
     *
     * @api
     *
     * @param string|null $bundleControllerAction
     *
     * @return array
     */
    public function getRepeatData($bundleControllerAction);

    /**
     * Specification:
     * - Returns current zed request.
     *
     * @api
     *
     * @return \Spryker\Shared\ZedRequest\Client\AbstractRequest
     */
    public function getCurrentZedRequest(): AbstractRequest;
}
