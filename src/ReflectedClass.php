<?php

declare(strict_types=1);

namespace Refract;

use Error;
use LogicException;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use Refract\Collections\Collection;
use Refract\Collections\ConstantCollection;
use Refract\Collections\MethodCollection;
use Refract\Collections\PropertyCollection;
use Refract\Concerns\InteractsWithAttributes;
use Refract\Concerns\InteractsWithDocComment;
use Refract\Concerns\InteractsWithSource;
use Refract\Concerns\PreventsSerialization;
use Refract\Contracts\HasAttributes;
use Refract\Contracts\Named;
use Refract\Enums\ClassKind;
use Refract\Exceptions\ClassNotFound;
use Refract\Exceptions\ClassNotInstantiable;
use Refract\Exceptions\ConstantNotFound;
use Refract\Exceptions\EnumNotFound;
use Refract\Exceptions\FeatureNotSupported;
use Refract\Exceptions\MethodNotFound;
use Refract\Exceptions\PropertyNotFound;
use UnitEnum;

/**
 * Fluent, immutable reflection of a class, interface, trait or enum.
 *
 * @template T of object
 */
final readonly class ReflectedClass implements HasAttributes, Named
{
    use InteractsWithAttributes;
    use InteractsWithDocComment;
    use InteractsWithSource;
    use PreventsSerialization;

    /**
     * @param  ReflectionClass<T>  $native
     */
    private function __construct(
        private ReflectionClass $native,
    ) {}

    /**
     * Reflect a class-like structure by name, or the class of the given object.
     *
     * Any object is reflected as itself: passing a ReflectionClass reflects ReflectionClass.
     * Use fromNative() to wrap an existing native reflection instead.
     *
     * An object is reflected through ReflectionObject, so properties added to that instance
     * at runtime are included.
     *
     * @template TClass of object
     *
     * @param  class-string<TClass>|TClass  $class
     * @return self<TClass>
     *
     * @throws ClassNotFound When the given name is not a class, interface, trait or enum.
     */
    public static function from(string|object $class): self
    {
        if (is_object($class)) {
            return new self(new ReflectionObject($class));
        }

        try {
            return new self(new ReflectionClass($class));
        } catch (ReflectionException $exception) {
            throw ClassNotFound::named($class, $exception);
        }
    }

    /**
     * Like from(), but returns null instead of throwing when the class does not exist.
     *
     * Accepts any string, so it can be used directly on untrusted input. An object always
     * resolves, so the result is only nullable for strings. For a class name known to be
     * valid, prefer from(): it keeps the reflected class type.
     *
     * @template TClass of object
     *
     * @param  TClass|string  $class
     * @return ($class is string ? self<object>|null : self<TClass>)
     */
    public static function tryFrom(string|object $class): ?self
    {
        if (is_object($class)) {
            return new self(new ReflectionObject($class));
        }

        // class_exists() also covers enums.
        if (! class_exists($class) && ! interface_exists($class) && ! trait_exists($class)) {
            return null;
        }

        return new self(new ReflectionClass($class));
    }

    /**
     * Wrap an existing native reflection.
     *
     * @template TClass of object
     *
     * @param  ReflectionClass<TClass>  $native
     * @return self<TClass>
     */
    public static function fromNative(ReflectionClass $native): self
    {
        return new self($native);
    }

    /**
     * @return ReflectionClass<T>
     */
    public function native(): ReflectionClass
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
     * The namespace without a leading backslash, or an empty string for the global namespace.
     */
    public function namespace(): string
    {
        return $this->native->getNamespaceName();
    }

    public function kind(): ClassKind
    {
        return match (true) {
            $this->native->isInterface() => ClassKind::Interface,
            $this->native->isTrait() => ClassKind::Trait,
            $this->native->isEnum() => ClassKind::Enum,
            default => ClassKind::Standard,
        };
    }

    public function isAbstract(): bool
    {
        return $this->native->isAbstract();
    }

    public function isFinal(): bool
    {
        return $this->native->isFinal();
    }

    public function isReadonly(): bool
    {
        return $this->native->isReadOnly();
    }

    public function isAnonymous(): bool
    {
        return $this->native->isAnonymous();
    }

    /**
     * Whether the class can be created with `new`: false for interfaces, traits, enums,
     * abstract classes and classes without a public constructor.
     */
    public function isInstantiable(): bool
    {
        return $this->native->isInstantiable();
    }

    public function isCloneable(): bool
    {
        return $this->native->isCloneable();
    }

    /**
     * Whether the class implements Traversable, so its instances work with foreach.
     */
    public function isIterable(): bool
    {
        return $this->native->isIterable();
    }

    /**
     * Whether the given object is an instance of this class, including subclasses and implementations.
     *
     * @phpstan-assert-if-true T $object
     */
    public function isInstance(object $object): bool
    {
        return $this->native->isInstance($object);
    }

    /**
     * Default values of all declared properties, static ones included, keyed by property name.
     * Typed properties without a default are left out.
     *
     * @return array<string, mixed>
     */
    public function defaultProperties(): array
    {
        return $this->native->getDefaultProperties();
    }

    /**
     * The current value of a static property.
     *
     * @throws PropertyNotFound When the class has no static property with that name.
     */
    public function staticPropertyValue(string $name): mixed
    {
        try {
            return $this->native->getStaticPropertyValue($name);
        } catch (ReflectionException $exception) {
            throw PropertyNotFound::in($this->name(), $name, $exception);
        }
    }

    /**
     * @return self<object>|null Null when the class does not extend another class.
     */
    public function parent(): ?self
    {
        $parent = $this->native->getParentClass();

        return $parent === false ? null : new self($parent);
    }

    /**
     * All implemented interfaces, including those inherited from parents and other interfaces.
     *
     * @return Collection<self<object>>
     */
    public function interfaces(): Collection
    {
        return new Collection(array_values(array_map(self::fromNative(...), $this->native->getInterfaces())));
    }

    /**
     * Traits used directly by this class. Traits used by parent classes are not included.
     *
     * @return Collection<self<object>>
     */
    public function traits(): Collection
    {
        return new Collection(array_values(array_map(self::fromNative(...), $this->native->getTraits())));
    }

    /**
     * @param  class-string  $class
     *
     * @throws ClassNotFound When the given class does not exist.
     */
    public function isSubclassOf(string $class): bool
    {
        try {
            return $this->native->isSubclassOf($class);
        } catch (ReflectionException $exception) {
            throw ClassNotFound::named($class, $exception);
        }
    }

    /**
     * Whether this class implements the given interface. Always false when the given
     * name is a class, trait or enum rather than an interface.
     *
     * @param  class-string  $interface
     *
     * @throws ClassNotFound When the given name does not exist.
     */
    public function implements(string $interface): bool
    {
        if (interface_exists($interface)) {
            return $this->native->implementsInterface($interface);
        }

        if (class_exists($interface) || trait_exists($interface)) {
            return false;
        }

        throw ClassNotFound::named($interface);
    }

    /**
     * All methods, including inherited ones. Use declaredBy() on the result to exclude them.
     */
    public function methods(): MethodCollection
    {
        return new MethodCollection(array_map(
            static fn (ReflectionMethod $method): ReflectedMethod => ReflectedMethod::fromNative($method),
            $this->native->getMethods(),
        ));
    }

    /**
     * Whether the method exists, including inherited methods.
     */
    public function hasMethod(string $name): bool
    {
        return $this->native->hasMethod($name);
    }

    /**
     * @throws MethodNotFound
     */
    public function method(string $name): ReflectedMethod
    {
        if (! $this->native->hasMethod($name)) {
            throw MethodNotFound::in($this->name(), $name);
        }

        return ReflectedMethod::fromNative($this->native->getMethod($name));
    }

    public function constructor(): ?ReflectedMethod
    {
        $constructor = $this->native->getConstructor();

        return $constructor === null ? null : ReflectedMethod::fromNative($constructor);
    }

    /**
     * All properties, including inherited non-private ones. Use declaredBy() on the result to exclude them.
     */
    public function properties(): PropertyCollection
    {
        return new PropertyCollection(array_map(
            static fn (ReflectionProperty $property): ReflectedProperty => ReflectedProperty::fromNative($property),
            $this->native->getProperties(),
        ));
    }

    public function hasProperty(string $name): bool
    {
        return $this->native->hasProperty($name);
    }

    /**
     * @throws PropertyNotFound
     */
    public function property(string $name): ReflectedProperty
    {
        if (! $this->native->hasProperty($name)) {
            throw PropertyNotFound::in($this->name(), $name);
        }

        return ReflectedProperty::fromNative($this->native->getProperty($name));
    }

    /**
     * All constants, including inherited non-private ones and enum cases.
     */
    public function constants(): ConstantCollection
    {
        return new ConstantCollection(array_map(
            static fn (ReflectionClassConstant $constant): ReflectedConstant => ReflectedConstant::fromNative($constant),
            $this->native->getReflectionConstants(),
        ));
    }

    public function hasConstant(string $name): bool
    {
        return $this->native->hasConstant($name);
    }

    /**
     * @throws ConstantNotFound
     */
    public function constant(string $name): ReflectedConstant
    {
        $constant = $this->native->getReflectionConstant($name);

        if ($constant === false) {
            throw ConstantNotFound::in($this->name(), $name);
        }

        return ReflectedConstant::fromNative($constant);
    }

    /**
     * Create an instance, passing the given arguments to the constructor.
     * Named arguments are supported.
     *
     * @return T
     *
     * @throws ClassNotInstantiable When the class is not instantiable.
     */
    public function newInstance(mixed ...$arguments): object
    {
        if (! $this->native->isInstantiable()) {
            throw ClassNotInstantiable::named($this->name());
        }

        return $this->native->newInstanceArgs($arguments);
    }

    /**
     * Create an instance without calling the constructor. Properties are left uninitialized.
     *
     * Unlike newInstance(), this works on classes with a non-public constructor.
     *
     * @return T
     *
     * @throws ClassNotInstantiable When the class is abstract, not a standard class,
     *                              or an internal final class.
     */
    public function newInstanceWithoutConstructor(): object
    {
        if ($this->kind() !== ClassKind::Standard || $this->native->isAbstract()) {
            throw ClassNotInstantiable::named($this->name());
        }

        try {
            return $this->native->newInstanceWithoutConstructor();
        } catch (ReflectionException $exception) {
            throw ClassNotInstantiable::named($this->name(), $exception);
        }
    }

    /**
     * Create a lazy ghost (PHP 8.4+): an uninitialized instance whose initializer runs on first access
     * and fills in the properties of that same object.
     *
     * @param  callable(T): void  $initializer
     * @return T
     *
     * @throws FeatureNotSupported Before PHP 8.4.
     * @throws ClassNotInstantiable When PHP cannot make the class lazy, like abstract or internal classes.
     */
    public function newLazyGhost(callable $initializer): object
    {
        method_exists($this->native, 'newLazyGhost') || throw FeatureNotSupported::requires('Lazy objects', '8.4');

        try {
            return $this->asInstance($this->native->newLazyGhost($initializer));
        } catch (Error $error) {
            throw ClassNotInstantiable::named($this->name(), $error);
        }
    }

    /**
     * Create a lazy proxy (PHP 8.4+): an uninitialized instance whose factory runs on first access
     * and returns the real instance all access is then forwarded to.
     *
     * @param  callable(T): T  $factory
     * @return T
     *
     * @throws FeatureNotSupported Before PHP 8.4.
     * @throws ClassNotInstantiable When PHP cannot make the class lazy, like abstract or internal classes.
     */
    public function newLazyProxy(callable $factory): object
    {
        method_exists($this->native, 'newLazyProxy') || throw FeatureNotSupported::requires('Lazy objects', '8.4');

        try {
            return $this->asInstance($this->native->newLazyProxy($factory));
        } catch (Error $error) {
            throw ClassNotInstantiable::named($this->name(), $error);
        }
    }

    /**
     * The class as an enum, for what only enums have: cases and a backing type.
     * Check kind() first when the class may not be an enum.
     *
     * @return ReflectedEnum<UnitEnum>
     *
     * @throws EnumNotFound When the class is not an enum.
     */
    public function asEnum(): ReflectedEnum
    {
        return ReflectedEnum::tryFrom($this->name()) ?? throw EnumNotFound::named($this->name());
    }

    /**
     * Whether the object is a lazy ghost or proxy that has not been initialized yet (PHP 8.4+).
     * Always false before PHP 8.4.
     */
    public function isUninitializedLazyObject(object $object): bool
    {
        return method_exists($this->native, 'isUninitializedLazyObject') && $this->native->isUninitializedLazyObject($object) === true;
    }

    /**
     * The fully qualified name of the declaration, the same as name(), e.g. "App\Models\User".
     */
    public function qualifiedName(): string
    {
        return $this->name();
    }

    /**
     * @return T
     */
    private function asInstance(mixed $value): object
    {
        return is_object($value) && $this->isInstance($value) ? $value : throw new LogicException('PHP returned an instance of another class.');
    }
}
