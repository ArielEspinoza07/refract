<?php

declare(strict_types=1);

use Refract\Collections\Collection;
use Refract\Collections\ConstantCollection;
use Refract\Collections\MethodCollection;
use Refract\Collections\PropertyCollection;
use Refract\Enums\ClassKind;
use Refract\Exceptions\AttributeNotFound;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\ClassNotInstantiable;
use Refract\Exceptions\ConstantNotFound;
use Refract\Exceptions\EnumNotFound;
use Refract\Exceptions\FeatureNotSupported;
use Refract\Exceptions\MethodNotFound;
use Refract\Exceptions\PropertyNotFound;
use Refract\ReflectedClass;
use Refract\ReflectedMethod;
use Refract\ReflectedProperty;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\HasTimestamps;
use Refract\Tests\Fixtures\Identifiable;
use Refract\Tests\Fixtures\Model;
use Refract\Tests\Fixtures\Money;
use Refract\Tests\Fixtures\Status;
use Refract\Tests\Fixtures\Table;
use Refract\Tests\Fixtures\Tag;
use Refract\Tests\Fixtures\User;

describe('from()', function (): void {
    it('reflects a class by name', function (): void {
        expect(ReflectedClass::from(User::class)->name())->toBe(User::class);
    });

    it('reflects a class by instance', function (): void {
        expect(ReflectedClass::from(new User('ada@example.com'))->name())->toBe(User::class);
    });

    it('reflects the enum of an enum case', function (): void {
        expect(ReflectedClass::from(Status::Active)->name())->toBe(Status::class);
    });

    it('reflects interfaces, traits and enums by name', function (): void {
        expect(ReflectedClass::from(Identifiable::class)->name())->toBe(Identifiable::class)
            ->and(ReflectedClass::from(HasTimestamps::class)->name())->toBe(HasTimestamps::class)
            ->and(ReflectedClass::from(Status::class)->name())->toBe(Status::class);
    });

    it('reflects the given object itself, even when it is a native reflection', function (): void {
        $native = new ReflectionClass(User::class);

        expect(ReflectedClass::from($native)->name())->toBe(ReflectionClass::class);
    });

    it('throws ClassNotFound for an unknown class name', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedClass::from('Refract\Tests\Fixtures\Missing');
    })->throws(ClassNotFound::class, 'Refract\Tests\Fixtures\Missing');
});

describe('tryFrom()', function (): void {
    it('reflects a known class', function (): void {
        expect(ReflectedClass::tryFrom(User::class)?->name())->toBe(User::class);
    });

    it('returns null for an unknown class name', function (): void {
        expect(ReflectedClass::tryFrom('Refract\Tests\Fixtures\Missing'))->toBeNull();
    });
});

describe('fromNative()', function (): void {
    it('wraps the given native reflection', function (): void {
        $native = new ReflectionClass(User::class);

        $class = ReflectedClass::fromNative($native);

        expect($class->name())->toBe(User::class)
            ->and($class->native())->toBe($native);
    });
});

describe('identity', function (): void {
    it('exposes the fully qualified name, short name and namespace', function (): void {
        $class = ReflectedClass::from(User::class);

        expect($class->name())->toBe(User::class)
            ->and($class->shortName())->toBe('User')
            ->and($class->namespace())->toBe('Refract\Tests\Fixtures');
    });

    it('returns an empty namespace for a global class', function (): void {
        expect(ReflectedClass::from(stdClass::class)->namespace())->toBe('');
    });
});

describe('kind()', function (): void {
    it('tells what kind of class-like structure it is', function (ReflectedClass $class, ClassKind $kind): void {
        expect($class->kind())->toBe($kind);
    })->with([
        'class' => [ReflectedClass::from(User::class), ClassKind::Standard],
        'abstract class' => [ReflectedClass::from(Model::class), ClassKind::Standard],
        'interface' => [ReflectedClass::from(Identifiable::class), ClassKind::Interface],
        'trait' => [ReflectedClass::from(HasTimestamps::class), ClassKind::Trait],
        'enum' => [ReflectedClass::from(Status::class), ClassKind::Enum],
    ]);

    it('treats an anonymous class as a standard class', function (): void {
        expect(ReflectedClass::from(new class {})->kind())->toBe(ClassKind::Standard);
    });
});

