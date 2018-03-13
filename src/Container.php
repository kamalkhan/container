<?php

namespace Bhittani\Container;

use Closure;
use Exception;
use ArrayAccess;
use ReflectionClass;
use ReflectionFunction;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface, ArrayAccess
{
    /**
     * Items.
     *
     * @var array[mixed]
     */
    protected $items = [];

    /**
     * Shared keys.
     *
     * @var array[string]
     */
    protected $shared = [];

    /**
     * Delegations.
     *
     * @var array[ContainerInterface]
     */
    protected $delegates = [];

    /**
     * Resolve an entry by key.
     *
     * @throws NotFoundException Entry not found.
     * @throws BindingResolutionException Entry can not be resolved.
     *
     * @param  string  $key
     * @param  array  $explicitArgs
     * @return mixed
     */
    public function get($key, array $explicitArgs = [])
    {
        $entity = $this->find($key, $found);

        if ($found) {
            if (is_callable($entity)) {
                try {
                    $entity = $this->resolveCallable($entity, $explicitArgs);
                } catch (Exception $e) {
                    throw new BindingResolutionException(sprintf(
                        'Failed to resolve callable %s while resolving %s from the container.',
                        get_class($entity), $key
                    ), 1, $e);
                }
                if (is_string($key) && in_array($key, $this->shared)) {
                    $this->items[$key] = $entity;
                }
            }

            return $entity;
        }

        if (class_exists($key)) {
            try {
                $entity = $this->resolveClass($key, $explicitArgs);
            } catch (Exception $e) {
                throw new BindingResolutionException(sprintf(
                    'Failed to resolve class %s from the container.', $key
                ), 1, $e);
            }

            return $entity;
        }

        if (interface_exists($key)) {
            throw new BindingResolutionException(sprintf(
                'Failed to resolve interface %s from the container.', $key
            ));
        }

        throw new NotFoundException(sprintf(
            '%s is not managed by the container.', $key
        ));
    }

    /**
     * Resolve a callable.
     *
     * @throws BindingResolutionException Entry can not be resolved.
     *
     * @param  callable  $callable
     * @param  array  $explicitArgs
     * @return mixed
     */
    public function call(callable $callable, array $explicitArgs = [])
    {
        return $this->resolveCallable($callable, $explicitArgs);
    }

    /**
     * Check whether an entry for a key exists.
     *
     * @param  string  $key
     * @return boolean
     */
    public function has($key)
    {
        $this->find($key, $has);

        return (bool) $has;
    }

    /**
     * Add an entry.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function add($key, $value)
    {
        $this->items[$key] = $value;

        $this->shared = array_diff($this->shared, [$key]);

        return $this;
    }

    /**
     * Add a shared entry.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function share($key, $value)
    {
        $this->items[$key] = $value;

        if (!in_array($key, $this->shared)) {
            $this->shared[] = $key;
        }

        return $this;
    }

    /**
     * Add a delegation.
     *
     * @param  ContainerInterface  $container
     * @return $this
     */
    public function delegate(ContainerInterface $container)
    {
        $this->delegates[] = $container;

        return $this;
    }

    /**
     * ArrayAccess: Alias of add.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return mixed
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }

        return $value;
    }

    /**
     * ArrayAccess: alias of has.
     *
     * @param  string  $offset
     * @return boolean
     */
    public function offsetExists($offset) {
        return $this->has($offset);
    }

    /**
     * ArrayAccess: unset a key binding.
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset) {
        $this->find($offset, $found, true);
    }

    /**
     * ArrayAccess: alias of get.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->get($offset);
    }

    /**
     * Resolve parameter bindings from the container.
     *
     * @throws BindingResolutionException Entry can not be resolved.
     * @param  array  $bindings
     * @param  array  $explicitArgs
     * @return array
     */
    protected function resolve(array $bindings, array $explicitArgs = [])
    {
        $args = [];

        foreach ($bindings as $binding) {
            if (isset($explicitArgs[$name = $binding->getName()])) {
                $args[] = $explicitArgs[$name];

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
     * @param  string  $class
     * @param  array  $explicitArgs
     * @return object
     */
    protected function resolveClass($class, array $explicitArgs = [])
    {
        $reflectedClass = new ReflectionClass($class);
        $constructor = $reflectedClass->getConstructor();

        if (is_null($constructor)) {
            return new $class;
        }

        $args = $this->resolve($constructor->getParameters(), $explicitArgs);

        return $reflectedClass->newInstanceArgs($args);
    }

    /**
     * Resolve a callable.
     *
     * @param  callable  $callable
     * @param  array  $explicitArgs
     * @return mixed
     */
    protected function resolveCallable(callable $callable, array $explicitArgs = [])
    {
        if ($callable instanceof Closure) {
            $reflectedFunction = new ReflectionFunction($callable);
        } else {
            $reflectedClass = new ReflectionClass($callable);
            if (!$reflectedClass->hasMethod('__invoke')) {
                return $callable;
            }
            $reflectedFunction = $reflectedClass->getMethod('__invoke');
        }

        $args = $this->resolve($reflectedFunction->getParameters(), $explicitArgs);

        return call_user_func_array($callable, $args);
    }

    /**
     * Find an entry including within delegates.
     *
     * @param  string  $key
     * @param  boolean  $found
     * @return mixed
     */
    protected function find($key, & $found = false, $unset = false)
    {
        if (array_key_exists($key, $this->items)) {
            $found = true;
            if ($unset) {
                unset($this->items[$key]);
                return;
            }
            return $this->items[$key];
        }

        // $delegateFound = false;

        foreach ($this->delegates as $delegate) {
            $entity = $delegate->find($key, $found, $unset);
            if ($found) {
                // To use as stack pop
                // return $entity;
                if (!$unset) {
                    return $entity;
                }
                // $delegateFound = true;
            }
        }

        // $found = $delegateFound;
    }
}
