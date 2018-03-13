<?php

namespace Bhittani\Container;

use Psr\Container\ContainerInterface;

interface ContainerAwareInterface
{
    /**
     * @return ContainerInterface
     */
    public function getContainer();

    /**
     * @param  ContainerInterface  $container
     */
    public function setContainer(ContainerInterface $container);
}
