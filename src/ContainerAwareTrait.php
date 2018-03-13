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
