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

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    protected $macros = [];
    protected $provides = [];

    /**
     * @inheritDoc
     */
    public function boot()
    {
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
    }

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
