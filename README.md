# PSR-11 Container

[![Build Status][icon-status]][link-status]
[![Packagist Downloads][icon-downloads]][link-downloads]
[![License][icon-license]](LICENSE.md)

PSR-11 dependency injection container implementation with automatic resolution, service providers, facades and macros. This package does not require any external dependencies.

- [Install](#install)
- [Usage](#usage)
  - [PSR-11 Implementation](#psr-11-implementation)
  - [Container](#container)
  - [Binding resolution](#binding-resolution)
  - [Automatic dependency resolution](#automatic-dependency-resolution)
  - [Interface resolution](#interface-resolution)
  - [Callable resolution](#callable-resolution)
  - [Custom parameter resolution](#custom-parameter-resolution)
  - [Factory bindings](#factory-bindings)
  - [Shared bindings](#shared-bindings)
  - [Delegates](#delegates)
  - [Service providers](#service-providers)
  - [Facades](#facades)
  - [Macros](#macros)
  - [Deferred Service Providers](#deferred-service-providers)
- [Changelog](#changelog)
- [Testing](#testing)
- [Contributing](#contributing)
- [Security](#security)
- [Inspiration](#inspiration)
- [Credits](#credits)
- [License](#license)

## Install

You may install this package using [composer][link-composer].

```shell
$ composer require bhittani/container --prefer-dist
```

## Usage

### PSR-11 Implementation

This package implements the [PSR-11](https://github.com/php-fig/container) container interface, hence, you can easily swap any existing implementation with the container provided in this package.

### Container

In its simplest form, the container stores key value pairs so that it can be accessed later during your application life cycle.

```php
<?php

require_once __DIR__ . '/vendor/autload.php';

$container = new \Bhittani\Container\Container;

$container->add('foo', 'bar');

echo $container->get('foo'); // 'bar'
```

### Binding resolution

Practically, a dependency injection container is more useful by storing class factories/instances so that they are automatically resolved.

```php
<?php

require_once __DIR__ . '/vendor/autload.php';

class FooDatabase
{
    // ...
}

$container = new Bhittani\Container\Container;

$container->add(FooDatabase::class, new FooDatabase);

$db = $container->get(FooDatabase::class);
```

The key being used `FooDatabase` is significant. This key will act as a look-up against any class typehints during resolution attempts of binding parameters (in class constructors, methods, closures, callables, ..., etc).

Still confused? Lets take a closer look at a practical example.

```php
<?php

require_once __DIR__ . '/vendor/autload.php';

class FooDatabase
{
    // ...
}

class Query
{
    protected $db;

    public function __construct(FooDatabase $db)
    {
      $this->db = $db;
    }
}

$container = new Bhittani\Container\Container;

$container->add(FooDatabase::class, new FooDatabase);

$query = $container->get(Query::class); // Query
```

Here, `$db` is automatically resolved by the container as it is type hinted with the 'FooDatabase' class which the container is aware of.

### Automatic dependency resolution

Binding resolution is all handy and dandy but we can do much better and improve on our first iteration.

If we take a closer look at the previous code example, we see that we bind the `FooDatabase` class explicitly into the container but the `Query` class is implicitly resolved without any explicit binding.

This means, we should also be able to resolve the `FooDatabase` class implicitly.

Let's apply our first refactor.

```php
<?php

require_once __DIR__ . '/vendor/autload.php';

class FooDatabase
{
    // ...
}

class Query
{
    protected $db;

    public function __construct(FooDatabase $db)
    {
        $this->db = $db;
    }
}

$container = new Bhittani\Container\Container;

$query = $container->get(Query::class); // Query
```

In case you didn't notice, the code line `$container->add(FooDatabase::class, new FooDatabase);` is completely removed as no binding is required.

How does this work? Lets go behind the scenes for a moment to see what actually happens.

When you call the `get` method on the container,

1. The container identifies the key as a class that exists.
2. It takes a peek into the constructor parameters and notices a parameter type-hinted as the class `FooDatabase`.
3. In order to resolve this parameter, it repeats step 1 and 2 using the type-hint as the key.
4. It doesn't see any constructor for the `FooDatabase` class so it instantiates it and uses that instance to instantiate the `Query` class.

> A binding will take precedence over a new instantiation.

### Interface resolution

Wouldn't it be nice if we could implement to an interface so that we could easily swap the underlying implementation?

```php
<?php

require_once __DIR__ . '/vendor/autload.php';

interface DatabaseInterface
{
    // ...
}

class FooDatabase implements DatabaseInterface
{
    // ...
}

class BarDatabase implements DatabaseInterface
{
    // ...
}

class Query
{
    public $db;
    
    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }
}

$container = new Bhittani\Container\Container;

$container->add(DatabaseInterface::class, new FooDatabase);

$query = $container->get(Query::class);

echo $query->db instanceof FooDatabase; // true

$container->add(DatabaseInterface::class, new BarDatabase);

$query = $container->get(Query::class);

echo $query->db instanceof BarDatabase; // true
```

We have easily swapped the underlying database implementation from `FooDatabase` to `BarDatabase`.

### Callable resolution

To resolve a callable/closure, we can invoke it directly.

```php
$container = new Bhittani\Container\Container;

class Acme
{
    // ...
}

$container->call(function (Acme $acme) {
    echo $acme instanceof Acme; // true
});
```

### Custom parameter resolution

We can resolve entities that require custom arguments in two ways.

1. Binding the custom argument into the container.
2. Passing explicit arguments.

```php
class Acme
{
    public $foo;
    
    public function __construct($foo)
    {
        $this->foo = $foo;
    }
}

$container = new Bhittani\Container\Container;

$container->add('foo', 'bar');

$acme = $container->get(Acme::class);

echo $acme->foo; // bar

$acme = $container->get(Acme::class, ['foo' => 'baz']);

echo $acme->foo; // baz
```

> Explicit arguments will take precedence over bindings.

### Factory bindings

Factory bindings allow lazy loading of your instances. Which means that resolution will only occur when it is needed.

```php
<?php

require_once __DIR__ . '/vendor/autload.php';

interface DatabaseInterface
{
    // ...
}

class FooDatabase implements DatabaseInterface
{
    // ...
}

class Query
{
    public function __construct(DatabaseInterface $db)
    {
        // ...
    }
}

$container = new Bhittani\Container\Container;

$container->add(DatabaseInterface::class, function () {
    return new FooDatabase;
});

$query = $container->get(Query::class); // will trigger the factory closure above.
```

This way, you can add as many bindings as you want in your container but only trigger/resolve them when needed. Hence, lazy loaded.

### Shared bindings

Shared bindings allow the same instance to be resolved when accessed instead of a new instance every time it is needed.

```php
$container = new Bhittani\Container\Container;

// This will be shared as we are not using a factory.
$container->add(DatabaseInterface::class, new FooDatabase);

// This will be shared as we are using the method 'share' explicitly.
$container->share(DatabaseInterface::class, function () {
    return new FooDatabase;
});

// This will NOT be shared as we are using a factory.
$container->add(DatabaseInterface::class, function () {
    return new FooDatabase;
});
```

### Delegates

Delegated containers serve as fallback containers that are looked-up for binding resolutions when it can not be found in the container.

```php
use Bhittani\Container\Container;

$container = new Container;

$container->has('foo'); // false

$delegateContainer = new Container; // Or any PSR-11 container.

$delegateContainer->add('foo', 'bar');

$container->delegate($delegateContainer);

$container->has('foo'); // true
```

### Service providers

This package also ships with a service provider container which allows registering of service providers (Think of [laravel](https://laravel.com) service providers) in order to have a smooth and easy application development process.

In order to make use of service providers, we will work with a `ServiceContainer` instead of a simple `Container`.

```php
use Bhittani\Container\ServiceContainer;
use Bhittani\Container\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function boot($container)
    {
        // This method will be called when all service providers are registered.
    }
    
    public function register($container)
    {
        $container->share(DatabaseInterface::class, function () {
            return new SqliteDatabase;
        });
    }
}

$container = new ServiceContainer;

$container->addServiceProvider(DatabaseServiceProvider::class);

$container->bootstrap();

$db = $container->get(DatabaseInterface::class);

echo $db instanceof SqliteDatabase; // true
```

### Facades

Facades extend the container by assigning custom properties onto the service container. When accessed, it will be resolved out of the service container.

```php
use Bhittani\Container\ServiceContainer;

$container = new ServiceContainer;

$container->share(DatabaseInterface::class, function () {
    return new SqliteDatabase;
});

$container->db = DatabaseInterface::class;

echo $container->db instanceof SqliteDatabase; // true
```

### Macros

Macros extend the container by assigning custom methods onto the service container.

```php
use Bhittani\Container\ServiceContainer;

$container = new ServiceContainer;

$container->share(DatabaseInterface::class, function () {
    return new SqliteDatabase;
});

$container->macro('query', function ($sql) {
    // $this will be set to the underlying ServiceContainer.
    return $this->get(DatabaseInterface::class)->query($sql);
});

echo $container->query('SELECT * FROM users'); // Invokes the 'query' macro.
```

### Deferred Service Providers

Service providers can be deferred so that services can be lazy loaded.

```php
use Bhittani\Container\ServiceContainer;
use Bhittani\Container\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    // A non empty $provides array will defer this service provider.
    protected $provides = [
        DatabaseInterface::class,
        // If setting a facade, use the facade key as the index.
        // 'db' => DatabaseInterface::class,
    ];

    // A non empty $macros array will defer this service provider as well.
    protected $macros = [
        'query',
    ];

    public function register($container)
    {
        $container->share(DatabaseInterface::class, function () {
            return new SqliteDatabase;
        });

        $container->macro('query', function ($sql) {
            return $this->get(DatabaseInterface::class)->query($sql);
        });
    }
}

$container = new ServiceContainer;

$container->addServiceProvider(DatabaseServiceProvider::class);

$container->bootstrap();

// This will register and boot the service provider.
$container->query('SELECT * FROM users');
```
Using deferred service providers is an efficient way to build up your application as these services will be lazy loaded and act as plug and play while having a minimimum impact on your application performance.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed.

## Testing

```shell
git clone https://github.com/kamalkhan/container

cd container

composer install

composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email `shout@bhittani.com` instead of using the issue tracker.

## Inspiration

- [league/container](http://container.thephpleague.com)
- [Laravel](https://laravel.com)

## Credits

- [Kamal Khan](http://bhittani.com)
- [All Contributors](https://github.com/kamalkhan/container/contributors)

## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.

<!--Status-->
[icon-status]: https://github.com/kamalkhan/container/workflows/Test/badge.svg
[link-status]: https://github.com/kamalkhan/container/actions
<!--Downloads-->
[icon-downloads]: https://img.shields.io/packagist/dt/bhittani/container.svg?style=flat-square
[link-downloads]: https://packagist.org/packages/bhittani/container
<!--License-->
[icon-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
<!--composer-->
[link-composer]: https://getcomposer.org
