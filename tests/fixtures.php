<?php

namespace Bhittani\Container\Fixtures;

use Psr\Container\ContainerInterface;
use Bhittani\Container\ServiceProvider;

class Foobar {
    function __construct($foo) {}
}

class WithoutConstructor {}

class WithZeroParams {
    function __construct() {}
}

class WithOneParam {
    function __construct(WithZeroParams $w0) {}
}

class WithTwoParams {
    function __construct(WithZeroParams $w0, WithOneParam $w1) {}
}

class WithManagedParams {
    function __construct(Foobar $foo, WithOneParam $w1) {}
}

class WithOptionalParams {
    var $a;
    function __construct(WithTwoParams $w2, $a = 'b') {
        $this->a = $a;
    }
}

class Invocable {
    function __invoke() { return "invoke"; }
}

class InvocableWithParams {
    function __invoke(WithZeroParams $w0, WithOneParam $w1) {
        return "invoke + params";
    }
}

interface Contract {
    function contract();
}

class Concrete implements Contract {
    function __construct(WithZeroParams $w0, WithOneParam $w1) {}
    function contract() {}
}

class ContractParam {
    public $concrete;
    function __construct(Contract $concrete) {
        $this->concrete = $concrete;
    }
}

class MixedParams {
    function __construct(WithTwoParams $w2, $foo, $bar = null) {}
}

// Serviceable

class FooService {}

class TestServiceProvider extends ServiceProvider {
    static $calls = [];
    function boot(ContainerInterface $container) {
        static::$calls[] = 'boot:'.static::class;
    }
    function register(ContainerInterface $container) {
        static::$calls[] = 'register:'.static::class;
    }
}

class FooServiceProvider extends TestServiceProvider {}

class BarServiceProvider extends TestServiceProvider {}

class DeferredBindingServiceProvider extends ServiceProvider {
    static $booted = 0;
    static $registered = 0;
    var $provides = ['foo'];
    function boot(ContainerInterface $container) {
        static::$booted++;
    }
    function register(ContainerInterface $container) {
        static::$registered++;
        $container->add('foo', 'bar');
    }
}

class DeferredFacadeServiceProvider extends ServiceProvider {
    static $booted = 0;
    static $registered = 0;
    var $provides = ['foo' => 'foo'];
    public function boot(ContainerInterface $container) {
        static::$booted++;
    }
    public function register(ContainerInterface $container) {
        static::$registered++;
        $container->add('foo', 'bar');
    }
}

class DeferredMacroServiceProvider extends ServiceProvider {
    static $booted = 0;
    static $registered = 0;
    var $macros = ['foo'];
    public function boot(ContainerInterface $container) {
        static::$booted++;
    }
    public function register(ContainerInterface $container) {
        static::$registered++;
        $container->macro('foo', function ($foo) {
            return $foo;
        });
    }
}
