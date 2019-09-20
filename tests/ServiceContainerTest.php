<?php

namespace Bhittani\Container;

use PHPUnit\Framework\TestCase;

class ServiceContainerTest extends TestCase
{
    protected $container;

    function setUp()
    {
        Fixtures\TestServiceProvider::$calls = [];

        Fixtures\DeferredBindingServiceProvider::$booted = 0;
        Fixtures\DeferredBindingServiceProvider::$registered = 0;

        Fixtures\DeferredFacadeServiceProvider::$booted = 0;
        Fixtures\DeferredFacadeServiceProvider::$registered = 0;

        Fixtures\DeferredMacroServiceProvider::$booted = 0;
        Fixtures\DeferredMacroServiceProvider::$registered = 0;

        $this->container = new ServiceContainer;
    }

    /** @test */
    function it_extends_the_container()
    {
        $this->assertInstanceOf(Container::class, $this->container);
    }

    /** @test */
    function it_accepts_service_providers_for_bootstrapping()
    {
        $this->assertEmpty(Fixtures\TestServiceProvider::$calls);

        $this->container->addServiceProvider(Fixtures\FooServiceProvider::class);
        $this->container->addServiceProvider(Fixtures\BarServiceProvider::class);

        $this->container->bootstrap();

        $this->assertEquals([
            'register:'.Fixtures\FooServiceProvider::class,
            'register:'.Fixtures\BarServiceProvider::class,
            'boot:'.Fixtures\FooServiceProvider::class,
            'boot:'.Fixtures\BarServiceProvider::class,
        ], Fixtures\TestServiceProvider::$calls);
    }

    /** @test */
    function it_bootstraps_at_most_once()
    {
        $this->assertEmpty(Fixtures\TestServiceProvider::$calls);

        $this->container->addServiceProvider(Fixtures\FooServiceProvider::class);
        $this->container->addServiceProvider(Fixtures\BarServiceProvider::class);

        $this->container->bootstrap();

        $this->assertEquals([
            'register:'.Fixtures\FooServiceProvider::class,
            'register:'.Fixtures\BarServiceProvider::class,
            'boot:'.Fixtures\FooServiceProvider::class,
            'boot:'.Fixtures\BarServiceProvider::class,
        ], Fixtures\TestServiceProvider::$calls);

        $this->container->bootstrap();

        $this->assertEquals([
            'register:'.Fixtures\FooServiceProvider::class,
            'register:'.Fixtures\BarServiceProvider::class,
            'boot:'.Fixtures\FooServiceProvider::class,
            'boot:'.Fixtures\BarServiceProvider::class,
        ], Fixtures\TestServiceProvider::$calls);
    }

    /** @test */
    function service_providers_with_explicit_bindings_are_deferred()
    {
        $this->assertEquals(0, Fixtures\DeferredBindingServiceProvider::$booted);
        $this->assertEquals(0, Fixtures\DeferredBindingServiceProvider::$registered);

        $this->container->addServiceProvider(Fixtures\DeferredBindingServiceProvider::class);

        $this->container->bootstrap();

        $this->assertEquals(0, Fixtures\DeferredBindingServiceProvider::$booted);
        $this->assertEquals(0, Fixtures\DeferredBindingServiceProvider::$registered);

        $this->assertEquals('bar', $this->container->get('foo'));

        $this->assertEquals(1, Fixtures\DeferredBindingServiceProvider::$booted);
        $this->assertEquals(1, Fixtures\DeferredBindingServiceProvider::$registered);
    }

    /** @test */
    function service_providers_with_explicit_facades_are_deferred()
    {
        $this->assertEquals(0, Fixtures\DeferredFacadeServiceProvider::$booted);
        $this->assertEquals(0, Fixtures\DeferredFacadeServiceProvider::$registered);

        $this->container->addServiceProvider(Fixtures\DeferredFacadeServiceProvider::class);

        $this->assertFalse($this->container->has('foo'));

        $this->container->bootstrap();

        $this->assertTrue($this->container->has('foo'));

        $this->assertEquals(0, Fixtures\DeferredFacadeServiceProvider::$booted);
        $this->assertEquals(0, Fixtures\DeferredFacadeServiceProvider::$registered);

        $this->assertEquals('bar', $this->container->foo);

        $this->assertEquals(1, Fixtures\DeferredFacadeServiceProvider::$booted);
        $this->assertEquals(1, Fixtures\DeferredFacadeServiceProvider::$registered);
    }

    /** @test */
    function service_providers_with_explicit_macros_are_deferred()
    {
        $this->assertEquals(0, Fixtures\DeferredMacroServiceProvider::$booted);
        $this->assertEquals(0, Fixtures\DeferredMacroServiceProvider::$registered);

        $this->container->addServiceProvider(Fixtures\DeferredMacroServiceProvider::class);

        $this->assertFalse($this->container->hasMacro('foo'));

        $this->container->bootstrap();

        $this->assertTrue($this->container->hasMacro('foo'));

        $this->assertEquals(0, Fixtures\DeferredMacroServiceProvider::$booted);
        $this->assertEquals(0, Fixtures\DeferredMacroServiceProvider::$registered);

        $this->assertEquals('bar', $this->container->foo('bar'));

        $this->assertEquals(1, Fixtures\DeferredMacroServiceProvider::$booted);
        $this->assertEquals(1, Fixtures\DeferredMacroServiceProvider::$registered);
    }
}
