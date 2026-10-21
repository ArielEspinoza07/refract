<?php

declare(strict_types=1);

use Refract\Collections\ParameterCollection;
use Refract\Contracts\HasAttributes;
use Refract\Exceptions\AttributeNotFound;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\DefaultValueNotAvailable;
use Refract\Exceptions\FunctionNotFound;
use Refract\Exceptions\InvalidFunctionReference;
use Refract\Exceptions\MethodNotFound;
use Refract\Exceptions\ParameterNotFound;
use Refract\ReflectedFunction;
use Refract\ReflectedMethod;
use Refract\ReflectedParameter;
use Refract\Tests\Fixtures\Signatures;
use Refract\Tests\Fixtures\Table;
use Refract\Tests\Fixtures\Tag;
use Refract\Tests\Fixtures\User;

describe('from()', function (): void {
    it('reflects a parameter of any callable form', function (mixed $function, string $parameter): void {
        /** @var Closure|object|string|array{0: class-string|object, 1: string} $function */
        expect(ReflectedParameter::from($function, $parameter)->name())->toBe($parameter);
    })->with([
        'closure' => [static fn (int $value): int => $value, 'value'],
        'function name' => ['Refract\Tests\Fixtures\greet', 'name'],
        'static call string' => [Signatures::class.'::send', 'to'],
        'class and method' => [[Signatures::class, 'send'], 'to'],
        'object and method' => [[new Signatures(), 'send'], 'subject'],
        'invokable object' => [new Signatures(), 'value'],
    ]);

    it('reflects a parameter by position', function (): void {
        expect(ReflectedParameter::from([Signatures::class, 'send'], 2)->name())->toBe('attempts');
    });

    it('throws FunctionNotFound for an unknown function', function (): void {
        ReflectedParameter::from('Refract\Tests\Fixtures\missing', 'name');
    })->throws(FunctionNotFound::class);

    it('throws ClassNotFound for an unknown class in a static call string', function (): void {
        ReflectedParameter::from('Refract\Tests\Fixtures\Missing::send', 'to');
    })->throws(ClassNotFound::class, 'Refract\Tests\Fixtures\Missing');

    it('throws MethodNotFound for an unknown method', function (): void {
        ReflectedParameter::from([Signatures::class, 'missing'], 'to');
    })->throws(MethodNotFound::class);

    it('throws MethodNotFound for an object that is not invokable', function (): void {
        ReflectedParameter::from(new User('ada@example.com'), 'value');
    })->throws(MethodNotFound::class, '__invoke');

    it('throws ParameterNotFound for an unknown parameter', function (): void {
        ReflectedParameter::from([Signatures::class, 'send'], 'missing');
    })->throws(ParameterNotFound::class);
});

describe('tryFrom()', function (): void {
    it('reflects a known parameter', function (): void {
        expect(ReflectedParameter::tryFrom(static fn (int $value): int => $value, 'value')?->name())->toBe('value')
            ->and(ReflectedParameter::tryFrom(Signatures::class.'::send', 'to')?->name())->toBe('to')
            ->and(ReflectedParameter::tryFrom('Refract\Tests\Fixtures\greet', 0)?->name())->toBe('name');
    });

    it('returns null when the function or parameter does not exist', function (mixed $function, string $parameter): void {
        /** @var Closure|object|string|array{0: class-string|object, 1: string} $function */
        expect(ReflectedParameter::tryFrom($function, $parameter))->toBeNull();
    })->with([
        'unknown function' => ['Refract\Tests\Fixtures\missing', 'name'],
        'unknown class' => ['Refract\Tests\Fixtures\Missing::send', 'to'],
        'unknown method' => [[Signatures::class, 'missing'], 'to'],
        'object that is not invokable' => [new User('ada@example.com'), 'value'],
        'unknown parameter' => [[Signatures::class, 'send'], 'missing'],
        'array with one item' => [['only-one'], 'x'],
        'array with three items' => [[Signatures::class, 'send', 'extra'], 'to'],
        'array without the expected keys' => [['class' => Signatures::class, 'method' => 'send'], 'to'],
        'array with a non-string method' => [[Signatures::class, 42], 'to'],
        'array with a non-class first item' => [[1, 'send'], 'to'],
    ]);

    it('throws InvalidFunctionReference from from() for a malformed array', function (mixed $function): void {
        /** @var array{0: class-string|object, 1: string} $function deliberately malformed at runtime */
        ReflectedParameter::from($function, 'to');
    })->with([
        'one item' => [['only-one']],
        'three items' => [[Signatures::class, 'send', 'extra']],
        'wrong keys' => [['class' => Signatures::class, 'method' => 'send']],
        'non-string method' => [[Signatures::class, 42]],
    ])->throws(InvalidFunctionReference::class);
});

