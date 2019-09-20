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

interface BootableServiceProviderInterface extends ServiceProviderInterface
{
    /**
     * Bootstrap the service.
     *
     * @param ContainerInterface $container
     */
    public function boot(ContainerInterface $container);
}
