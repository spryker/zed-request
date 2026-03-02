<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ZedRequest\Communication\Plugin\Fixture;

use Spryker\Shared\Kernel\Transfer\TransferInterface;

class NotGatewayController
{
    public function badAction(): string
    {
        return 'bad';
    }

    public function bazAction(TransferInterface $foo, int $bar = 0): int
    {
        if ($foo) {
            $bar = 0;
        }

        return $bar;
    }
}
