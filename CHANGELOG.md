# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-10-23

### Added

- `ReflectedCallable::from()` and `tryFrom()` reflect any callable as the `ReflectedFunction` or `ReflectedMethod` it
  refers to: a closure, a function name, `"Class::method"`, `[$classOrObject, 'method']` or an invokable object.
  First-class callables such as `$user->save(...)` resolve to the method they were created from.
- Reference documentation in [`docs/`](docs/README.md), describing every public method, exception and PHP 8.4 fallback.

### Changed

- `ReflectedParameter::from()`, `tryFrom()` and `collectFrom()` resolve callables through `ReflectedCallable`, so a
  parameter of a first-class callable now belongs to its `ReflectedMethod` instead of a `ReflectedFunction`.
- Callable strings using `self::`, `parent::` or `static::` are rejected with `InvalidFunctionReference`.
- Faster `hasAttribute()`, `withAttribute()` and method and parameter lookups.

## [0.1.0] - 2026-10-21

First release: a fluent, immutable and fully typed API for PHP's native reflection. Requires PHP 8.3 or later and has
no runtime dependencies.

### Added

#### Entry points

- The same static entry points on every reflection type: `from()` throws a specific exception when the target does not
  exist, `tryFrom()` returns `null` instead, and `fromNative()` wraps an existing native `Reflection*` object. Member
  and parameter types also have `collectFrom()`, which returns a typed collection.
- `tryFrom()` is safe for untrusted input: it returns `null` for unknown names and malformed values instead of
  throwing or emitting warnings.
- `native()` on every type returns the underlying native reflection.

#### Reflection types

- `ReflectedClass`
  - identity (`name()`, `shortName()`, `namespace()`) and `kind()`
  - modifiers: `isAbstract()`, `isFinal()`, `isReadonly()`, `isAnonymous()`, `isInstantiable()`, `isCloneable()`,
    `isIterable()`, `isInternal()`, `isUserDefined()`
  - hierarchy: `parent()`, `interfaces()`, `traits()`, `isSubclassOf()`, `implements()`, `isInstance()`
  - members: `methods()`, `properties()`, `constants()`, `attributes()` and their single-item lookups
  - `defaultProperties()`, `staticPropertyValue()`, source location and `docComment()`
  - instantiation: `newInstance()` (typed as the reflected class) and `newInstanceWithoutConstructor()`
  - `asEnum()` to switch to the enum view
  - Objects are reflected through `ReflectionObject`, so properties added at runtime are visible.
- `ReflectedMethod`
  - `isAbstract()`, `isFinal()`, `isConstructor()`, `isDestructor()`
  - `prototype()`: the parent or interface method it overrides
  - `invoke()` and `closure()`, regardless of visibility, with named arguments
- `ReflectedFunction` for named functions and closures
  - `isAnonymous()`, `isClosure()`, `closureThis()`, `closureScopeClass()`
  - `staticVariables()`, including variables captured with `use`
  - `invoke()` and `closure()`
- Shared by methods and functions (`FunctionLike`)
  - `parameters()`, `parameter()` by name or position, `returnType()`
  - `isStatic()`, `isVariadic()`, `isGenerator()`, `returnsReference()`, `isDeprecated()`
  - source location and `docComment()`
- `ReflectedProperty`
  - `type()`
  - `isStatic()`, `isReadonly()`, `isPromoted()`, `isDynamic()`
  - `hasDefaultValue()` and `defaultValue()`
  - `value()` regardless of visibility, and `isInitialized()`
  - `docComment()`
- `ReflectedConstant` for class constants and enum cases
  - `value()`, `backingValue()`, `type()` (typed constants), `isFinal()`, `isEnumCase()`, `docComment()`
- Shared by methods, properties and constants (`ClassMember`)
  - `isPublic()`, `isProtected()`, `isPrivate()`, `declaringClass()`, `isDeclaredBy()`
