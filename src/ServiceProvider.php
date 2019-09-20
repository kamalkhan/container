<?php

/*
 * This file is part of bhittani/container.
 *
 * (c) Kamal Khan <shout@bhittani.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bhittani\Container;

use Psr\Container\ContainerInterface;

abstract class ServiceProvider
    implements ServiceProviderInterface,
        BootableServiceProviderInterface,
        DeferrableServiceProviderInterface
{
    /**
     * Macros.
     *
     * @var string[]
     */
    protected $macros = [];

    /**
     * Bindings.
     *
     * @var string[]
     */
    protected $provides = [];

    /** @inheritDoc */
    public function boot(ContainerInterface $container)
    {
        // Extended classes may implement this method.
    }

    /** @inheritDoc */
    public function register(ContainerInterface $container)
    {
        // Extended classes may implement this method.
    }

    /** @inheritDoc */
    public function getBindings()
    {
        return $this->provides;
    }

    /** @inheritDoc */
    public function getMacros()
    {
        return $this->macros;
    }
}
