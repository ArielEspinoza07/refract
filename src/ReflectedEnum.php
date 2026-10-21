<?php

declare(strict_types=1);

namespace Refract;

use ReflectionEnum;
use ReflectionEnumUnitCase;
use ReflectionException;
use Refract\Collections\ConstantCollection;
use Refract\Concerns\InteractsWithAttributes;
use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\HasAttributes;
use Refract\Contracts\Named;
use Refract\Exceptions\ConstantNotFound;
use Refract\Exceptions\EnumNotFound;
use UnitEnum;

/**
 * Fluent, immutable reflection of an enum and its cases.
 *
 * Cases are reflected as ReflectedConstant, the same as in ReflectedClass::constants().
 * Use asClass() for everything an enum shares with classes, like methods and interfaces.
 *
 * @template T of UnitEnum
 */
final readonly class ReflectedEnum implements HasAttributes, Named
{
    use InteractsWithAttributes;
    use PreventsSerialization;

    /**
     * @param  ReflectionEnum<T>  $native
     */
    private function __construct(
        private ReflectionEnum $native,
    ) {}

    /**
     * Reflect an enum by name, or the enum of the given case.
     *
     * @template TEnum of UnitEnum
     *
     * @param  class-string<TEnum>|TEnum  $enum
     * @return self<TEnum>
     *
     * @throws EnumNotFound When the given name is not an enum.
     */
    public static function from(string|UnitEnum $enum): self
    {
        try {
            return new self(new ReflectionEnum($enum));
        } catch (ReflectionException $exception) {
            throw EnumNotFound::named(is_string($enum) ? $enum : $enum::class, $exception);
        }
    }

    /**
     * Like from(), but returns null instead of throwing when the name is not an enum.
     *
     * Accepts any string, so it can be used directly on untrusted input.
     *
     * @template TEnum of UnitEnum
     *
     * @param  TEnum|string  $enum
     * @return ($enum is string ? self<UnitEnum>|null : self<TEnum>)
     */
    public static function tryFrom(string|UnitEnum $enum): ?self
    {
        if ($enum instanceof UnitEnum) {
            return new self(new ReflectionEnum($enum));
        }

        return enum_exists($enum) ? new self(new ReflectionEnum($enum)) : null;
    }

    /**
     * Wrap an existing native reflection.
     *
     * @template TEnum of UnitEnum
     *
     * @param  ReflectionEnum<TEnum>  $native
     * @return self<TEnum>
     */
    public static function fromNative(ReflectionEnum $native): self
    {
        return new self($native);
    }

    /**
     * @return ReflectionEnum<T>
     */
    public function native(): ReflectionEnum
    {
        return $this->native;
    }

    /**
     * @return class-string<T>
     */
    public function name(): string
    {
        return $this->native->getName();
    }

    public function shortName(): string
    {
        return $this->native->getShortName();
    }

    /**
     * Whether the cases are backed by int or string values.
     */
    public function isBacked(): bool
    {
        return $this->native->isBacked();
    }

    /**
     * The type of the case values (int or string), or null for a pure enum.
     */
    public function backingType(): ?ReflectedType
    {
        $type = $this->native->getBackingType();

        return $type === null ? null : ReflectedType::fromNative($type, $this->name());
    }

    /**
     * All cases, in declaration order.
     */
    public function cases(): ConstantCollection
    {
        return new ConstantCollection(array_values(array_map(
            static fn (ReflectionEnumUnitCase $case): ReflectedConstant => ReflectedConstant::fromNative($case),
            $this->native->getCases(),
        )));
    }

    public function hasCase(string $name): bool
    {
        return $this->native->hasCase($name);
    }

    /**
     * @throws ConstantNotFound When the enum has no such case.
     */
    public function case(string $name): ReflectedConstant
    {
        return $this->native->hasCase($name)
            ? ReflectedConstant::fromNative($this->native->getCase($name))
            : throw ConstantNotFound::in($this->name(), $name);
    }

    /**
     * The enum as a class, for everything enums share with classes: methods, interfaces, constants.
     *
     * @return ReflectedClass<T>
     */
    public function asClass(): ReflectedClass
    {
        return ReflectedClass::fromNative($this->native);
    }

    /**
     * The fully qualified name of the declaration, the same as name(), e.g. "App\Enums\Status".
     */
    public function qualifiedName(): string
    {
        return $this->name();
    }
}
