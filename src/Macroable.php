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

use Closure;
use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;

trait Macroable
{
    /**
     * Macros.
     *
     * @var callable[]
     */
    protected $macros = [];

    /**
     * Call a macro.
     *
     * @param string  $method
     * @param mixed[] $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if ($this->hasMacro($name)) {
            $macro = $this->macros[$name];

            if ($macro instanceof Closure) {
                $macro->bindTo($this);
            }

            return $macro(...$arguments);
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s().',
            static::class,
            $name
        ));
    }

    /**
     * Tell whether the given macro exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasMacro($name)
    {
        return isset($this->macros[$name]);
    }

    /**
     * Bind a macro.
     *
     * @param string $name
     * @param callable $macro
     *
     * @return $this
     */
    public function macro($name, callable $macro)
    {
        $this->macros[$name] = $macro;

        return $this;
    }

    /**
     * Mix methods of another object into the class as macros.
     *
     * @param object $mixin
     *
     * @return $this
     */
    public function mixin($mixin, $override = true)
    {
        $methods = (new ReflectionClass($mixin))
            ->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $name = $method->getName();

            if (! $this->hasMacro($name) || $override) {
                $this->macro($name, [$mixin, $name]);
            }
        }

        return $this;
    }
}
