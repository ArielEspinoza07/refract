<?php

declare(strict_types=1);

use Refract\Exceptions\NotSerializable;
use Refract\ReflectedAttribute;
use Refract\ReflectedClass;
use Refract\ReflectedConstant;
use Refract\ReflectedEnum;
use Refract\ReflectedFunction;
use Refract\ReflectedMethod;
use Refract\ReflectedParameter;
use Refract\ReflectedProperty;
use Refract\Tests\Fixtures\Status;
use Refract\Tests\Fixtures\Table;
use Refract\Tests\Fixtures\User;

dataset('reflections', [
    'class' => [ReflectedClass::from(User::class)],
    'method' => [ReflectedMethod::from(User::class, 'save')],
    'property' => [ReflectedProperty::from(User::class, 'email')],
    'constant' => [ReflectedConstant::from(User::class, 'TABLE')],
    'attribute' => [ReflectedAttribute::from(User::class, Table::class)],
    'enum' => [ReflectedEnum::from(Status::class)],
    'function' => [ReflectedFunction::from('strlen')],
    'parameter' => [ReflectedParameter::from('strlen', 0)],
    'type' => [ReflectedProperty::from(User::class, 'email')->type()],
]);

it('refuses to be serialized, naming the Refract class and the fix', function (object $reflection): void {
    expect(static fn (): string => serialize($reflection))
        ->toThrow(NotSerializable::class, '['.$reflection::class.'] cannot be serialized')
        ->and(static fn (): string => serialize($reflection))
        ->toThrow(NotSerializable::class, 'call from() again');
})->with('reflections');

it('refuses to be unserialized from a crafted string', function (string $class): void {
    unserialize('O:'.mb_strlen($class).':"'.$class.'":0:{}');
})->with([
    ReflectedClass::class,
    ReflectedMethod::class,
])->throws(NotSerializable::class);
