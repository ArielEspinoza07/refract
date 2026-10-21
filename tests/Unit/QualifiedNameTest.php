<?php

declare(strict_types=1);

use Refract\Contracts\ClassMember;
use Refract\Contracts\FunctionLike;
use Refract\ReflectedClass;
use Refract\ReflectedConstant;
use Refract\ReflectedEnum;
use Refract\ReflectedFunction;
use Refract\ReflectedMethod;
use Refract\ReflectedParameter;
use Refract\ReflectedProperty;
use Refract\Tests\Fixtures\Model;
use Refract\Tests\Fixtures\Status;
use Refract\Tests\Fixtures\User;

it('names every kind of declaration in full', function (object $reflection, string $expected): void {
    /** @var ReflectedClass<object>|ReflectedEnum<UnitEnum>|ClassMember|FunctionLike|ReflectedParameter $reflection */
    expect($reflection->qualifiedName())->toBe($expected);
})->with([
    'class' => [ReflectedClass::from(User::class), User::class],
    'enum' => [ReflectedEnum::from(Status::class), Status::class],
    'method' => [ReflectedMethod::from(User::class, 'save'), User::class.'::save()'],
    'inherited method, named after the class that declares it' => [ReflectedMethod::from(User::class, 'id'), Model::class.'::id()'],
    'property' => [ReflectedProperty::from(User::class, 'email'), User::class.'::$email'],
    'constant' => [ReflectedConstant::from(User::class, 'TABLE'), User::class.'::TABLE'],
    'enum case' => [ReflectedConstant::from(Status::class, 'Active'), Status::class.'::Active'],
    'function' => [ReflectedFunction::from('Refract\Tests\Fixtures\greet'), 'Refract\Tests\Fixtures\greet()'],
    'method parameter' => [ReflectedParameter::from([User::class, '__construct'], 'email'), User::class.'::__construct($email)'],
    'function parameter' => [ReflectedParameter::from('Refract\Tests\Fixtures\greet', 'greeting'), 'Refract\Tests\Fixtures\greet($greeting)'],
]);

it('names a closure the way PHP does', function (): void {
    expect(ReflectedFunction::from(static fn (): int => 1)->qualifiedName())->toContain('{closure')->toEndWith('()');
});

it('is part of the ClassMember and FunctionLike contracts', function (ClassMember|FunctionLike $reflection, string $expected): void {
    expect($reflection->qualifiedName())->toBe($expected);
})->with([
    'class member' => [ReflectedProperty::from(User::class, 'email'), User::class.'::$email'],
    'function-like' => [ReflectedFunction::from('strlen'), 'strlen()'],
]);