describe('collectFrom()', function (): void {
    it('reflects all parameters in order', function (): void {
        $parameters = ReflectedParameter::collectFrom([Signatures::class, 'send']);

        expect($parameters)->toBeInstanceOf(ParameterCollection::class)
            ->and($parameters->names())
            ->toBe(['to', 'subject', 'attempts', 'cc']);
    });
});

describe('fromNative()', function (): void {
    it('wraps the given native reflection', function (): void {
        $native = new ReflectionParameter([Signatures::class, 'send'], 'to');

        expect(ReflectedParameter::fromNative($native)->native())->toBe($native);
    });
});

describe('signature details', function (): void {
    it('exposes the name, position and type', function (): void {
        $parameter = ReflectedParameter::from([Signatures::class, 'send'], 'subject');

        expect($parameter->name())->toBe('subject')
            ->and($parameter->position())->toBe(1)
            ->and($parameter->type()?->name())->toBe('?string')
            ->and($parameter->allowsNull())->toBeTrue();
    });

    it('returns null when the parameter has no type', function (): void {
        expect(ReflectedParameter::from(static fn ($value) => $value, 'value')->type())->toBeNull();
    });

    it('tells required, optional, variadic and by-reference parameters apart', function (): void {
        $to = ReflectedParameter::from([Signatures::class, 'send'], 'to');
        $attempts = ReflectedParameter::from([Signatures::class, 'send'], 'attempts');
        $cc = ReflectedParameter::from([Signatures::class, 'send'], 'cc');

        expect($to->isOptional())->toBeFalse()
            ->and($attempts->isOptional())->toBeTrue()
            ->and($attempts->isPassedByReference())->toBeTrue()
            ->and($to->isPassedByReference())->toBeFalse()
            ->and($cc->isVariadic())->toBeTrue()
            ->and($cc->isOptional())->toBeTrue()
            ->and($to->isVariadic())->toBeFalse();
    });

    it('detects promoted constructor parameters', function (): void {
        expect(ReflectedParameter::from([Signatures::class, '__construct'], 'id')->isPromoted())->toBeTrue()
            ->and(ReflectedParameter::from([Signatures::class, 'send'], 'to')->isPromoted())->toBeFalse();
    });
});

describe('default values', function (): void {
    it('returns the default value when one is declared', function (): void {
        $parameter = ReflectedParameter::from('Refract\Tests\Fixtures\greet', 'greeting');

        expect($parameter->hasDefaultValue())->toBeTrue()
            ->and($parameter->defaultValue())->toBe('Hello');
    });

    it('throws DefaultValueNotAvailable for a method parameter without default', function (): void {
        ReflectedParameter::from([Signatures::class, 'send'], 'to')->defaultValue();
    })->throws(DefaultValueNotAvailable::class, '['.Signatures::class.'::send($to)] has no default value.');

    it('throws DefaultValueNotAvailable for a function parameter without default', function (): void {
        expect(ReflectedParameter::from('Refract\Tests\Fixtures\greet', 'name')->hasDefaultValue())->toBeFalse();

        ReflectedParameter::from('Refract\Tests\Fixtures\greet', 'name')->defaultValue();
    })->throws(DefaultValueNotAvailable::class, '[Refract\Tests\Fixtures\greet($name)] has no default value.');
});

describe('declaration', function (): void {
    it('returns the method it belongs to', function (): void {
        $parameter = ReflectedParameter::from([Signatures::class, 'send'], 'to');

        expect($parameter->declaringFunction())->toBeInstanceOf(ReflectedMethod::class)
            ->and($parameter->declaringFunction()->name())->toBe('send')
            ->and($parameter->declaringClass()?->name())->toBe(Signatures::class);
    });

    it('returns the function it belongs to, without a class', function (): void {
        $parameter = ReflectedParameter::from('Refract\Tests\Fixtures\greet', 'name');

        expect($parameter->declaringFunction())->toBeInstanceOf(ReflectedFunction::class)
            ->and($parameter->declaringClass())->toBeNull();
    });
});

describe('attributes', function (): void {
    it('reads attributes declared on the parameter', function (): void {
        $parameter = ReflectedParameter::from([Signatures::class, 'send'], 'to');

        expect($parameter)->toBeInstanceOf(HasAttributes::class)
            ->and($parameter->hasAttribute(Tag::class))->toBeTrue()
            ->and(ReflectedParameter::from([Signatures::class, 'send'], 'subject')->hasAttribute(Tag::class))->toBeFalse();
    });

    it('names the parameter when an attribute is missing', function (): void {
        ReflectedParameter::from([Signatures::class, 'send'], 'to')->attribute(Table::class);
    })->throws(AttributeNotFound::class, 'is not declared on ['.Signatures::class.'::send($to)]');
});
