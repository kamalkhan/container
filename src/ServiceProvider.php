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

abstract class ServiceProvider implements
    ServiceProviderInterface,
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

    /** {@inheritdoc} */
    public function boot($container)
    {
        // Extended classes may implement this method.
    }

    /** {@inheritdoc} */
    public function register($container)
    {
        // Extended classes may implement this method.
    }

    /** {@inheritdoc} */
    public function getBindings()
    {
        return $this->provides;
    }

    /** {@inheritdoc} */
    public function getMacros()
    {
        return $this->macros;
    }
}
