<?php

namespace Bhittani\Container;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;

/**
 * @see https://github.com/illuminate/support/blob/master/Traits/Macroable.php
 * @see https://github.com/spatie/macroable
 */

trait Macroable
{
    protected static $macros = [];

    /**
     * Register a custom macro.
     *
     * @param  string  $name
     * @param  object|callable  $macro
     */
    public static function macro($name, $macro)
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Mix another object into the class.
     *
     * @param  object  $mixin
     */
    public static function mixin($mixin)
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            $method->setAccessible(true);

            static::macro($method->name, $method->invoke($mixin));
        }
    }

    /**
     * Checks whether the given macro is available.
     *
     * @param  string  $name
     */
    public static function hasMacro($name)
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Call a defined macro as static.
     *
     * @param  string  $method
     * @param  array[mixed]  $parameters
     */
    public static function __callStatic($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        if (static::$macros[$method] instanceof Closure) {
            return call_user_func_array(
                Closure::bind(static::$macros[$method], null, static::class),
                $parameters
            );
        }

        return call_user_func_array(static::$macros[$method], $parameters);
    }

    /**
     * Call a defined macro.
     *
     * @param  string  $method
     * @param  array[mixed]  $parameters
     */
    public function __call($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            return call_user_func_array($macro->bindTo($this, static::class), $parameters);
        }

        return call_user_func_array($macro, $parameters);
    }
}
