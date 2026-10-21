<?php

declare(strict_types=1);

use Refract\Collections\PropertyCollection;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\DefaultValueNotAvailable;
use Refract\Enums\PropertyHook;
use Refract\Exceptions\InstanceRequired;
use Refract\Exceptions\PropertyHookNotFound;
use Refract\Exceptions\PropertyNotFound;
use Refract\ReflectedClass;
use Refract\ReflectedMethod;
use Refract\ReflectedProperty;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\TypeSamples;
use Refract\Tests\Fixtures\User;

describe('from()', function (): void {
    it('reflects a property by class name', function (): void {
        expect(ReflectedProperty::from(User::class, 'email')->name())->toBe('email');
    });

    it('reflects a property by instance', function (): void {
        expect(ReflectedProperty::from(new User('ada@example.com'), 'email')->name())->toBe('email');
    });

    it('reflects inherited properties', function (): void {
        expect(ReflectedProperty::from(User::class, 'key')->name())->toBe('key');
    });

    it('throws ClassNotFound for an unknown class name', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedProperty::from('Refract\Tests\Fixtures\Missing', 'email');
    })->throws(ClassNotFound::class);

    it('throws PropertyNotFound for an unknown property', function (): void {
        ReflectedProperty::from(User::class, 'missing');
    })->throws(PropertyNotFound::class, 'missing');
});

describe('tryFrom()', function (): void {
    it('reflects a known property', function (): void {
        expect(ReflectedProperty::tryFrom(User::class, 'email')?->name())->toBe('email');
    });

    it('returns null for an unknown class name', function (): void {
        expect(ReflectedProperty::tryFrom('Refract\Tests\Fixtures\Missing', 'email'))->toBeNull();
    });

    it('returns null for an unknown property', function (): void {
        expect(ReflectedProperty::tryFrom(User::class, 'missing'))->toBeNull();
    });
});

describe('collectFrom()', function (): void {
    it('reflects all properties of a class, including inherited ones', function (): void {
        $properties = ReflectedProperty::collectFrom(User::class);

        expect($properties)->toBeInstanceOf(PropertyCollection::class)
            ->and($properties->names())
            ->toEqualCanonicalizing(['email', 'key']);
    });

    it('reflects the properties of an instance', function (): void {
        expect(ReflectedProperty::collectFrom(new User('ada@example.com'))->count())->toBe(2);
    });

    it('throws ClassNotFound for an unknown class name', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedProperty::collectFrom('Refract\Tests\Fixtures\Missing');
    })->throws(ClassNotFound::class);
});

describe('type()', function (): void {
    it('returns the declared type', function (): void {
        expect(ReflectedProperty::from(Article::class, 'title')->type()?->name())->toBe('string')
            ->and(ReflectedProperty::from(Article::class, 'summary')->type()?->name())->toBe('?string');
    });

    it('resolves self against the declaring class', function (): void {
        expect(ReflectedProperty::from(TypeSamples::class, 'self')->type()?->allows(new TypeSamples()))->toBeTrue();
    });

    it('returns null when the property has no type', function (): void {
        $object = new stdClass();
        $object->extra = 1;

        expect(ReflectedProperty::from($object, 'extra')->type())->toBeNull();
    });
});

describe('modifiers', function (): void {
    it('detects static, readonly and promoted properties', function (): void {
        expect(ReflectedProperty::from(Article::class, 'published')->isStatic())->toBeTrue()
            ->and(ReflectedProperty::from(Article::class, 'title')->isStatic())->toBeFalse()
            ->and(ReflectedProperty::from(Article::class, 'title')->isReadonly())->toBeTrue()
            ->and(ReflectedProperty::from(Article::class, 'summary')->isReadonly())->toBeFalse()
            ->and(ReflectedProperty::from(Article::class, 'slug')->isPromoted())->toBeTrue()
            ->and(ReflectedProperty::from(Article::class, 'title')->isPromoted())->toBeFalse();
    });

    it('detects properties added to an object at runtime', function (): void {
        $object = new stdClass();
        $object->extra = 1;

        expect(ReflectedProperty::from($object, 'extra')->isDynamic())->toBeTrue()
            ->and(ReflectedProperty::from(User::class, 'email')->isDynamic())->toBeFalse();
    });
});

describe('default values', function (): void {
    it('returns the default value when one is declared', function (): void {
        $property = ReflectedProperty::from(Article::class, 'summary');

        expect($property->hasDefaultValue())->toBeTrue()
            ->and($property->defaultValue())->toBeNull();
    });

    it('tells when there is no default value', function (string $name): void {
        expect(ReflectedProperty::from(Article::class, $name)->hasDefaultValue())->toBeFalse();
    })->with(['title', 'slug']);

    it('throws DefaultValueNotAvailable when there is no default value', function (): void {
        ReflectedProperty::from(Article::class, 'title')->defaultValue();
    })->throws(DefaultValueNotAvailable::class, Article::class.'::$title');
});

