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

interface ServiceProviderInterface
{
    /**
     * Register any bindings, facades, macros and/or mixins with the container.
     *
     * @param ContainerInterface $container
     */
    public function register(ContainerInterface $container);
}
