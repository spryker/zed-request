<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ZedRequest\Communication\Plugin\Fixture;

use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController;
use stdClass;

class GatewayController extends AbstractGatewayController
{
    public function badAction(): string
    {
        return 'bad';
    }

    public function goodAction(TransferInterface $foo): TransferInterface
    {
        return $foo;
    }

    public function twoTransferParametersAction(TransferInterface $foo, TransferInterface $bar): TransferInterface
    {
        if ($bar) {
        }

        return $foo;
    }

    /**
     * @param \Spryker\Shared\Kernel\Transfer\TransferInterface $foo
     * @param mixed $bar
     * @param mixed $baz
     *
     * @return \Spryker\Shared\Kernel\Transfer\TransferInterface
     */
    public function tooManyParametersAction(TransferInterface $foo, $bar, $baz): TransferInterface
    {
        if ($bar && $baz) {
        }

        return $foo;
    }

    public function notTransferAction(stdClass $foo): stdClass
    {
        return $foo;
    }

    /**
     * @param mixed $foo
     *
     * @return mixed
     */
    public function noClassParameterAction($foo)
    {
        return $foo;
    }

    public function transformMessageAction(): void
    {
        $this->addInfoMessage('info');
        $this->addErrorMessage('error');
        $this->addSuccessMessage('success');
        $this->setSuccess(false);
    }
}
