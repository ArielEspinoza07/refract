<?php

declare(strict_types=1);

use Refract\Collections\MethodCollection;
use Refract\Contracts\ClassMember;
use Refract\Contracts\FunctionLike;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\InstanceRequired;
use Refract\Exceptions\MethodNotFound;
use Refract\Exceptions\MethodNotInvocable;
use Refract\Exceptions\ParameterNotFound;
use Refract\ReflectedMethod;
use Refract\ReflectedProperty;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Identifiable;
use Refract\Tests\Fixtures\Model;
use Refract\Tests\Fixtures\Signatures;
use Refract\Tests\Fixtures\User;

describe('from()', function (): void {
    it('reflects a method by class name', function (): void {
        expect(ReflectedMethod::from(User::class, 'save')->name())->toBe('save');
    });

    it('reflects a method by instance', function (): void {
        expect(ReflectedMethod::from(new User('ada@example.com'), 'save')->name())->toBe('save');
    });

    it('reflects inherited and trait methods', function (): void {
        expect(ReflectedMethod::from(User::class, 'id')->name())->toBe('id')
            ->and(ReflectedMethod::from(User::class, 'touch')->name())->toBe('touch');
    });

    it('throws ClassNotFound for an unknown class name', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedMethod::from('Refract\Tests\Fixtures\Missing', 'save');
    })->throws(ClassNotFound::class);

    it('throws MethodNotFound for an unknown method', function (): void {
        ReflectedMethod::from(User::class, 'missing');
    })->throws(MethodNotFound::class, 'missing');
});

describe('tryFrom()', function (): void {
    it('reflects a known method', function (): void {
        expect(ReflectedMethod::tryFrom(User::class, 'save')?->name())->toBe('save');
    });

    it('returns null for an unknown class name', function (): void {
        expect(ReflectedMethod::tryFrom('Refract\Tests\Fixtures\Missing', 'save'))->toBeNull();
    });

    it('returns null for an unknown method', function (): void {
        expect(ReflectedMethod::tryFrom(User::class, 'missing'))->toBeNull();
    });
});

describe('collectFrom()', function (): void {
    it('reflects all methods of a class, including inherited and trait methods', function (): void {
        $methods = ReflectedMethod::collectFrom(User::class);

        expect($methods)->toBeInstanceOf(MethodCollection::class)
            ->and($methods->names())
            ->toEqualCanonicalizing(['__construct', 'save', 'touch', 'id']);
    });

    it('reflects the methods of an instance', function (): void {
        expect(ReflectedMethod::collectFrom(new User('ada@example.com'))->count())->toBe(4);
    });

    it('throws ClassNotFound for an unknown class name', function (): void {
        /** @phpstan-ignore argument.type (deliberately passing a class name that does not exist) */
        ReflectedMethod::collectFrom('Refract\Tests\Fixtures\Missing');
    })->throws(ClassNotFound::class);
});

describe('modifiers', function (): void {
    it('implements FunctionLike as well as ClassMember', function (): void {
        $method = ReflectedMethod::from(Signatures::class, 'send');

        expect($method)->toBeInstanceOf(FunctionLike::class)
            ->and($method)->toBeInstanceOf(ClassMember::class)
            ->and($method->shortName())->toBe('send');
    });

    it('detects static, abstract and final methods', function (): void {
        expect(ReflectedMethod::from(Signatures::class, 'make')->isStatic())->toBeTrue()
            ->and(ReflectedMethod::from(Signatures::class, 'send')->isStatic())->toBeFalse()
            ->and(ReflectedMethod::from(Identifiable::class, 'id')->isAbstract())->toBeTrue()
            ->and(ReflectedMethod::from(Model::class, 'id')->isAbstract())->toBeFalse()
            ->and(ReflectedMethod::from(Model::class, 'id')->isFinal())->toBeTrue()
            ->and(ReflectedMethod::from(Signatures::class, 'send')->isFinal())->toBeFalse();
    });

    it('detects constructors and destructors', function (): void {
        expect(ReflectedMethod::from(Signatures::class, '__construct')->isConstructor())->toBeTrue()
            ->and(ReflectedMethod::from(Signatures::class, '__destruct')->isDestructor())->toBeTrue()
            ->and(ReflectedMethod::from(Signatures::class, 'send')->isConstructor())->toBeFalse()
            ->and(ReflectedMethod::from(Signatures::class, 'send')->isDestructor())->toBeFalse();
    });
});

