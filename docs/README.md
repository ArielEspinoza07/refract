# Refract documentation

The [README](../README.md) gives a quick tour. These pages describe every public method, grouped by what you want
to inspect.

## Which type do I need?

| To inspect… | Use | Page |
|---|---|---|
| a class, interface or trait | `ReflectedClass` | [Classes and enums](classes-and-enums.md) |
| an enum and its cases | `ReflectedEnum` | [Classes and enums](classes-and-enums.md) |
| a method, property or constant | `ReflectedMethod`, `ReflectedProperty`, `ReflectedConstant` | [Class members](members.md) |
| a function or closure | `ReflectedFunction` | [Functions and parameters](functions.md) |
| any callable, without knowing its form | `ReflectedCallable` | [Functions and parameters](functions.md) |
| a parameter | `ReflectedParameter` | [Functions and parameters](functions.md) |
| an attribute | `ReflectedAttribute` | [Attributes](attributes.md) |
| a declared type | `ReflectedType` | [Types](types.md) |
| lists of any of the above | `Collection` and its subclasses | [Collections](collections.md) |

## Topics

- [Entry points](entry-points.md): `from()`, `tryFrom()`, `collectFrom()` and `fromNative()`, and untrusted input.
- [Errors](errors.md): every exception, and why reflections cannot be serialized.
- [PHP 8.4 features](php-8.4.md): property hooks, asymmetric visibility and lazy objects, with their PHP 8.3 fallbacks.

## Conventions

- **Immutable.** Reflections and collections never change after creation; filters return new collections.
- **Standalone.** Every type has its own entry points; you never have to go through `ReflectedClass` first.
- **Typed.** Generic types flow through for PHPStan: `ReflectedClass::from(User::class)->newInstance()` is a `User`.
- **Escape hatch.** `native()` returns the underlying native `Reflection*` object whenever you need something
  Refract does not cover.