describe('modifiers', function (): void {
    it('detects abstract classes', function (): void {
        expect(ReflectedClass::from(Model::class)->isAbstract())->toBeTrue()
            ->and(ReflectedClass::from(User::class)->isAbstract())->toBeFalse();
    });

    it('detects final classes', function (): void {
        expect(ReflectedClass::from(User::class)->isFinal())->toBeTrue()
            ->and(ReflectedClass::from(Model::class)->isFinal())->toBeFalse();
    });

    it('detects readonly classes', function (): void {
        expect(ReflectedClass::from(Money::class)->isReadonly())->toBeTrue()
            ->and(ReflectedClass::from(User::class)->isReadonly())->toBeFalse();
    });

    it('detects anonymous classes', function (): void {
        expect(ReflectedClass::from(new class {})->isAnonymous())->toBeTrue()
            ->and(ReflectedClass::from(User::class)->isAnonymous())->toBeFalse();
    });

    it('tells whether the class can be instantiated', function (ReflectedClass $class, bool $instantiable): void {
        expect($class->isInstantiable())->toBe($instantiable);
    })->with([
        'concrete class' => [ReflectedClass::from(User::class), true],
        'abstract class' => [ReflectedClass::from(Model::class), false],
        'interface' => [ReflectedClass::from(Identifiable::class), false],
        'trait' => [ReflectedClass::from(HasTimestamps::class), false],
        'enum' => [ReflectedClass::from(Status::class), false],
    ]);
});

describe('hierarchy', function (): void {
    it('returns the parent class', function (): void {
        expect(ReflectedClass::from(User::class)->parent()?->name())->toBe(Model::class);
    });

    it('returns null when there is no parent class', function (): void {
        expect(ReflectedClass::from(Model::class)->parent())->toBeNull();
    });

    it('lists implemented interfaces, including inherited ones', function (): void {
        $interfaces = ReflectedClass::from(User::class)->interfaces();

        expect($interfaces)->toBeInstanceOf(Collection::class)
            ->and($interfaces->names())
            ->toContain(Identifiable::class);
    });

    it('lists the traits used by the class', function (): void {
        $traits = ReflectedClass::from(User::class)->traits();

        expect($traits)->toBeInstanceOf(Collection::class);

        $names = $traits->names();

        expect($names)->toBe([HasTimestamps::class]);
    });

    it('tells whether it is a subclass of another class', function (): void {
        $class = ReflectedClass::from(User::class);

        expect($class->isSubclassOf(Model::class))->toBeTrue()
            ->and($class->isSubclassOf(User::class))->toBeFalse();
    });

    it('throws ClassNotFound when checking against an unknown class', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedClass::from(User::class)->isSubclassOf('Refract\Tests\Fixtures\Missing');
    })->throws(ClassNotFound::class);

    it('tells whether it implements an interface', function (): void {
        expect(ReflectedClass::from(User::class)->implements(Identifiable::class))->toBeTrue()
            ->and(ReflectedClass::from(Money::class)->implements(Identifiable::class))->toBeFalse();
    });

    it('never implements something that is not an interface', function (): void {
        $class = ReflectedClass::from(User::class);

        expect($class->implements(Model::class))->toBeFalse()
            ->and($class->implements(HasTimestamps::class))->toBeFalse()
            ->and($class->implements(Status::class))->toBeFalse();
    });

    it('throws ClassNotFound when checking an unknown interface', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedClass::from(User::class)->implements('Refract\Tests\Fixtures\Missing');
    })->throws(ClassNotFound::class);
});

