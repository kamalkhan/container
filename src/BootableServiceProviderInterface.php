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

interface BootableServiceProviderInterface extends ServiceProviderInterface
{
    /**
     * Bootstrap the service.
     *
     * @param \Psr\Container\ContainerInterface $container
     */
    public function boot($container);
}
