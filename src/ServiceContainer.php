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

use Exception;
use InvalidArgumentException;

class ServiceContainer extends Container
{
    /**
     * Has the container booted.
     *
     * @var bool
     */
    protected $hasBooted = false;

    /**
     * Facades.
     *
     * @var mixed[]
     */
    protected $facades = [];

    /**
     * Providers.
     *
     * @var mixed[]
     */
    protected $providers = [];

    /**
     * Deferred macros.
     *
     * @var string[]
     */
    protected $deferredMacros = [];

    /**
     * Deferred bindings.
     *
     * @var string[]
     */
    protected $deferredBindings = [];

    /**
     * Registered providers.
     *
     * @var ServiceProviderInterface[]
     */
    protected $registeredProviders = [];

    /**
     * Create the container.
     *
     * @param array $providers
     */
    public function __construct($providers = [])
    {
        $this->addServiceProviders(
            is_array($providers) ? $providers : [$providers]
        );
    }

    /**
     * Add a facade.
     *
     * @param string $property
     * @param mixed $value
     *
     * @return mixed
     */
    public function __set($property, $value)
    {
        $this->addFacade($property, $value);

        return $value;
    }

    /**
     * Get a facade.
     *
     * @param string $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if (! isset($this->facades[$property])) {
            throw new Exception(
                sprintf(
                    'Undefined property %s::%s. Facade %s is not defined.',
                    static::class,
                    $property,
                    $property
                )
            );
        }

        $facade = $this->facades[$property];

        if (is_callable($facade)) {
            return $this->call($facade);
        }

        return $this->get($facade);
    }

    /**
     * Call a macro.
     *
     * @param string $name
     * @param mixed[] $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (isset($this->deferredMacros[$name])) {
            $this->promoteDeferredServiceProvider(
                $this->deferredMacros[$name]
            );
        }

        return parent::__call($name, $arguments);
    }

    /**
     * {@inheritDoc}
     *
     * @param  string $key
     * @param  mixed  $value
     * @param  bool   $facade
     *
     * @return ServiceContainer
     */
    public function add($key, $value, $facade = false)
    {
        if ($facade) {
            $this->addFacade($key, $key);
        }

        return parent::add($key, $value);
    }

    /**
     * {@inheritDoc}
     *
     * @param  string $key
     * @param  mixed  $value
     * @param  bool   $facade
     *
     * @return ServiceContainer
     */
    public function share($key, $value, $facade = false)
    {
        if ($facade) {
            $this->addFacade($key, $key);
        }

        return parent::share($key, $value);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|array $aliases
     * @param string $actual
     * @param bool $facade
     *
     * @return ServiceContainer
     */
    public function alias($aliases, $actual, $facade = false)
    {
        if ($facade) {
            foreach ((array) $aliases as $alias) {
                $this->addFacade($alias, $actual);
            }
        }

        return parent::alias($aliases, $actual);
    }

    /** {@inheritdoc} */
    public function get($key, array $arguments = [])
    {
        try {
            return parent::get($key, $arguments);
        } catch (NotFoundException $e) {
            if (! isset($this->deferredBindings[$key])) {
                throw $e;
            }

            $provider = $this->promoteDeferredServiceProvider(
                $this->deferredBindings[$key]
            );

            return parent::get($key, $arguments);
        }
    }

    /** {@inheritdoc} */
    public function has($key)
    {
        if (parent::has($key)) {
            return true;
        }

        return isset($this->deferredBindings[$key]);
    }

    /** {@inheritdoc} */
    public function hasMacro($key)
    {
        if (parent::hasMacro($key)) {
            return true;
        }

        return isset($this->deferredMacros[$key]);
    }

    /**
     * Add a service provider.
     *
     * @param string|ServiceProviderInterface $provider
     *
     * @param mixed ...$arguments
     *
     * @return ServiceContainer
     */
    public function addServiceProvider($provider, ...$arguments)
    {
        $this->providers[] = $provider;

        if ($this->hasBooted) {
            $provider = $this->registerServiceProvider($provider, false, $isDeferred);

            if (! $isDeferred) {
                $this->bootServiceProvider($provider, ...$arguments);
            }
        }

        return $this;
    }

    /**
     * Add service providers.
     *
     * @param string[]|ServiceProviderInterface[] $providers
     *
     * @return ServiceContainer
     */
    public function addServiceProviders(array $providers)
    {
        foreach ($providers as $provider) {
            $this->addServiceProvider($provider);
        }

        return $this;
    }

    /**
     * Add a facade.
     *
     * @param string $key
     * @param mixed $facade
     *
     * @return ServiceContainer
     */
    public function addFacade($key, $facade)
    {
        // TODO: Validate $key to be a valid variable name.
        $this->facades[$key] = $facade;

        return $this;
    }

    /**
     * Add facades.
     *
     * @param mixed[] $facades
     *
     * @return ServiceContainer
     */
    public function addFacades(array $facades)
    {
        foreach ($facades as $key => $facade) {
            $this->addFacade($key, $facade);
        }

        return $this;
    }

    /**
     * Bootstrap the service providers.
     *
     * @param mixed ...$arguments
     * @return ServiceContainer
     */
    public function bootstrap(...$arguments)
    {
        if ($this->hasBooted) {
            return $this;
        }

        foreach ($this->providers as $provider) {
            $this->registerServiceProvider($provider);
        }

        $this->hasBooted = true;

        foreach ($this->registeredProviders as $provider) {
            $this->bootServiceProvider($provider, ...$arguments);
        }

        return $this;
    }

    /**
     * Register the service provider.
     *
     * @param string|ServiceProviderInterface $class
     * @param bool $force
     * @param null|bool $isDeferred
     *
     * @return ServiceProviderInterface
     */
    protected function registerServiceProvider($class, $force = false, &$isDeferred = null)
    {
        $isDeferred = $isDeferred ?: false;

        $provider = $class;
        $isConstructedClass = false;

        if (! is_string($class)) {
            $isConstructedClass = true;
            $class = get_class($class);
        } elseif (class_exists($class)) {
            $provider = new $class; //$this->get($class);
        }

        if (! ($provider instanceof ServiceProviderInterface)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s is not a valid service provider',
                    $class
                )
            );
        }