describe('members', function (): void {
    it('tells whether a method exists, including inherited ones', function (): void {
        $class = ReflectedClass::from(User::class);

        expect($class->hasMethod('save'))->toBeTrue()
            ->and($class->hasMethod('id'))->toBeTrue()
            ->and($class->hasMethod('missing'))->toBeFalse();
    });

    it('returns a method by name', function (): void {
        expect(ReflectedClass::from(User::class)->method('save'))->toBeInstanceOf(ReflectedMethod::class);
    });

    it('throws MethodNotFound for an unknown method', function (): void {
        ReflectedClass::from(User::class)->method('missing');
    })->throws(MethodNotFound::class, 'missing');

    it('tells whether a property exists', function (): void {
        $class = ReflectedClass::from(User::class);

        expect($class->hasProperty('email'))->toBeTrue()
            ->and($class->hasProperty('missing'))->toBeFalse();
    });

    it('returns a property by name', function (): void {
        expect(ReflectedClass::from(User::class)->property('email'))->toBeInstanceOf(ReflectedProperty::class);
    });

    it('throws PropertyNotFound for an unknown property', function (): void {
        ReflectedClass::from(User::class)->property('missing');
    })->throws(PropertyNotFound::class, 'missing');

    it('tells whether a constant exists', function (): void {
        $class = ReflectedClass::from(User::class);

        expect($class->hasConstant('TABLE'))->toBeTrue()
            ->and($class->hasConstant('MISSING'))->toBeFalse();
    });

    it('returns the constructor', function (): void {
        expect(ReflectedClass::from(User::class)->constructor())->toBeInstanceOf(ReflectedMethod::class);
    });

    it('returns null when there is no constructor', function (): void {
        expect(ReflectedClass::from(Model::class)->constructor())->toBeNull();
    });
});

describe('attributes', function (): void {
    it('tells whether the class has an attribute', function (): void {
        expect(ReflectedClass::from(User::class)->hasAttribute(Table::class))->toBeTrue()
            ->and(ReflectedClass::from(Money::class)->hasAttribute(Table::class))->toBeFalse();
    });
});

describe('instantiation', function (): void {
    it('creates an instance with constructor arguments', function (): void {
        $user = ReflectedClass::from(User::class)->newInstance('ada@example.com');

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->email)->toBe('ada@example.com');
    });

    it('creates an instance without calling the constructor', function (): void {
        $user = ReflectedClass::from(User::class)->newInstanceWithoutConstructor();

        expect($user)->toBeInstanceOf(User::class)
            ->and(isset($user->email))->toBeFalse();
    });

    it('throws ClassNotInstantiable for a class that cannot be instantiated', function (ReflectedClass $class): void {
        $class->newInstance();
    })->with([
        'abstract class' => [ReflectedClass::from(Model::class)],
        'interface' => [ReflectedClass::from(Identifiable::class)],
        'enum' => [ReflectedClass::from(Status::class)],
    ])->throws(ClassNotInstantiable::class);

    it('throws ClassNotInstantiable when creating a non-standard or abstract class without constructor', function (ReflectedClass $class): void {
        $class->newInstanceWithoutConstructor();
    })->with([
        'abstract class' => [ReflectedClass::from(Model::class)],
        'interface' => [ReflectedClass::from(Identifiable::class)],
        'trait' => [ReflectedClass::from(HasTimestamps::class)],
        'enum' => [ReflectedClass::from(Status::class)],
    ])->throws(ClassNotInstantiable::class);

    it('throws ClassNotInstantiable for an internal final class created without constructor', function (): void {
        ReflectedClass::from(Closure::class)->newInstanceWithoutConstructor();
    })->throws(ClassNotInstantiable::class, 'Closure');
});

