<?php

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
