<?php

declare(strict_types=1);

use Refract\Collections\ParameterCollection;
use Refract\ReflectedParameter;
use Refract\Tests\Fixtures\Signatures;
use Refract\Tests\Fixtures\Tag;

function sendParameters(): ParameterCollection
{
    return ReflectedParameter::collectFrom([Signatures::class, 'send']);
}

it('filters required parameters', function (): void {
    expect(sendParameters()->required()->names())->toBe(['to']);
});

it('filters optional parameters, including variadics', function (): void {
    expect(sendParameters()->optional()->names())->toBe(['subject', 'attempts', 'cc']);
});

it('filters variadic parameters', function (): void {
    expect(sendParameters()->variadic()->names())->toBe(['cc']);
});

it('filters promoted parameters', function (): void {
    expect(ReflectedParameter::collectFrom([Signatures::class, '__construct'])->promoted()->names())->toBe(['id'])
        ->and(sendParameters()->promoted()->names())->toBe([]);
});

it('filters parameters with a given attribute', function (): void {
    expect(sendParameters()->withAttribute(Tag::class)->names())->toBe(['to']);
});

it('chains filters', function (): void {
    expect(sendParameters()->optional()->variadic()->names())->toBe(['cc']);
});
