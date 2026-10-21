<?php

declare(strict_types=1);

use Refract\Contracts\ClassMember;
use Refract\Contracts\MemberCollection;
use Refract\ReflectedClass;
use Refract\ReflectedConstant;
use Refract\ReflectedMethod;
use Refract\ReflectedProperty;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Model;

dataset('public members', [
    'method' => [ReflectedMethod::from(Article::class, 'show')],
    'property' => [ReflectedProperty::from(Article::class, 'title')],
    'constant' => [ReflectedConstant::from(Article::class, 'TYPE')],
]);

dataset('protected members', [
    'method' => [ReflectedMethod::from(Article::class, 'render')],
    'property' => [ReflectedProperty::from(Article::class, 'summary')],
    'constant' => [ReflectedConstant::from(Article::class, 'LIMIT')],
]);

dataset('private members', [
    'method' => [ReflectedMethod::from(Article::class, 'track')],
    'property' => [ReflectedProperty::from(Article::class, 'views')],
    'constant' => [ReflectedConstant::from(Article::class, 'STEP')],
]);

// Reflected through Article, but declared by its parent Model.
dataset('inherited members', [
    'method' => [ReflectedMethod::from(Article::class, 'id')],
    'property' => [ReflectedProperty::from(Article::class, 'key')],
    'constant' => [ReflectedConstant::from(Article::class, 'CONNECTION')],
]);

it('is implemented by every class member type', function (ClassMember $member): void {
    expect($member)->toBeInstanceOf(ClassMember::class);
})->with('public members');

it('is matched by a MemberCollection for every member type', function (): void {
    $class = ReflectedClass::from(Article::class);

    expect($class->methods())->toBeInstanceOf(MemberCollection::class)
        ->and($class->properties())->toBeInstanceOf(MemberCollection::class)
        ->and($class->constants())->toBeInstanceOf(MemberCollection::class);
});

it('detects public members', function (ClassMember $member): void {
    expect($member->isPublic())->toBeTrue()
        ->and($member->isProtected())->toBeFalse()
        ->and($member->isPrivate())->toBeFalse();
})->with('public members');

it('detects protected members', function (ClassMember $member): void {
    expect($member->isPublic())->toBeFalse()
        ->and($member->isProtected())->toBeTrue()
        ->and($member->isPrivate())->toBeFalse();
})->with('protected members');

it('detects private members', function (ClassMember $member): void {
    expect($member->isPublic())->toBeFalse()
        ->and($member->isProtected())->toBeFalse()
        ->and($member->isPrivate())->toBeTrue();
})->with('private members');

it('returns the class that declares the member', function (ClassMember $member): void {
    expect($member->declaringClass()->name())->toBe(Article::class);
})->with('public members');

it('returns the parent class for an inherited member', function (ClassMember $member): void {
    expect($member->declaringClass()->name())->toBe(Model::class);
})->with('inherited members');

it('tells whether the member is declared by a given class', function (ClassMember $member): void {
    expect($member->isDeclaredBy(Article::class))->toBeTrue()
        ->and($member->isDeclaredBy(Model::class))->toBeFalse();
})->with('public members');

it('is not declared by the class it is inherited into', function (ClassMember $member): void {
    expect($member->isDeclaredBy(Model::class))->toBeTrue()
        ->and($member->isDeclaredBy(Article::class))->toBeFalse();
})->with('inherited members');
