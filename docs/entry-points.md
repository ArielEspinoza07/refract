# Entry points

Every reflection type is created through the same static methods. Constructors are private.

| Method | Returns | When the target does not exist |
|---|---|---|
| `from(...)` | one reflection | throws a specific exception, like `ClassNotFound` or `MethodNotFound` |
| `tryFrom(...)` | one reflection or `null` | returns `null` |
| `collectFrom(...)` | a typed collection | throws for an unknown class or function |
| `fromNative($native)` | wraps an existing native `Reflection*` object | — |

`native()` returns the wrapped native object.

## By type

| Type | `from()` / `tryFrom()` arguments | `collectFrom()` |
|---|---|---|
| `ReflectedClass` | a class name, or any object | — |
| `ReflectedEnum` | an enum name, or a case | — |
| `ReflectedMethod` | class name or object, method name | all methods, as a `MethodCollection` |
| `ReflectedProperty` | class name or object, property name | all properties, as a `PropertyCollection` |
| `ReflectedConstant` | class name or object, constant or case name | all constants, as a `ConstantCollection` |
| `ReflectedAttribute` | class name or object, attribute class | the class attributes, optionally of one type |
| `ReflectedFunction` | a function name, or a closure | — |
| `ReflectedParameter` | any callable, parameter name or zero-based position | all parameters, as a `ParameterCollection` |
| `ReflectedCallable` | any callable | — |

`ReflectedType` has only `fromNative()`, because a type is always reached through what it belongs to, such as
`ReflectedProperty::type()`.

```php
ReflectedClass::from(User::class);
ReflectedClass::from($user);                       // an object is reflected through ReflectionObject,
                                                   // so properties added at runtime are visible
ReflectedMethod::from(User::class, 'save');
ReflectedProperty::tryFrom($user, 'email');
ReflectedParameter::from([UserController::class, 'show'], 'id');
ReflectedMethod::collectFrom(User::class)->public();
ReflectedClass::fromNative(new ReflectionClass(User::class));
```

## Objects are reflected as themselves

`from()` reflects the class of whatever object it is given, even a native reflection: `ReflectedClass::from($native)`
reflects `ReflectionClass` itself. To wrap a native reflection, use `fromNative()`.

## Untrusted input

`tryFrom()` accepts any string, and `ReflectedCallable::tryFrom()` and `ReflectedParameter::tryFrom()` accept any
value, including malformed arrays. They return `null` instead of throwing or emitting warnings, so they can be used
directly on user input.

Looking up a class by name still runs the autoloader, which loads that class's file. When the name comes from user
input, restrict it to a known set. See [SECURITY.md](../SECURITY.md).

## Types for static analysis

`from()` keeps the reflected type: `ReflectedClass::from(User::class)` is a `ReflectedClass<User>`, so
`newInstance()` returns a `User`, and `ReflectedAttribute::from(User::class, Table::class)` is a
`ReflectedAttribute<Table>`.

`tryFrom()` keeps it for objects. For strings it cannot, because PHPStan treats a class name inside a union as a
plain string; use `from()` when the class name is known to be valid.