describe('member collections', function (): void {
    it('lists all methods, including inherited and trait methods', function (): void {
        $methods = ReflectedClass::from(User::class)->methods();

        expect($methods)->toBeInstanceOf(MethodCollection::class)
            ->and($methods->names())
            ->toEqualCanonicalizing(['__construct', 'save', 'touch', 'id']);
    });

    it('lists all properties, including inherited ones', function (): void {
        $properties = ReflectedClass::from(User::class)->properties();

        expect($properties)->toBeInstanceOf(PropertyCollection::class)
            ->and($properties->names())
            ->toEqualCanonicalizing(['email', 'key']);
    });

    it('lists all constants, including inherited ones', function (): void {
        $constants = ReflectedClass::from(User::class)->constants();

        expect($constants)->toBeInstanceOf(ConstantCollection::class)
            ->and($constants->names())
            ->toEqualCanonicalizing(['TABLE', 'CONNECTION']);
    });

    it('lists enum cases as constants', function (): void {
        $constants = ReflectedClass::from(Status::class)->constants();

        expect($constants->names())
            ->toEqualCanonicalizing(['Active', 'Inactive']);
    });

    it('returns a constant by name', function (): void {
        expect(ReflectedClass::from(User::class)->constant('TABLE')->name())->toBe('TABLE');
    });

    it('throws ConstantNotFound for an unknown constant', function (): void {
        ReflectedClass::from(User::class)->constant('MISSING');
    })->throws(ConstantNotFound::class, 'MISSING');
});

describe('attribute lookup', function (): void {
    it('lists all attributes declared on the class', function (): void {
        $attributes = ReflectedClass::from(User::class)->attributes();

        expect($attributes)->toBeInstanceOf(Collection::class)
            ->and($attributes->names())
            ->toBe([Table::class]);
    });

    it('lists only attributes of the given type', function (): void {
        $class = ReflectedClass::from(User::class);

        expect($class->attributes(Table::class)->count())->toBe(1)
            ->and($class->attributes(Tag::class)->isEmpty())->toBeTrue();
    });

    it('returns an attribute by type', function (): void {
        expect(ReflectedClass::from(User::class)->attribute(Table::class)->name())->toBe(Table::class);
    });

    it('throws AttributeNotFound when the attribute is not declared', function (): void {
        ReflectedClass::from(User::class)->attribute(Tag::class);
    })->throws(AttributeNotFound::class, Tag::class);
});

describe('dynamic properties', function (): void {
    it('sees properties added to the reflected instance at runtime', function (): void {
        $object = new stdClass();
        $object->extra = 1;

        expect(ReflectedClass::from($object)->hasProperty('extra'))->toBeTrue()
            ->and(ReflectedClass::tryFrom($object)->hasProperty('extra'))->toBeTrue()
            ->and(ReflectedClass::from(stdClass::class)->hasProperty('extra'))->toBeFalse();
    });
});

describe('class traits', function (): void {
    it('tells whether instances can be cloned', function (): void {
        expect(ReflectedClass::from(User::class)->isCloneable())->toBeTrue()
            ->and(ReflectedClass::from(Identifiable::class)->isCloneable())->toBeFalse()
            ->and(ReflectedClass::from(Status::class)->isCloneable())->toBeFalse();
    });

    it('tells whether instances are iterable', function (): void {
        expect(ReflectedClass::from(ArrayIterator::class)->isIterable())->toBeTrue()
            ->and(ReflectedClass::from(User::class)->isIterable())->toBeFalse();
    });

    it('tells whether the class is internal or user-defined', function (): void {
        $internal = ReflectedClass::from(stdClass::class);
        $userDefined = ReflectedClass::from(User::class);

        expect($internal->isInternal())->toBeTrue()
            ->and($internal->isUserDefined())->toBeFalse()
            ->and($userDefined->isInternal())->toBeFalse()
            ->and($userDefined->isUserDefined())->toBeTrue();
    });

    // The object is typed as plain `object`, as it is when the class is only known at runtime.
    it('tells whether an object is an instance of the class, including subclasses', function (object $user): void {
        expect(ReflectedClass::from(User::class)->isInstance($user))->toBeTrue()
            ->and(ReflectedClass::from(Model::class)->isInstance($user))->toBeTrue()
            ->and(ReflectedClass::from(Identifiable::class)->isInstance($user))->toBeTrue()
            ->and(ReflectedClass::from(Money::class)->isInstance($user))->toBeFalse();
    })->with([[new User('ada@example.com')]]);
});