describe('value()', function (): void {
    it('reads an instance property, regardless of visibility', function (): void {
        $article = new Article('hello-world', 'Hello');

        expect(ReflectedProperty::from(Article::class, 'title')->value($article))->toBe('Hello')
            ->and(ReflectedProperty::from(Article::class, 'views')->value($article))->toBe(0);
    });

    it('reads a static property without an object', function (): void {
        expect(ReflectedProperty::from(Article::class, 'published')->value())->toBe(0);
    });

    it('throws InstanceRequired when reading an instance property without an object', function (): void {
        ReflectedProperty::from(Article::class, 'title')->value();
    })->throws(InstanceRequired::class, Article::class.'::$title');
});

describe('isInitialized()', function (): void {
    it('tells whether a typed property has been assigned', function (): void {
        $class = ReflectedClass::from(User::class);
        $property = ReflectedProperty::from(User::class, 'email');

        expect($property->isInitialized($class->newInstance('ada@example.com')))->toBeTrue()
            ->and($property->isInitialized($class->newInstanceWithoutConstructor()))->toBeFalse();
    });

    it('checks a static property without an object', function (): void {
        expect(ReflectedProperty::from(Article::class, 'published')->isInitialized())->toBeTrue();
    });

    it('throws InstanceRequired when checking an instance property without an object', function (): void {
        ReflectedProperty::from(User::class, 'email')->isInitialized();
    })->throws(InstanceRequired::class);
});

describe('docComment()', function (): void {
    it('returns the doc comment, or null when there is none', function (): void {
        expect(ReflectedProperty::from(TypeSamples::class, 'array')->docComment())->toBe('/** @var array<mixed> */')
            ->and(ReflectedProperty::from(TypeSamples::class, 'int')->docComment())->toBeNull();
    });
});

describe('PHP 8.4 property features', function (): void {
    it('detects virtual properties', function (): void {
        expect(hooked('fullName')->isVirtual())->toBeTrue()
            ->and(hooked('first')->isVirtual())->toBeFalse();
    })->skip(PHP_VERSION_ID < 80400, 'Requires PHP 8.4.');

    it('detects final and abstract properties', function (): void {
        expect(hooked('locked')->isFinal())->toBeTrue()
            ->and(hooked('label')->isAbstract())->toBeTrue()
            ->and(hooked('first')->isFinal())->toBeFalse()
            ->and(hooked('first')->isAbstract())->toBeFalse();
    })->skip(PHP_VERSION_ID < 80400, 'Requires PHP 8.4.');

    it('detects asymmetric visibility', function (): void {
        expect(hooked('last')->isPrivateSet())->toBeTrue()
            ->and(hooked('count')->isProtectedSet())->toBeTrue()
            ->and(hooked('last')->isProtectedSet())->toBeFalse()
            ->and(hooked('count')->isPrivateSet())->toBeFalse();
    })->skip(PHP_VERSION_ID < 80400, 'Requires PHP 8.4.');

    it('tells which hooks a property declares', function (): void {
        $fullName = hooked('fullName');
        $first = hooked('first');

        expect($fullName->hasHooks())->toBeTrue()
            ->and($fullName->hasHook(PropertyHook::Get))->toBeTrue()
            ->and($fullName->hasHook(PropertyHook::Set))->toBeFalse()
            ->and($first->hasHook(PropertyHook::Set))->toBeTrue()
            ->and(hooked('last')->hasHooks())->toBeFalse();
    })->skip(PHP_VERSION_ID < 80400, 'Requires PHP 8.4.');

    it('returns a hook as a method', function (): void {
        $hook = hooked('first')->hook(PropertyHook::Set);

        expect($hook)->toBeInstanceOf(ReflectedMethod::class)
            ->and($hook->parameters()->first()?->name())->toBe('value');
    })->skip(PHP_VERSION_ID < 80400, 'Requires PHP 8.4.');

    it('reports none of these features on a plain property, on any PHP version', function (): void {
        $property = ReflectedProperty::from(User::class, 'email');

        expect($property->isVirtual())->toBeFalse()
            ->and($property->isFinal())->toBeFalse()
            ->and($property->isAbstract())->toBeFalse()
            ->and($property->isPrivateSet())->toBeFalse()
            ->and($property->isProtectedSet())->toBeFalse()
            ->and($property->hasHooks())->toBeFalse()
            ->and($property->hasHook(PropertyHook::Get))->toBeFalse();
    });

    it('throws PropertyHookNotFound for a hook the property does not declare', function (): void {
        ReflectedProperty::from(User::class, 'email')->hook(PropertyHook::Get);
    })->throws(PropertyHookNotFound::class, User::class.'::$email] has no get hook');
});

/**
 * The fixture is referenced by name: it uses PHP 8.4 syntax, which PHPStan cannot parse
 * while analysing against PHP 8.3.
 */
function hooked(string $property): ReflectedProperty
{
    return ReflectedProperty::tryFrom('Refract\Tests\Fixtures\Php84\Hooked', $property)
        ?? throw new LogicException("Fixture property [{$property}] not found.");
}
