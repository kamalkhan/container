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

interface ServiceProviderInterface extends ContainerAwareInterface
{
    /**
     * Bootstrap the service.
     */
    public function boot();

    /**
     * Register any bindings, facades, macros and/or mixins with the container.
     */
    public function register();

    /**
     * Get all the bindings this provider registers.
     *
     * If this returns a non empty array
     * it will defer the service provider.
     *
     * @return array[string]
     */
    public function getBindings();

    /**
     * Get all the macros this provider offers.
     *
     * If this returns a non empty array
     * it will defer the service provider.
     *
     * @return array[string]
     */
    public function getMacros();
}
