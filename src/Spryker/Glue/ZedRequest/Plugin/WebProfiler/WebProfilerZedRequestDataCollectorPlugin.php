<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\ZedRequest\Plugin\WebProfiler;

use Spryker\Glue\Kernel\AbstractPlugin;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\WebProfilerExtension\Dependency\Plugin\WebProfilerDataCollectorPluginInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * @method \Spryker\Glue\ZedRequest\ZedRequestFactory getFactory()
 */
class WebProfilerZedRequestDataCollectorPlugin extends AbstractPlugin implements WebProfilerDataCollectorPluginInterface
{
    /**
     * @var string
     */
    protected const DATA_COLLECTOR_NAME = 'zed_request';

    /**
     * @var string
     */
    protected const DATA_TEMPLATE_NAME = '@ZedRequest/zed-request';

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string
     */
    public function getName(): string
    {
        return static::DATA_COLLECTOR_NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return static::DATA_TEMPLATE_NAME;
    }

    /**
     * {@inheritDoc}
     * - Returns a data collector which collects Zed requests data for the profile page.
     *
     * @api
     *
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface
     */
    public function getDataCollector(ContainerInterface $container): DataCollectorInterface
    {
        return $this->getFactory()->createZedRequestDataCollector();
    }
}
