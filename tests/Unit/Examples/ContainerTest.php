<?php

declare(strict_types=1);

use Refract\Examples\Container\Clock;
use Refract\Examples\Container\Container;
use Refract\Examples\Container\Logger;
use Refract\Examples\Container\Mailer;
use Refract\Examples\Container\MemoryLogger;
use Refract\Examples\Container\Newsletter;
use Refract\Examples\Container\Report;
use Refract\Examples\Container\UnresolvableDependency;

function container(): Container
{
    $container = new Container();
    $container->bind(Logger::class, MemoryLogger::class);

    return $container;
}

it('builds a class without constructor dependencies', function (): void {
    expect(container()->make(MemoryLogger::class))->toBeInstanceOf(MemoryLogger::class);
});

it('builds interfaces through their binding, and keeps default values', function (): void {
    $mailer = container()->make(Mailer::class);

    expect($mailer->logger)->toBeInstanceOf(MemoryLogger::class)
        ->and($mailer->from)->toBe('noreply@example.com');
});

it('builds dependencies recursively, and falls back to null for an optional unbound interface', function (): void {
    $newsletter = container()->make(Newsletter::class);

    expect($newsletter->mailer->logger)->toBeInstanceOf(MemoryLogger::class)
        ->and($newsletter->clock)->toBeNull();
});

it('explains which parameter cannot be resolved', function (): void {
    container()->make(Report::class);
})->throws(UnresolvableDependency::class, 'Cannot resolve $limit of '.Report::class.'::__construct()');

it('refuses to build an interface without a binding', function (): void {
    container()->make(Clock::class);
})->throws(UnresolvableDependency::class, 'Cannot build ['.Clock::class.']');

it('refuses a binding to a class that does not implement the interface', function (): void {
    $container = new Container();
    $container->bind(Clock::class, MemoryLogger::class);

    $container->make(Clock::class);
})->throws(UnresolvableDependency::class, 'bound to ['.MemoryLogger::class.']');

it('detects circular dependencies instead of recursing forever', function (): void {
    // Mailer needs a Logger, and Logger is bound to Mailer.
    $container = new Container();
    $container->bind(Logger::class, Mailer::class);

    $container->make(Logger::class);
})->throws(UnresolvableDependency::class, 'circular dependency '.Logger::class.' -> '.Logger::class);

it('can build again after a failed attempt', function (): void {
    $container = container();

    expect(static fn (): Report => $container->make(Report::class))->toThrow(UnresolvableDependency::class)
        ->and($container->make(Mailer::class))->toBeInstanceOf(Mailer::class);
});
