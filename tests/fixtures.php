<?php

namespace Bhittani\Container\Fixtures;

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
    public $a;
    function __construct(WithTwoParams $w2, $a = "b") {
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