        if (! $force && $this->isServiceProviderDeferrable($provider)) {
            $isDeferred = true;

            return $this->deferServiceProvider($provider, $isConstructedClass);
        }

        $provider->register($this);

        return $this->registeredProviders[$class] = $provider;
    }

    /**
     * Boot the service provider.
     *
     * @param ServiceProviderInterface $provider
     *
     * @return ServiceProviderInterface
     */
    protected function bootServiceProvider(ServiceProviderInterface $provider, ...$arguments)
    {
        if ($this->isServiceProviderBootable($provider)) {
            $provider->boot($this, ...$arguments);
        }

        return $provider;
    }

    /**
     * Defer the service provider.
     *
     * @param ServiceProviderInterface $provider
     * @param bool $useInstance
     *
     * @return ServiceProviderInterface
     */
    protected function deferServiceProvider(ServiceProviderInterface $provider, $useInstance = false)
    {
        if (! $this->isServiceProviderDeferrable($provider)) {
            return $provider;
        }

        $classOrProvider = $useInstance ? $provider : get_class($provider);

        foreach ($provider->getBindings() as $key => $binding) {
            $this->deferredBindings[$binding] = $classOrProvider;

            if (is_string($key)) {
                $this->addFacade($key, $binding);
            }
        }

        foreach ($provider->getMacros() as $macro) {
            $this->deferredMacros[$macro] = $classOrProvider;
        }

        return $provider;
    }

    /**
     * Promote the deferred service provider.
     *
     * @param string|ServiceProviderInterface $classOrProvider
     *
     * @return ServiceProviderInterface
     */
    protected function promoteDeferredServiceProvider($classOrProvider)
    {
        $provider = $this->registerServiceProvider($classOrProvider, true);

        if ($this->hasBooted) {
            $this->bootServiceProvider($provider);
        }

        return $provider;
    }

    /**
     * Tell whether the service provider is bootable.
     *
     * @param ServiceProviderInterface $provider
     *
     * @return bool
     */
    protected function isServiceProviderBootable(ServiceProviderInterface $provider)
    {
        return $provider instanceof BootableServiceProviderInterface;
    }

    /**
     * Tells whether the service provider is deferrable.
     *
     * @param ServiceProviderInterface $provider
     *
     * @return bool
     */
    protected function isServiceProviderDeferrable(ServiceProviderInterface $provider)
    {
        return $provider instanceof DeferrableServiceProviderInterface
            && ($provider->getBindings() || $provider->getMacros());
    }
}
