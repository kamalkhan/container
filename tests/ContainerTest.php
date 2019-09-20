<?php

namespace Bhittani\Container;

use ArrayAccess;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerTest extends TestCase
{
    protected $container;

    function setUp()
    {
        $this->container = new Container;
    }

    /** @test */
    function it_implements_psr11()
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container);
    }

    /** @test */
    function it_implements_ArrayAccess()
    {
        $this->assertInstanceOf(ArrayAccess::class, $this->container);
    }

    /** @test */
    function it_adds_and_retrieves_an_entity()
    {
        $this->container->add('foo', 'bar');

        $this->assertEquals('bar', $this->container->get('foo'));
    }

    /** @test */
    function it_resolves_an_unbinded_object_if_no_class_constructor()
    {
        $class = Fixtures\WithoutConstructor::class;

        $this->assertInstanceOf($class, $this->container->get($class));
    }

    /** @test */
    function it_resolves_an_unbinded_object_if_class_constructor_has_no_params()
    {
        $class = Fixtures\WithZeroParams::class;

        $this->assertInstanceOf($class, $this->container->get($class));
    }

    /** @test */
    function it_resolves_an_unbinded_object_if_class_constructor_params_can_be_resolved_recursively()
    {
        $class = Fixtures\WithTwoParams::class;

        $this->assertInstanceOf($class, $this->container->get($class));
    }

    /** @test */
    function it_resolves_an_object_if_class_constructor_params_are_being_managed()
    {
        $class = Fixtures\Foobar::class;

        $this->container->add('foo', 'bar');

        $this->assertInstanceOf($class, $this->container->get($class));

        $class = Fixtures\WithManagedParams::class;

        $this->assertInstanceOf($class, $this->container->get($class));
    }

    /** @test */
    function it_resolves_a_class_if_constructor_has_optional_params()
    {
        $class = Fixtures\WithOptionalParams::class;

        $withOptionalParams = $this->container->get($class);

        $this->assertInstanceOf($class, $withOptionalParams);
        $this->assertEquals('b', $withOptionalParams->a);

        $this->container->add('a', 'c');
        $withOptionalParams = $this->container->get($class);

        $this->assertInstanceOf($class, $withOptionalParams);
        $this->assertEquals('c', $withOptionalParams->a);
    }

    /** @test */
    function it_resolves_a_closure()
    {
        $this->container->add('closure', function () {
            return 'b';
        });

        $this->assertEquals('b', $this->container->get('closure'));

        $class = Fixtures\Foobar::class;

        $this->container->add($class, function () use ($class) {
            return new $class('baz');
        });

        $this->assertInstanceOf($class, $this->container->get($class));
    }

    /** @test */
    function it_resolves_a_closure_with_params()
    {
        $this->container->add('closure', function (Fixtures\WithTwoParams $w2) {
            return 'foobar';
        });

        $this->assertEquals('foobar', $this->container->get('closure'));
    }

    /** @test */
    function it_resolves_a_direct_closure()
    {
        $result = $this->container->call(function (Fixtures\WithTwoParams $w2) {
            return 'foobar';
        });

        $this->assertEquals('foobar', $result);
    }

    /** @test */
    function it_does_not_resolve_an_invocable()
    {
        $this->container->add('invocable', $invocable = new Fixtures\Invocable);

        $this->assertSame($invocable, $this->container->get('invocable'));
    }

    /** @test */
    function it_resolves_a_direct_invocable()
    {
        $result = $this->container->call(new Fixtures\InvocableWithParams);

        $this->assertEquals('invoke + params', $result);
    }

    /** @test */
    function it_resolves_an_interface()
    {
        $contractInterface = Fixtures\Contract::class;
        $concreteClass = Fixtures\Concrete::class;
        $concrete = $this->container->get($concreteClass);
        $this->assertInstanceOf($contractInterface, $concrete);
        $this->container->add($contractInterface, $concrete);
        $contractParamClass = Fixtures\ContractParam::class;
        $contractParam = $this->container->get($contractParamClass);
        $this->assertInstanceOf($contractParamClass, $contractParam);
    }

    /** @test */
    function it_accepts_explicit_arguments_to_resolve_an_entity()
    {
        $class = Fixtures\Foobar::class;

        $this->assertInstanceOf($class, $this->container->get($class, ['foo' => 'bar']));

        $class = Fixtures\MixedParams::class;

        $this->assertInstanceOf($class, $this->container->get($class, ['foo' => 'bar']));

        $closure = function (Fixtures\WithTwoParams $w2, $bar = null, $foo) {
            return $foo;
        };

        $this->container->add('baz', $closure);

        $this->assertEquals('bar', $this->container->get('baz', ['foo' => 'bar']));
        $this->assertEquals('bar', $this->container->call($closure, ['foo' => 'bar']));
    }

    /** @test */
    function it_allows_singletons()
    {
        $class = Fixtures\WithZeroParams::class;

        $this->container->share('shared', function () use ($class) {
            return new $class;
        });
        $resolvedShared1 = $this->container->get('shared');
        $this->assertInstanceOf($class, $resolvedShared1);
        $resolvedShared2 = $this->container->get('shared');
        $this->assertInstanceOf($class, $resolvedShared2);
        $this->assertSame($resolvedShared1, $resolvedShared2);

        $this->container->add('shared', function () use ($class) {
            return new $class;
        });

        $resolvedShared1 = $this->container->get('shared');
        $this->assertInstanceOf($class, $resolvedShared1);
        $resolvedShared2 = $this->container->get('shared');
        $this->assertInstanceOf($class, $resolvedShared2);
        $this->assertNotSame($resolvedShared1, $resolvedShared2);
    }

    /** @test */
    function it_allows_singleton_interfaces()
    {
        $contractInterface = Fixtures\Contract::class;
        $concreteClass = Fixtures\Concrete::class;
        $this->container->share($contractInterface, function () use ($concreteClass) {
            return $this->container->get($concreteClass);
        });
        $contractParamClass = Fixtures\ContractParam::class;
        $contractParam1 = $this->container->get($contractParamClass);
        $this->assertInstanceOf($contractParamClass, $contractParam1);
        $contractParam2 = $this->container->get($contractParamClass);
        $this->assertInstanceOf($contractParamClass, $contractParam2);
        $this->assertSame($contractParam1->concrete, $contractParam2->concrete);

        $this->container->add($contractInterface, function () use ($concreteClass) {
            return $this->container->get($concreteClass);
        });
        $contractParamClass = Fixtures\ContractParam::class;
        $contractParam1 = $this->container->get($contractParamClass);
        $this->assertInstanceOf($contractParamClass, $contractParam1);
        $contractParam2 = $this->container->get($contractParamClass);
        $this->assertInstanceOf($contractParamClass, $contractParam2);
        $this->assertNotSame($contractParam1->concrete, $contractParam2->concrete);
    }

    /** @test */
    function it_supports_aliases()
    {
        $this->container->alias('fizz', 'foo');

        $this->assertFalse($this->container->has('fizz'));

        $this->container->add('foo', 'bar');

        $this->assertTrue($this->container->has('fizz'));
        $this->assertEquals('bar', $this->container->get('fizz'));
    }

    /** @test */
    function it_supports_delegations()
    {
        $delegate = new Container;
        $delegate->add('delegation', 'beep');
        $this->container->delegate($delegate);
        $this->assertEquals('beep', $this->container->get('delegation'));
    }

    /** @test */
    function it_tells_whether_a_key_is_being_managed()
    {
        $delegate = new Container;
        $delegate->add('delegation', 'boop');
        $this->container->delegate($delegate);
        $this->container->add('foo', 'bar');
        $this->assertTrue($this->container->has('foo'));
        $this->assertTrue($this->container->has('delegation'));
        $this->assertFalse($this->container->has('hello'));
    }

    /** @test */
    function it_conforms_to_ArrayAccess()
    {
        $zombie = new Container;
        $zombie['world'] = 'hi';
        $delegation = new Container;
        $delegation['world'] = 'hello';
        $delegation->delegate($zombie);
        $this->container->delegate($delegation);
        $this->container->delegate($zombie);
        $this->container['hello'] = 'world';
        $this->container[] = 'foo';
        $this->assertEquals('world', $this->container['hello']);
        $this->assertEquals('hello', $this->container['world']);
        $this->assertEquals('foo', $this->container[0]);
        $this->assertTrue(isset($this->container['world']));
        $this->assertFalse(isset($this->container['beep']));
        unset($this->container['world']);
        $this->assertFalse(isset($this->container['world']));
    }

    /** @test */
    function it_throws_a_NotFoundException_if_key_is_not_being_managed()
    {
        try {
            $this->container->get('baz');
        } catch (NotFoundException $e) {
            return $this->assertEquals(
                'baz is not managed by the container.',
                $e->getMessage()
            );
        }

        $this->fail(sprintf('A %s exception was not thrown.', NotFoundException::class));
    }

    /** @test */
    function it_throws_a_NotFoundException_if_an_unbinded_class_can_not_be_resolved()
    {
        try {
            $this->container->get($class = Fixtures\Foobar::class);
        } catch (NotFoundException $e) {
            return $this->assertEquals(
                sprintf('Failed to resolve class %s.', $class),
                $e->getMessage()
            );
        }

        $this->fail(sprintf('A %s exception was not thrown.', NotFoundException::class));
    }

    /** @test */
    function it_throws_a_NotFoundException_if_an_unbinded_interface_can_not_be_resolved()
    {
        try {
            $this->container->get($interface = Fixtures\Contract::class);
        } catch (NotFoundException $e) {
            return $this->assertEquals(
                sprintf('Failed to resolve interface %s.', $interface),
                $e->getMessage()
            );
        }

        $this->fail(sprintf('A %s exception was not thrown.', NotFoundException::class));
    }
}