- `qualifiedName()` on classes, enums, methods, properties, constants, functions and parameters, such as
  `App\User::save()`, `App\User::$email` or `App\User::save($data)`
- `ReflectedParameter`
  - accepts every callable form: a closure, a function name, `"Class::method"`, `[$classOrObject, 'method']`, or an
    invokable object
  - `position()`, `type()`, `allowsNull()`
  - `isOptional()`, `isVariadic()`, `isPassedByReference()`, `isPromoted()`
  - `hasDefaultValue()` and `defaultValue()`
  - `declaringFunction()` and `declaringClass()`
- `ReflectedEnum`
  - `isBacked()`, `backingType()`
  - `cases()`, `case()`, `hasCase()`
  - `asClass()` to switch to the class view
- `ReflectedAttribute`
  - `arguments()` as declared (positional and named), `target()`, `isRepeated()`
  - `newInstance()`, typed as the attribute class
- Attributes on classes, enums, methods, functions, properties, constants and parameters (`HasAttributes`)
  - `attributes()`, optionally filtered by type, plus `attribute()` and `hasAttribute()`
  - `attributeInstances()`: the attribute objects of a type, created and typed
  - Subclasses of the requested attribute are matched.
- `ReflectedType` for named, union, intersection and DNF types
  - `kind()`, `name()`, `nonNullName()`, `allowsNull()`, `isBuiltin()`
  - `types()`: the members of a union or intersection
  - `allows($value)`: checks a value the way PHP does with `strict_types=1`
  - `classNames()`: the classes a type accepts, with DNF types flattened and `self`, `static` and `parent` resolved

#### Collections

- Immutable, ordered collections, iterable and countable. Every operation returns a new collection.
  - `all()`, `first()` and `last()` with an optional condition
  - `isEmpty()`, `isNotEmpty()`, `count()`, `any()`, `every()`
  - `filter()`, `reject()`, `map()`
  - `names()` and `keyByName()`
- Methods, properties and constants filter by `public()`, `protected()`, `private()`, `declaredBy()` and
  `withAttribute()`, plus:
  - `MethodCollection`: `static()`, `abstract()`, `final()`
  - `PropertyCollection`: `static()`, `readonly()`, `promoted()`
  - `ConstantCollection`: `final()`
- `ParameterCollection` filters by `required()`, `optional()`, `variadic()`, `promoted()` and `withAttribute()`.

#### Enums and contracts

- Enums: `ClassKind`, `TypeKind`, `AttributeTarget` and `PropertyHook`.
- Contracts for writing code that works across reflection types: `Named`, `HasAttributes`, `ClassMember`,
  `FunctionLike` and `MemberCollection`.

#### Static analysis

- Generic types flow through the API for PHPStan. For example, `ReflectedClass::from(User::class)->newInstance()`
  is typed as `User`, and `attribute(Route::class)` as `ReflectedAttribute<Route>`.

#### Errors

- A dedicated exception for every failure, such as `ClassNotFound`, `MethodNotFound`, `InstanceRequired` or
  `TypeNotResolvable`. All of them implement `RefractException`, and native `ReflectionException`s never reach user
  code.
- Reflections refuse to be serialized or unserialized. They throw `NotSerializable`, which explains to store the name
  and call `from()` again, instead of PHP's error about the native reflection inside.

#### PHP 8.4+

- `ReflectedProperty`: `isVirtual()`, `isFinal()`, `isAbstract()`, `isPrivateSet()`, `isProtectedSet()`, `hasHooks()`,
  `hasHook()` and `hook()`.
- `ReflectedClass`: `newLazyGhost()`, `newLazyProxy()` and `isUninitializedLazyObject()`.
- On PHP 8.3, the inspection methods return `false`, and creating a lazy object throws `FeatureNotSupported`.

[Unreleased]: https://github.com/arielespinoza07/refract/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/arielespinoza07/refract/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/arielespinoza07/refract/releases/tag/v0.1.0
