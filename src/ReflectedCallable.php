<?php

declare(strict_types=1);

namespace Refract;

use Closure;
use Refract\Contracts\FunctionLike;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\FunctionNotFound;
use Refract\Exceptions\InvalidFunctionReference;
use Refract\Exceptions\MethodNotFound;

/**
 * Reflects any PHP callable as the function or method it refers to.
 *
 * Accepts a Closure, a function name, "Class::method", [$classOrObject, 'method'], or an invokable object.
 * A first-class callable such as $user->save(...) or strlen(...) resolves to the method or function it was
 * created from; only closures written with `function` or `fn` stay a ReflectedFunction.
 *
 * This class only creates reflections; it is never instantiated itself.
 *
 * @phpstan-type CallableReference Closure|object|string|array{0: class-string|object, 1: string}
 */
final class ReflectedCallable
{
    /** @codeCoverageIgnore Never called: the class only creates other reflections. */
    private function __construct() {}

    /**
     * @param  CallableReference  $callable
     *
     * @throws FunctionNotFound When no function has the given name.
     * @throws ClassNotFound When the given class does not exist.
     * @throws MethodNotFound When the class has no such method, or the object has no __invoke() method.
     * @throws InvalidFunctionReference When an array is not [class-or-object, method], or a string uses
     *                                  self::, parent:: or static::.
     */
    public static function from(object|string|array $callable): FunctionLike
    {
        return match (true) {
            $callable instanceof Closure => self::fromClosure($callable),
            is_object($callable) => ReflectedMethod::from($callable, '__invoke'),
            is_array($callable) => self::methodFrom(...(self::methodReference($callable) ?? throw InvalidFunctionReference::malformedArray())),
            str_contains($callable, '::') => self::methodFrom(...self::splitStaticCall($callable)),
            default => ReflectedFunction::from($callable),
        };
    }

    /**
     * Like from(), but returns null instead of throwing for anything that does not resolve.
     *
     * Accepts any value, including malformed arrays, so it can be used directly on untrusted input.
     */
    public static function tryFrom(mixed $callable): ?FunctionLike
    {
        return match (true) {
            $callable instanceof Closure => self::fromClosure($callable),
            is_object($callable) => ReflectedMethod::tryFrom($callable, '__invoke'),
            is_array($callable) => self::tryMethodFromArray($callable),
            ! is_string($callable) => null,
            str_contains($callable, '::') => self::isRelativeStaticCall($callable) ? null : ReflectedMethod::tryFrom(...self::splitStaticCall($callable)),
            default => ReflectedFunction::tryFrom($callable),
        };
    }

    private static function fromClosure(Closure $closure): FunctionLike
    {
        $function = ReflectedFunction::from($closure);
        $scope = $function->native()->getClosureScopeClass();

        return $function->isAnonymous() || $scope === null
            ? $function
            : ReflectedMethod::tryFrom($scope->getName(), $function->name()) ?? $function;
    }

    /**
     * @throws ClassNotFound
     * @throws MethodNotFound
     */
    private static function methodFrom(string|object $class, string $method): ReflectedMethod
    {
        return ReflectedMethod::tryFrom($class, $method)
            ?? (ReflectedClass::tryFrom($class) ?? throw ClassNotFound::named(is_string($class) ? $class : $class::class))->method($method);
    }

    /**
     * @param  array<mixed>  $callable
     */
    private static function tryMethodFromArray(array $callable): ?ReflectedMethod
    {
        $reference = self::methodReference($callable);

        return $reference === null ? null : ReflectedMethod::tryFrom(...$reference);
    }

    /**
     * The array as [class-or-object, method], or null when it does not have exactly that shape.
     * Validated at runtime, because the array may come from untrusted input.
     *
     * @param  array<mixed>  $callable
     * @return array{0: string|object, 1: string}|null
     */
    private static function methodReference(array $callable): ?array
    {
        $class = $callable[0] ?? null;
        $method = $callable[1] ?? null;

        return count($callable) === 2 && (is_string($class) || is_object($class)) && is_string($method)
            ? [$class, $method]
            : null;
    }

    /**
     * @return array{0: string, 1: string}
     *
     * @throws InvalidFunctionReference When the class part is self, parent or static.
     */
    private static function splitStaticCall(string $callable): array
    {
        if (self::isRelativeStaticCall($callable)) {
            throw InvalidFunctionReference::relativeStaticCall($callable);
        }

        [$class, $method] = explode('::', $callable, 2);

        return [$class, $method];
    }

    /**
     * self::, parent:: and static:: only mean something inside a class; PHP 8.2 deprecated them in callable strings.
     */
    private static function isRelativeStaticCall(string $callable): bool
    {
        return in_array(mb_strtolower(explode('::', $callable, 2)[0]), ['self', 'parent', 'static'], true);
    }
}
