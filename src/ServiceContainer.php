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
    use Macroable {
        __call as macroCall;
    }

    protected $hasBooted = false;

    protected $facades = [];
    protected $providers = [];
    protected $deferredMacros = [];
    protected $deferredBindings = [];
    protected $registeredProviders = [];

    public function __set($property, $value)
    {
        return $this->addFacade($property, $value);
    }

    public function __get($property)
    {
        if (! isset($this->facades[$property])) {
            throw new Exception(
                sprintf(
                    'Undefined property %s::%s. Facade %s is not defined.',
                    get_class(),
                    $property,
                    $property
                )
            );
        }

        $facade = $this->facades[$property];

        if (is_string($facade)) {
            return $this->get($facade);
        }

        if (is_callable($facade)) {
            return $this->call($facade);
        }

        return $facade;
    }

    public function __call($method, $parameters)
    {
        if ((! static::hasMacro($method))
            && isset($this->deferredMacros[$method])
        ) {
            $this->promoteDeferredServiceProvider($this->deferredMacros[$method]);
        }

        return static::macroCall($method, $parameters);
    }

    public function __invoke()
    {
        call_user_func_array([$this, 'bootstrap'], func_get_args());
    }

    public function addServiceProvider($provider)
    {
        $this->providers[] = $provider;

        if ($this->hasBooted) {
            $provider = $this->registerServiceProvider($provider, false, $isDeferred);

            if (! $isDeferred) {
                $this->bootServiceProvider($provider);
            }
        }
    }

    public function addServiceProviders(array $providers)
    {
        foreach ($providers as $provider) {
            $this->addServiceProvider($provider);
        }
    }

    public function addFacade($key, $facade)
    {
        $this->facades[$key] = $facade;
    }

    public function addFacades(array $facades)
    {
        foreach ($facades as $key => $facade) {
            $this->addFacade($key, $facade);
        }
    }

    public function get($key, array $explicitArgs = [])
    {
        try {
            if (! $this->has($key)) {
                throw new NotFoundException;
            }

            return parent::get($key, $explicitArgs);
        } catch (NotFoundException $e) {
            if (isset($this->deferredBindings[$key])) {
                $provider = $this->promoteDeferredServiceProvider(
                    $this->deferredBindings[$key]
                );
            }

            return parent::get($key, $explicitArgs);
        }
    }

    public function bootstrap()
    {
        if ($this->hasBooted) {
            return;
        }

        foreach ($this->providers as $provider) {
            $this->registerServiceProvider($provider);
        }

        foreach ($this->registeredProviders as $provider) {
            $this->bootServiceProvider($provider);
        }

        $this->hasBooted = true;
    }

    protected function registerServiceProvider($class, $force = false, & $isDeferred = null)
    {
        $isDeferred = $isDeferred ?: false;

        $provider = $class;

        if (! is_string($class)) {
            $class = get_class($class);
        } elseif (class_exists($class)) {
            $provider = $this->get($class);
        }

        if (! ($provider instanceof ServiceProviderInterface)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s is not a valid service provider',
                    $class
                )
            );
        }

        if ($force || (! $this->isServiceProviderDeferred($provider))) {
            $provider->setContainer($this);
            $provider->register();

            return $this->registeredProviders[$class] = $provider;
        }

        $isDeferred = true;

        return $this->deferServiceProvider($provider);
    }

    protected function bootServiceProvider(ServiceProviderInterface $provider)
    {
        return $provider->boot();
    }

    protected function deferServiceProvider(ServiceProviderInterface $provider)
    {
        $class = get_class($provider);

        foreach ($provider->getBindings() as $key => $binding) {
            $this->deferredBindings[$binding] = $class;
            if (is_string($key)) {
                $this->addFacade($key, $binding);
            }
        }

        foreach ($provider->getMacros() as $macro) {
            $this->deferredMacros[$macro] = $class;
        }

        return $provider;
    }

    protected function isServiceProviderDeferred(ServiceProviderInterface $provider)
    {
        return (bool) $provider->getBindings() || (bool) $provider->getMacros();
    }

    protected function promoteDeferredServiceProvider($class)
    {
        $provider = $this->registerServiceProvider($class, true);

        if ($this->hasBooted) {
            $this->bootServiceProvider($provider);
        }

        return $provider;
    }
}
