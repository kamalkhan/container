<?php

namespace Bhittani\Container;

use Psr\Container\ContainerInterface;

trait ContainerAwareTrait
{
    /**
     * Container instance.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @inheritDoc
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
