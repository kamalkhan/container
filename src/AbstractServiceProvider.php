<?php

namespace Bhittani\Container;

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    protected $macros = [];
    protected $provides = [];

    /**
     * @inheritDoc
     */
    public function boot() {}

    /**
     * @inheritDoc
     */
    public function register() {}

    /**
     * @inheritDoc
     */
    public function getBindings()
    {
        return $this->provides;
    }

    /**
     * @inheritDoc
     */
    public function getMacros()
    {
        return $this->macros;
    }
}