describe('source location', function (): void {
    it('exposes the file and lines of a user-defined class', function (): void {
        $class = ReflectedClass::from(User::class);

        expect($class->fileName())->toEndWith('User.php')
            ->and($class->startLine())->toBeInt()
            ->and($class->endLine())->toBeGreaterThan($class->startLine() ?? PHP_INT_MAX);
    });

    it('returns null for the location of an internal class', function (): void {
        $class = ReflectedClass::from(stdClass::class);

        expect($class->fileName())->toBeNull()
            ->and($class->startLine())->toBeNull()
            ->and($class->endLine())->toBeNull();
    });

    it('returns the doc comment, or null when there is none', function (): void {
        expect(ReflectedClass::from(Article::class)->docComment())->toContain('Covers every member modifier')
            ->and(ReflectedClass::from(User::class)->docComment())->toBeNull();
    });
});

describe('property values', function (): void {
    it('lists default property values, including static and inherited ones', function (): void {
        $defaults = ReflectedClass::from(Article::class)->defaultProperties();

        expect($defaults)->toMatchArray(['published' => 0, 'summary' => null, 'views' => 0, 'key' => 1])
            ->and($defaults)->not->toHaveKey('title');
    });

    it('returns the value of a static property', function (): void {
        expect(ReflectedClass::from(Article::class)->staticPropertyValue('published'))->toBe(0);
    });

    it('throws PropertyNotFound for a property that is missing or not static', function (string $property): void {
        ReflectedClass::from(Article::class)->staticPropertyValue($property);
    })->with(['missing', 'views'])->throws(PropertyNotFound::class);
});

describe('lazy objects', function (): void {
    it('creates a lazy ghost initialized on first access', function (): void {
        $class = ReflectedClass::from(User::class);
        $ghost = $class->newLazyGhost(static function (User $user): void {
            $user->__construct('ada@example.com');
        });

        expect($class->isUninitializedLazyObject($ghost))->toBeTrue()
            ->and($ghost->email)->toBe('ada@example.com')
            ->and($class->isUninitializedLazyObject($ghost))->toBeFalse();
    })->skip(PHP_VERSION_ID < 80400, 'Requires PHP 8.4.');

    it('creates a lazy proxy that forwards to the real instance', function (): void {
        $class = ReflectedClass::from(User::class);
        $proxy = $class->newLazyProxy(static fn (User $user): User => new User('grace@example.com'));

        expect($class->isUninitializedLazyObject($proxy))->toBeTrue()
            ->and($proxy->email)->toBe('grace@example.com');
    })->skip(PHP_VERSION_ID < 80400, 'Requires PHP 8.4.');

    it('throws ClassNotInstantiable when PHP cannot make the class lazy', function (string $method): void {
        $class = ReflectedClass::from(Model::class);

        $method === 'ghost'
            ? $class->newLazyGhost(static function (Model $model): void {})
            : $class->newLazyProxy(static fn (Model $model): Model => $model);
    })->with(['ghost', 'proxy'])->throws(ClassNotInstantiable::class)->skip(PHP_VERSION_ID < 80400, 'Requires PHP 8.4.');

    it('throws FeatureNotSupported before PHP 8.4', function (): void {
        ReflectedClass::from(User::class)->newLazyGhost(static function (User $user): void {});
    })->throws(FeatureNotSupported::class)->skip(PHP_VERSION_ID >= 80400, 'Only applies before PHP 8.4.');

    it('reports a regular object as not lazy', function (): void {
        expect(ReflectedClass::from(User::class)->isUninitializedLazyObject(new User('ada@example.com')))->toBeFalse();
    });
});

describe('asEnum()', function (): void {
    it('reflects an enum class as an enum', function (): void {
        $enum = ReflectedClass::from(Status::class)->asEnum();

        expect($enum->name())->toBe(Status::class)
            ->and($enum->cases()->names())->toBe(['Active', 'Inactive']);
    });

    it('throws EnumNotFound for a class that is not an enum', function (): void {
        ReflectedClass::from(User::class)->asEnum();
    })->throws(EnumNotFound::class, User::class);
});
