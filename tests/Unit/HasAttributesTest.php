<?php

declare(strict_types=1);

use Refract\Collections\Collection;
use Refract\Contracts\HasAttributes;
use Refract\Exceptions\AttributeNotFound;
use Refract\ReflectedAttribute;
use Refract\ReflectedClass;
use Refract\ReflectedConstant;
use Refract\ReflectedMethod;
use Refract\ReflectedProperty;
use Refract\Tests\Fixtures\Article;
use Refract\Tests\Fixtures\Label;
use Refract\Tests\Fixtures\Table;
use Refract\Tests\Fixtures\Tag;
use Refract\Tests\Fixtures\User;

// Every member below carries #[Tag] and not #[Table]. Class-level attributes are
// covered in ReflectedClassTest; they share the same implementation.
dataset('tagged members', [
    'method' => [ReflectedMethod::from(Article::class, 'show'), Article::class.'::show()'],
    'property' => [ReflectedProperty::from(Article::class, 'summary'), Article::class.'::$summary'],
    'constant' => [ReflectedConstant::from(Article::class, 'TYPE'), Article::class.'::TYPE'],
]);

it('is implemented by every type that can carry attributes', function (): void {
    expect(ReflectedClass::from(Article::class))->toBeInstanceOf(HasAttributes::class)
        ->and(ReflectedMethod::from(Article::class, 'show'))->toBeInstanceOf(HasAttributes::class)
        ->and(ReflectedProperty::from(Article::class, 'summary'))->toBeInstanceOf(HasAttributes::class)
        ->and(ReflectedConstant::from(Article::class, 'TYPE'))->toBeInstanceOf(HasAttributes::class);
});

it('lists all attributes', function (HasAttributes $member): void {
    $attributes = $member->attributes();

    expect($attributes)->toBeInstanceOf(Collection::class)
        ->and($attributes->names())
        ->toBe([Tag::class]);
})->with('tagged members');

it('lists only attributes of the given type', function (HasAttributes $member): void {
    expect($member->attributes(Tag::class)->count())->toBe(1)
        ->and($member->attributes(Table::class)->isEmpty())->toBeTrue();
})->with('tagged members');

it('tells whether it has an attribute', function (HasAttributes $member): void {
    expect($member->hasAttribute(Tag::class))->toBeTrue()
        ->and($member->hasAttribute(Table::class))->toBeFalse();
})->with('tagged members');

it('returns an attribute by type', function (HasAttributes $member): void {
    expect($member->attribute(Tag::class)->name())->toBe(Tag::class);
})->with('tagged members');

it('names the declaration when the attribute is missing', function (HasAttributes $member, string $target): void {
    expect(static fn (): ReflectedAttribute => $member->attribute(Table::class))
        ->toThrow(AttributeNotFound::class, "Attribute [".Table::class."] is not declared on [{$target}].");
})->with('tagged members');

it('creates the attribute objects of the given type', function (HasAttributes $member): void {
    $instances = $member->attributeInstances(Tag::class);

    expect($instances)->toHaveCount(1)
        ->and($instances[0])->toBeInstanceOf(Tag::class)
        ->and($member->attributeInstances(Table::class))->toBe([]);
})->with('tagged members');

it('creates one object per declaration of a repeated attribute, in order', function (): void {
    $labels = ReflectedMethod::from(Article::class, 'render')->attributeInstances(Label::class);

    expect(array_map(static fn (Label $label): string => $label->value, $labels))->toBe(['first', 'second']);
});

it('creates class attribute objects with their arguments', function (): void {
    expect(ReflectedClass::from(User::class)->attributeInstances(Table::class)[0]->name)->toBe('users');
});
