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
use Exception;
use ArrayAccess;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface, ArrayAccess
{
    use Macroable;

    /**
     * Items.
     *
     * @var mixed[]
     */
    protected $items = [];

    /**
     * Shared keys.
     *
     * @var string[]
     */
    protected $shared = [];

    /**
     * Aliases.
     *
     * @var string[]
     */
    protected $aliases = [];

    /**
     * Delegations.
     *
     * @var ContainerInterface[]
     */
    protected $delegates = [];

    /**
     * Ignore delegated items.
     *
     * @var string[]
     */
    protected $ignoreInDelegates = [];

    /**
     * Get an item.
     *
     * @param  string $key
     * @param  array  $arguments
     *
     * @throws NotFoundException if the key is not managed by the container.
     * @throws BindingResolutionException if a binding can not be resolved from the container.
     *
     * @return mixed
     */
    public function get($key, array $arguments = [])
    {
        if (isset($this->aliases[$key])) {
            return $this->get($this->aliases[$key]);
        }

        $entity = $this->find($key);

        if ($entity) {
            if (! $entity instanceof Closure) {
                return $entity;
            }

            try {
                $entity = $this->resolveCallable($entity, $arguments);
            } catch (Exception $e) {
                throw new BindingResolutionException(
                    "Failed to resolve {$key} from the container.", 0, $e
                );
            }

            if (in_array($key, $this->shared)) {
                $this->items[$key] = $entity;
            }

            return $entity;
        }

        if (class_exists($key)) {
            try {
                return $this->resolveClass($key, $arguments);
            } catch (Exception $e) {
                throw new NotFoundException("Failed to resolve class {$key}.", 0, $e);
            }
        }

        if (interface_exists($key)) {
            throw new NotFoundException("Failed to resolve interface {$key}.");
        }

        throw new NotFoundException(
            "{$key} is not managed by the container."
        );
    }

    /**
     * Resolve a callable.
     *
     * @param  callable $callable
     * @param  array    $arguments
     *
     * @throws BindingResolutionException Entry can not be resolved.
     *
     * @return mixed
     */
    public function call(callable $callable, array $arguments = [])
    {
        return $this->resolveCallable($callable, $arguments);
    }

    /**
     * Tell whether an item exists.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function has($key)
    {
        if (isset($this->aliases[$key])) {
            $key = $this->aliases[$key];
        }

        return (bool) $this->find($key);
    }

    /**
     * Add an item.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return Container
     */
    public function add($key, $value)
    {
        $this->remove($key);
        $this->items[$key] = $value;

        return $this;
    }

    /**
     * Add a shared item.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return Container
     */
    public function share($key, $value)
    {
        $this->add($key, $value);
        $this->shared[] = $key;

        return $this;
    }

    /**
     * Add a singleton.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return Container
     */
    public function singleton($key, $value)
    {
        return $this->share($key, $value);
    }

    /**
     * Alias an item.
     *
     * @param string|array $aliases
     * @param string $actual
     *
     * @return Container
     */
    public function alias($aliases, $actual)
    {
        foreach ((array) $aliases as $alias) {
            $this->remove($alias);
            $this->aliases[$alias] = $actual;
        }

        return $this;
    }

    /**
     * Remove an item.
     *
     * @param string $key
     *
     * @return Container
     */
    public function remove($key)
    {
        unset($this->items[$key]);

        $this->shared = array_diff($this->shared, [$key]);

        if (! in_array($key, $this->ignoreInDelegates)) {
            $this->ignoreInDelegates[] = $key;
        }

        return $this;
    }

    /**
     * Add a delegation.
     *
     * @param  ContainerInterface $container
     *
     * @return Container
     */
    public function delegate(ContainerInterface $container)
    {
        $this->delegates[] = $container;

        return $this;
    }

    /**
     * ArrayAccess: Alias of add.
     *
     * @param  string $offset
     * @param  mixed  $value
     *
     * @return mixed
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $indexes = array_filter($this->items, function ($key) {
                return is_int($key);
            }, ARRAY_FILTER_USE_KEY);

            rsort($indexes);

            $offset = array_shift($indexes) ?: 0;
        }

        $this->add($offset, $value);

        return $value;
    }

    /**
     * ArrayAccess: Alias of has.
     *
     * @param  string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * ArrayAccess: Alias of remove.
     *
     * @param  string $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * ArrayAccess: Alias of get.
     *
     * @param  string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Resolve parameter bindings.
     *
     * @param  ReflectionParameter[] $bindings
     * @param  mixed[] $arguments
     *
     * @throws NotFoundException If the bindings can not be resolved.
     *
     * @return mixed[]
     */
    protected function resolve(array $bindings, array $arguments = [])
    {
        $args = [];

        foreach ($bindings as $binding) {
            if (isset($arguments[$name = $binding->getName()])) {
                $args[] = $arguments[$name];

                continue;
            }

            $key = is_null($bindingClass = $binding->getClass())
                ? $name
                : $bindingClass->getName();

            try {
                $args[] = $this->get($key);
            } catch (Exception $e) {
                if (! $binding->isDefaultValueAvailable()) {
                    throw $e;
                }

                $args[] = $binding->getDefaultValue();
            }
        }

        return $args;
    }

    /**
     * Resolve a class.
     *
     * @param  string $class
     * @param  mixed[]  $arguments
     *
     * @return object
     */
    protected function resolveClass($class, array $arguments = [])
    {
        $reflectedClass = new ReflectionClass($class);
        $constructor = $reflectedClass->getConstructor();

        return is_null($constructor) ? new $class : $reflectedClass->newInstanceArgs(
            $this->resolve($constructor->getParameters(), $arguments)
        );
    }

    /**
     * Resolve a callable.
     *
     * @param  callable $callable
     * @param  mixed[]    $arguments
     *
     * @return mixed
     */
    protected function resolveCallable(callable $callable, array $arguments = [])
    {
        $reflectedFunction = (is_string($callable) || $callable instanceof Closure)
            ? new ReflectionFunction($callable)
            : (new ReflectionClass($callable))->getMethod('__invoke');

        return $callable(...$this->resolve($reflectedFunction->getParameters(), $arguments));
    }

    /**
     * Find an item including within delegates.
     *
     * @param  string  $key
     *
     * @return mixed
     */
    protected function find($key)
    {
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }

        if (! in_array($key, $this->ignoreInDelegates)) {
            foreach ($this->delegates as $delegate) {
                if ($delegate->has($key)) {
                    return $delegate->get($key);
                }
            }
        }
    }
}
