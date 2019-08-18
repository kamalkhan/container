<?php

namespace Bhittani\Container;

use PHPUnit\Framework\TestCase;

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
        $this->assertInstanceOf('Psr\Container\ContainerInterface', $this->container);
    }

    /** @test */
    function it_implements_ArrayAccess()
    {
        $this->assertInstanceOf('ArrayAccess', $this->container);
    }

    /** @test */
    function it_adds_and_retrieves_an_entity()
    {
        $this->container->add('foo', 'bar');
        $this->assertEquals('bar', $this->container->get('foo'));
    }

    /** @test */
    function it_resolves_an_unbinded_object_if_class_constructor_is_not_defined_or_has_no_params()
    {
        $class = 'Bhittani\Container\Fixtures\WithoutConstructor';
        $this->assertInstanceOf($class, $this->container->get($class));

        $class = 'Bhittani\Container\Fixtures\WithZeroParams';
        $this->assertInstanceOf($class, $this->container->get($class));
    }

    /** @test */
    function it_resolves_an_object_if_class_constructor_params_can_be_resolved_recursively()
    {
        $class = 'Bhittani\Container\Fixtures\WithTwoParams';
        $this->assertInstanceOf($class, $this->container->get($class));
    }

    /** @test */
    function it_resolves_an_object_if_class_constructor_params_are_being_managed()
    {
        $foobarClass = 'Bhittani\Container\Fixtures\Foobar';

        $this->container->add('foo', 'bar');
        $this->assertInstanceOf($foobarClass, $this->container->get($foobarClass));

        $class = 'Bhittani\Container\Fixtures\WithManagedParams';
        $this->assertInstanceOf($class, $this->container->get($class));
    }

    /** @test */
    function it_resolves_a_class_if_constructor_has_optional_params()
    {
        $class = 'Bhittani\Container\Fixtures\WithOptionalParams';
        $this->assertInstanceOf($class, $this->container->get($class));

        $this->container->add('a', 'c');
        $WithOptionalParams = $this->container->get($class);
        $this->assertInstanceOf($class, $WithOptionalParams);
        $this->assertEquals('c', $WithOptionalParams->a);
    }

    /** @test */
    function it_resolves_a_closure()
    {
        $this->container->add('a', function () {
            return 'b';
        });
        $this->assertEquals('b', $this->container->get('a'));

        $foobarClass = 'Bhittani\Container\Fixtures\Foobar';
        $this->container->add($foobarClass, function () use ($foobarClass) {
            return new $foobarClass('baz');
        });
        $this->assertInstanceOf($foobarClass, $this->container->get($foobarClass));
    }

    /** @test */
    function it_resolves_a_closure_with_params()
    {
        $this->container->add('closure', function (\Bhittani\Container\Fixtures\WithTwoParams $w2) {
            return 'it works!';
        });
        $this->assertEquals('it works!', $this->container->get('closure'));
    }

    /** @test */
    function it_resolves_a_direct_closure()
    {
        $result = $this->container->call(function (\Bhittani\Container\Fixtures\WithTwoParams $w2) {
            return 'it works!';
        });
        $this->assertEquals('it works!', $result);
    }

    /** @test */
    function it_resolves_an_invocable()
    {
        $this->container->add('invocable', new \Bhittani\Container\Fixtures\Invocable);
        $this->assertEquals('invoke', $this->container->get('invocable'));
    }

    /** @test */
    function it_resolves_an_invocable_with_params()
    {
        $this->container->add('invocableWithParams', new \Bhittani\Container\Fixtures\InvocableWithParams);
        $this->assertEquals('invoke + params', $this->container->get('invocableWithParams'));
    }

    /** @test */
    function it_resolves_a_direct_invocable()
    {
        $result = $this->container->call(new \Bhittani\Container\Fixtures\InvocableWithParams);
        $this->assertEquals('invoke + params', $result);
    }

    /** @test */
    function it_resolves_an_interface()
    {
        $contractInterface = 'Bhittani\Container\Fixtures\Contract';
        $concreteClass = 'Bhittani\Container\Fixtures\Concrete';
        $concrete = $this->container->get($concreteClass);
        $this->assertInstanceOf($contractInterface, $concrete);
        $this->container->add($contractInterface, $concrete);
        $contractParamClass = 'Bhittani\Container\Fixtures\ContractParam';
        $contractParam = $this->container->get($contractParamClass);
        $this->assertInstanceOf($contractParamClass, $contractParam);
    }

    /** @test */
    function it_accepts_explicit_arguments_to_resolve_an_entity()
    {
        $class = 'Bhittani\Container\Fixtures\Foobar';
        $this->assertInstanceOf($class, $this->container->get($class, ['foo' => 'bar']));

        $class = 'Bhittani\Container\Fixtures\MixedParams';
        $this->assertInstanceOf($class, $this->container->get($class, ['foo' => 'bar']));

        $closure = function (\Bhittani\Container\Fixtures\WithTwoParams $w2, $bar = null, $foo) {
            return $foo;
        };

        $this->container->add('baz', $closure);
        $this->assertEquals('bar', $this->container->get('baz', ['foo' => 'bar']));

        $this->assertEquals('bar', $this->container->call($closure, ['foo' => 'bar']));
    }

    /** @test */
    function it_allows_singletons()
    {
        $w0Class = 'Bhittani\Container\Fixtures\WithZeroParams';

        $this->container->share('shared', function () use ($w0Class) {
            return new $w0Class;
        });
        $resolvedShared1 = $this->container->get('shared');
        $this->assertInstanceOf($w0Class, $resolvedShared1);
        $resolvedShared2 = $this->container->get('shared');
        $this->assertInstanceOf($w0Class, $resolvedShared2);
        $this->assertSame($resolvedShared1, $resolvedShared2);

        $this->container->add('shared', function () use ($w0Class) {
            return new $w0Class;
        });
        $resolvedShared1 = $this->container->get('shared');
        $this->assertInstanceOf($w0Class, $resolvedShared1);
        $resolvedShared2 = $this->container->get('shared');
        $this->assertInstanceOf($w0Class, $resolvedShared2);
        $this->assertNotSame($resolvedShared1, $resolvedShared2);
    }

    /** @test */
    function it_allows_singleton_interfaces()
    {
        $contractInterface = 'Bhittani\Container\Fixtures\Contract';
        $concreteClass = 'Bhittani\Container\Fixtures\Concrete';
        $this->container->share($contractInterface, function () use ($concreteClass) {
            return $this->container->get($concreteClass);
        });
        $contractParamClass = 'Bhittani\Container\Fixtures\ContractParam';
        $contractParam1 = $this->container->get($contractParamClass);
        $this->assertInstanceOf($contractParamClass, $contractParam1);
        $contractParam2 = $this->container->get($contractParamClass);
        $this->assertInstanceOf($contractParamClass, $contractParam2);
        $this->assertSame($contractParam1->concrete, $contractParam2->concrete);

        $this->container->add($contractInterface, function () use ($concreteClass) {
            return $this->container->get($concreteClass);
        });
        $contractParamClass = 'Bhittani\Container\Fixtures\ContractParam';
        $contractParam1 = $this->container->get($contractParamClass);
        $this->assertInstanceOf($contractParamClass, $contractParam1);
        $contractParam2 = $this->container->get($contractParamClass);
        $this->assertInstanceOf($contractParamClass, $contractParam2);
        $this->assertNotSame($contractParam1->concrete, $contractParam2->concrete);
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
        // Should we also unset deeply in delegations?
        // Not unsetting from all delegations allows some fancy logic.
        // For e.g. Stack pop
        // while (isset($this->container['foo'])) {
        //     // some operation on $this->container['foo']
        //     unset($this->container['foo']);
        // }
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

        $this->fail('A NotFoundException exception was not thrown.');
    }

    /** @test */
    function it_throws_a_BindingResolutionException_if_key_can_not_be_resolved()
    {
        try {
            $this->container->get('Bhittani\Container\Fixtures\Foobar');
        } catch (BindingResolutionException $e) {
            return $this->assertEquals(
                'Failed to resolve class Bhittani\Container\Fixtures\Foobar from the container.',
                $e->getMessage()
            );
        }

        $this->fail('A BindingResolutionException exception was not thrown.');
    }
}