describe('prototype()', function (): void {
    it('returns the interface or parent method a method implements', function (): void {
        expect(ReflectedMethod::from(Model::class, 'id')->prototype()?->declaringClass()->name())->toBe(Identifiable::class);
    });

    it('returns null when the method overrides nothing', function (): void {
        expect(ReflectedMethod::from(Signatures::class, 'send')->prototype())->toBeNull();
    });
});

describe('signature', function (): void {
    it('lists the parameters in order', function (): void {
        expect(ReflectedMethod::from(Signatures::class, 'send')->parameters()->names())
            ->toBe(['to', 'subject', 'attempts', 'cc']);
    });

    it('resolves the return type against the declaring class', function (): void {
        expect(ReflectedMethod::from(Signatures::class, 'make')->returnType()?->allows(new Signatures()))->toBeTrue();
    });

    it('detects variadic, generator and by-reference methods', function (): void {
        expect(ReflectedMethod::from(Signatures::class, 'send')->isVariadic())->toBeTrue()
            ->and(ReflectedMethod::from(Signatures::class, 'numbers')->isGenerator())->toBeTrue()
            ->and(ReflectedMethod::from(Signatures::class, 'sent')->returnsReference())->toBeTrue();
    });

    it('names the method when a parameter is missing', function (): void {
        ReflectedMethod::from(Signatures::class, 'send')->parameter('missing');
    })->throws(ParameterNotFound::class, 'does not exist on ['.Signatures::class.'::send()]');

    it('returns the doc comment', function (): void {
        expect(ReflectedMethod::from(Signatures::class, 'send')->docComment())->toBe('/** Sends a message. */');
    });

    it('returns null for the location of an internal method', function (): void {
        $method = ReflectedMethod::from(ArrayIterator::class, 'count');

        expect($method->isInternal())->toBeTrue()
            ->and($method->fileName())->toBeNull();
    });
});

describe('invoke()', function (): void {
    it('calls an instance method, including with named arguments', function (): void {
        $method = ReflectedMethod::from(Signatures::class, 'send');
        $signatures = new Signatures();

        expect($method->invoke($signatures, 'ada@example.com'))->toBeFalse()
            ->and($method->invoke($signatures, to: 'ada@example.com', subject: 'Hi'))->toBeTrue();
    });

    it('calls a static method without an object', function (): void {
        expect(ReflectedMethod::from(Signatures::class, 'make')->invoke())->toBeInstanceOf(Signatures::class);
    });

    it('calls a private method', function (): void {
        $article = new Article('hello-world', 'Hello');

        ReflectedMethod::from(Article::class, 'track')->invoke($article);

        expect(ReflectedProperty::from(Article::class, 'views')->value($article))->toBe(1);
    });

    it('throws InstanceRequired when calling an instance method without an object', function (): void {
        ReflectedMethod::from(Signatures::class, 'send')->invoke();
    })->throws(InstanceRequired::class, Signatures::class.'::send()');

    it('throws MethodNotInvocable for a method without a body', function (): void {
        ReflectedMethod::from(Identifiable::class, 'id')->invoke(new User('ada@example.com'));
    })->throws(MethodNotInvocable::class, Identifiable::class.'::id()');
});

describe('closure()', function (): void {
    it('returns an instance method bound to the object', function (): void {
        expect((ReflectedMethod::from(User::class, 'id')->closure(new User('ada@example.com')))())->toBe(1);
    });

    it('returns a static method without an object', function (): void {
        expect((ReflectedMethod::from(Signatures::class, 'make')->closure())())->toBeInstanceOf(Signatures::class);
    });

    it('throws InstanceRequired for an instance method without an object', function (): void {
        ReflectedMethod::from(User::class, 'id')->closure();
    })->throws(InstanceRequired::class);
});
