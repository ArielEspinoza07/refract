<?php

declare(strict_types=1);

namespace Refract;

use BackedEnum;
use ReflectionClassConstant;
use Refract\Collections\ConstantCollection;
use Refract\Concerns\BelongsToClass;
use Refract\Concerns\InteractsWithAttributes;
use Refract\Concerns\InteractsWithDocComment;
use Refract\Concerns\InteractsWithTypes;
use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\ClassMember;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\ConstantNotFound;

/**
 * Fluent, immutable reflection of a class constant or enum case.
 */
final readonly class ReflectedConstant implements ClassMember
{
    use BelongsToClass;
    use InteractsWithAttributes;
    use InteractsWithDocComment;
    use InteractsWithTypes;
    use PreventsSerialization;

    private function __construct(
        private ReflectionClassConstant $native,
    ) {}

    /**
     * Reflect a constant or enum case, including inherited non-private constants, by class name or instance.
     *
     * @param  class-string|object  $class
     *
     * @throws ClassNotFound When the given name is not a class, interface, trait or enum.
     * @throws ConstantNotFound When the class has no such constant or enum case.
     */
    public static function from(string|object $class, string $constant): self
    {
        return ReflectedClass::from($class)->constant($constant);
    }

    /**
     * Like from(), but returns null instead of throwing when the class or the constant does not exist.
     *
     * Accepts any string as class name, so it can be used directly on untrusted input.
     */
    public static function tryFrom(string|object $class, string $constant): ?self
    {
        $reflected = ReflectedClass::tryFrom($class);

        if ($reflected === null || ! $reflected->hasConstant($constant)) {
            return null;
        }

        return $reflected->constant($constant);
    }

    /**
     * Reflect all constants and enum cases of a class, including inherited non-private constants.
     *
     * @param  class-string|object  $class
     *
     * @throws ClassNotFound When the given name is not a class, interface, trait or enum.
     */
    public static function collectFrom(string|object $class): ConstantCollection
    {
        return ReflectedClass::from($class)->constants();
    }

    public static function fromNative(ReflectionClassConstant $native): self
    {
        return new self($native);
    }

    public function native(): ReflectionClassConstant
    {
        return $this->native;
    }

    public function name(): string
    {
        return $this->native->getName();
    }

    /**
     * The constant's value. For an enum case, this is the case instance itself.
     */
    public function value(): mixed
    {
        return $this->native->getValue();
    }

    public function isFinal(): bool
    {
        return $this->native->isFinal();
    }

    public function isEnumCase(): bool
    {
        return $this->native->isEnumCase();
    }

    /**
     * The scalar value of a backed enum case, or null for a pure enum case or a regular constant.
     */
    public function backingValue(): int|string|null
    {
        $value = $this->native->getValue();

        return $value instanceof BackedEnum ? $value->value : null;
    }

    private function typeScope(): string
    {
        return $this->native->class;
    }

    private function declarationName(): string
    {
        return "{$this->native->class}::{$this->native->getName()}";
    }
}
