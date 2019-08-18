<?php

namespace Bhittani\Container;

use PHPUnit\Framework\TestCase;
use Bhittani\Container\Fixtures\FooService;
use Bhittani\Container\Fixtures\FooServiceProvider;
use Bhittani\Container\Fixtures\DeferredMacroServiceProvider;
use Bhittani\Container\Fixtures\DeferredFacadeServiceProvider;
use Bhittani\Container\Fixtures\DeferredBindingServiceProvider;

$container = null; $booted = $registered = 0;

class ServiceContainerTest extends TestCase
{
    protected $container;

    function setUp()
    {
        global $container, $booted, $registered;

        $container = null; $booted = $registered = 0;

        $this->container = new ServiceContainer;
    }

    /** @test */
    function it_extends_container()
    {
        $this->assertInstanceOf(Container::class, $this->container);
    }

    /** @test */
    function it_accepts_service_providers_for_container_bootstrapping()
    {
        global $container, $booted, $registered;

        $this->assertNull($container);
        $this->assertEquals(0, $booted);
        $this->assertEquals(0, $registered);

        $this->container->addServiceProvider(FooServiceProvider::class);
        $this->container->bootstrap();

        $this->assertSame($this->container, $container);
        $this->assertEquals(1, $booted);
        $this->assertEquals(1, $registered);
    }

    /** @test */
    function it_registers_and_bootstraps_the_added_service_providers_after_container_bootstrap()
    {
        global $booted, $registered;

        $this->assertEquals(0, $booted);
        $this->assertEquals(0, $registered);

        call_user_func($this->container);

        $this->assertEquals(0, $booted);
        $this->assertEquals(0, $registered);

        $this->container->addServiceProvider(FooServiceProvider::class);

        $this->assertEquals(1, $booted);
        $this->assertEquals(1, $registered);
    }

    /** @test */
    function it_bootstraps_at_most_once()
    {
        global $booted, $registered;

        $this->assertEquals(0, $booted);
        $this->assertEquals(0, $registered);

        $this->container->addServiceProvider(FooServiceProvider::class);
        $this->container->bootstrap();

        $this->assertEquals(1, $booted);
        $this->assertEquals(1, $registered);

        $this->container->bootstrap();

        $this->assertEquals(1, $booted);
        $this->assertEquals(1, $registered);
    }

    /** @test */
    function service_providers_with_explicit_bindings_are_deferred()
    {
        global $container, $booted, $registered;

        $this->assertNull($container);
        $this->assertEquals(0, $booted);
        $this->assertEquals(0, $registered);

        $this->container->addServiceProvider(DeferredBindingServiceProvider::class);
        $this->container->bootstrap();

        $this->assertNull($container);
        $this->assertEquals(0, $booted);
        $this->assertEquals(0, $registered);

        $this->container->get(FooService::class);

        $this->assertSame($this->container, $container);
        $this->assertEquals(1, $booted);
        $this->assertEquals(1, $registered);
    }

    /** @test */
    function service_providers_with_explicit_facades_are_deferred()
    {
        global $container, $booted, $registered;

        $this->assertNull($container);
        $this->assertEquals(0, $booted);
        $this->assertEquals(0, $registered);

        $this->container->addServiceProvider(DeferredFacadeServiceProvider::class);
        $this->container->bootstrap();

        $this->assertNull($container);
        $this->assertEquals(0, $booted);
        $this->assertEquals(0, $registered);

        $this->container->foo;

        $this->assertSame($this->container, $container);
        $this->assertEquals(1, $booted);
        $this->assertEquals(1, $registered);
    }

    /** @test */
    function service_providers_with_explicit_macros_are_deferred()
    {
        global $container, $booted, $registered;

        $this->assertNull($container);
        $this->assertEquals(0, $booted);
        $this->assertEquals(0, $registered);

        $this->container->addServiceProvider(DeferredMacroServiceProvider::class);
        $this->container->bootstrap();

        $this->assertNull($container);
        $this->assertEquals(0, $booted);
        $this->assertEquals(0, $registered);

        $foo = $this->container->foo('bar');
        $this->assertEquals('bar', $foo);

        $this->assertSame($this->container, $container);
        $this->assertEquals(1, $booted);
        $this->assertEquals(1, $registered);
    }
}
