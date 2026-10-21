<?php

declare(strict_types=1);

namespace Refract;

use ReflectionMethod;
use ReflectionProperty;
use Refract\Collections\PropertyCollection;
use Refract\Concerns\BelongsToClass;
use Refract\Concerns\InteractsWithAttributes;
use Refract\Concerns\InteractsWithDocComment;
use Refract\Concerns\InteractsWithTypes;
use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\ClassMember;
use Refract\Enums\PropertyHook;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\DefaultValueNotAvailable;
use Refract\Exceptions\InstanceRequired;
use Refract\Exceptions\PropertyHookNotFound;
use Refract\Exceptions\PropertyNotFound;

/**
 * Fluent, immutable reflection of a class property.
 */
final readonly class ReflectedProperty implements ClassMember
{
    use BelongsToClass;
    use InteractsWithAttributes;
    use InteractsWithDocComment;
    use InteractsWithTypes;
    use PreventsSerialization;

    private function __construct(
        private ReflectionProperty $native,
    ) {}

    /**
     * Reflect a property, including inherited non-private properties, by class name or instance.
     *
     * @param  class-string|object  $class
     *
     * @throws ClassNotFound When the given name is not a class, interface, trait or enum.
     * @throws PropertyNotFound When the class has no such property.
     */
    public static function from(string|object $class, string $property): self
    {
        return ReflectedClass::from($class)->property($property);
    }

    /**
     * Like from(), but returns null instead of throwing when the class or the property does not exist.
     *
     * Accepts any string as class name, so it can be used directly on untrusted input.
     */
    public static function tryFrom(string|object $class, string $property): ?self
    {
        $reflected = ReflectedClass::tryFrom($class);

        if ($reflected === null || ! $reflected->hasProperty($property)) {
            return null;
        }

        return $reflected->property($property);
    }

    /**
     * Reflect all properties of a class, including inherited non-private properties.
     *
     * @param  class-string|object  $class
     *
     * @throws ClassNotFound When the given name is not a class, interface, trait or enum.
     */
    public static function collectFrom(string|object $class): PropertyCollection
    {
        return ReflectedClass::from($class)->properties();
    }

    public static function fromNative(ReflectionProperty $native): self
    {
        return new self($native);
    }

    public function native(): ReflectionProperty
    {
        return $this->native;
    }

    public function name(): string
    {
        return $this->native->getName();
    }

    public function isStatic(): bool
    {
        return $this->native->isStatic();
    }

    public function isReadonly(): bool
    {
        return $this->native->isReadOnly();
    }

    /**
     * Whether the property is declared through constructor property promotion.
     */
    public function isPromoted(): bool
    {
        return $this->native->isPromoted();
    }

    /**
     * Whether the property was added to an object at runtime rather than declared in the class.
     * Only possible when the property was reflected from an instance.
     */
    public function isDynamic(): bool
    {
        return ! $this->native->isDefault();
    }

    /**
     * Whether the declaration has a default value. Untyped properties implicitly default to null;
     * typed and promoted properties have none unless one is written.
     */
    public function hasDefaultValue(): bool
    {
        return $this->native->hasDefaultValue();
    }

    /**
     * @throws DefaultValueNotAvailable When the property has no default value.
     */
    public function defaultValue(): mixed
    {
        return $this->native->hasDefaultValue()
            ? $this->native->getDefaultValue()
            : throw DefaultValueNotAvailable::for($this->qualifiedName());
    }

    /**
     * The current value, regardless of visibility. Static properties need no object.
     *
     * Reading a typed property before it is initialized throws PHP's own Error, as direct access would.
     *
     * @throws InstanceRequired When the property is not static and no object is given.
     */
    public function value(?object $object = null): mixed
    {
        return $this->native->getValue($this->instanceFor($object));
    }

    /**
     * Whether the property has a value. False for typed properties not assigned yet, including
     * on objects created without their constructor.
     *
     * @throws InstanceRequired When the property is not static and no object is given.
     */
    public function isInitialized(?object $object = null): bool
    {
        return $this->native->isInitialized($this->instanceFor($object));
    }

    /**
     * Whether the property is declared final (PHP 8.4+). Properties with private(set) are implicitly final.
     * Always false before PHP 8.4.
     */
    public function isFinal(): bool
    {
        return method_exists($this->native, 'isFinal') && $this->native->isFinal() === true;
    }

    /**
     * Whether the property is declared abstract (PHP 8.4+). Always false before PHP 8.4.
     */
    public function isAbstract(): bool
    {
        return method_exists($this->native, 'isAbstract') && $this->native->isAbstract() === true;
    }

    /**
     * Whether the property has no storage of its own: its hooks never touch the backing value (PHP 8.4+).
     * Always false before PHP 8.4.
     */
    public function isVirtual(): bool
    {
        return method_exists($this->native, 'isVirtual') && $this->native->isVirtual() === true;
    }

    /**
     * Whether writing is restricted to the declaring class by asymmetric visibility, e.g. public private(set) (PHP 8.4+).
     * Always false before PHP 8.4.
     */
    public function isPrivateSet(): bool
    {
        return method_exists($this->native, 'isPrivateSet') && $this->native->isPrivateSet() === true;
    }

    /**
     * Whether writing is restricted to the class hierarchy by asymmetric visibility, e.g. public protected(set) (PHP 8.4+).
     * Always false before PHP 8.4.
     */
    public function isProtectedSet(): bool
    {
        return method_exists($this->native, 'isProtectedSet') && $this->native->isProtectedSet() === true;
    }

    /**
     * Whether the property declares any hook (PHP 8.4+). Always false before PHP 8.4.
     */
    public function hasHooks(): bool
    {
        return $this->nativeHooks() !== [];
    }

    /**
     * Whether the property declares the given hook (PHP 8.4+). Always false before PHP 8.4.
     */
    public function hasHook(PropertyHook $hook): bool
    {
        return isset($this->nativeHooks()[$hook->value]);
    }

    /**
     * The given hook as a method (PHP 8.4+).
     *
     * @throws PropertyHookNotFound When the property does not declare the hook, which is always the case before PHP 8.4.
     */
    public function hook(PropertyHook $hook): ReflectedMethod
    {
        return ReflectedMethod::fromNative($this->nativeHooks()[$hook->value] ?? throw PropertyHookNotFound::on($this->qualifiedName(), $hook));
    }

    /**
     * The fully qualified name of the declaration, e.g. "App\Models\User::$email".
     */
    public function qualifiedName(): string
    {
        return "{$this->native->class}::\${$this->native->getName()}";
    }

    /**
     * @return array<ReflectionMethod>
     */
    private function nativeHooks(): array
    {
        $hooks = method_exists($this->native, 'getHooks') ? $this->native->getHooks() : [];

        return is_array($hooks) ? array_filter($hooks, static fn (mixed $hook): bool => $hook instanceof ReflectionMethod) : [];
    }

    private function instanceFor(?object $object): ?object
    {
        return $object ?? ($this->native->isStatic() ? null : throw InstanceRequired::forProperty($this->qualifiedName()));
    }

    private function typeScope(): string
    {
        return $this->native->class;
    }
}
