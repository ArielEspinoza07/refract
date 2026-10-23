# Types

`ReflectedType` wraps a declared type: of a property, parameter, return value, typed constant or enum backing.

```php
$type = ReflectedProperty::from(Order::class, 'total')->type();   // int|float|null

$type->kind();          // TypeKind::Union
$type->name();          // 'int|float|null'
$type->nonNullName();   // 'int|float'
$type->allows(9.99);    // true
```

| Method | Returns | Description |
|---|---|---|
| `kind()` | `TypeKind` | `Named`, `Union` or `Intersection`. `?int` is `Named`; DNF types like `(A&B)\|null` are `Union` |
| `name()` | `string` | As PHP prints it: `?int`, `string\|int`, `(A&B)\|null` |
| `nonNullName()` | `string` | Without the part that allows null: `int` for `?int`. `mixed` and `null` stay unchanged |
| `allowsNull()` | `bool` | |
| `isBuiltin()` | `bool` | A single built-in type like `int` or `mixed` |
| `types()` | `Collection<ReflectedType>` | The members of a union or intersection; a named type contains only itself |
| `allows($value)` | `bool` | Whether the value fits, as PHP checks it with `strict_types=1` |
| `classNames()` | `list<string>` | The classes and interfaces the type accepts |
| `native()` | `ReflectionType` | |
| `fromNative($native, $scope)` | `ReflectedType` | `$scope` is the class `self`, `static` and `parent` refer to |

PHP reports union members in its own order: `int|string` is printed as `string|int`.

## Checking values: allows()

`allows()` applies the same rules PHP applies to arguments under `strict_types=1`: no coercion, except that an `int`
is accepted where a `float` is expected. `self`, `static` and `parent` are resolved against the class the type is
declared in; a type created with `fromNative()` and no scope throws `TypeNotResolvable` for them.

## Finding classes: classNames()

Built-in types are skipped, nested DNF types are flattened, duplicates are removed, and `self`, `static` and `parent`
are resolved:

| Type | `classNames()` |
|---|---|
| `int`, `?string` | `[]` |
| `?User` | `['App\User']` |
| `(Countable&Traversable)\|null` | `['Countable', 'Traversable']` |
| `static` | the declaring class |

Like `allows()`, it throws `TypeNotResolvable` for `self`, `static` or `parent` on a type created without a scope.
Names are returned as declared and not checked for existence, so nothing is autoloaded. A dependency injection
container can pass them to `ReflectedClass::tryFrom()` to verify them.

## Converting values: nonNullName()

To convert a string before checking it, decide by the type without its nullability:

```php
$converted = match ($parameter->type()?->nonNullName()) {
    'int' => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
    'float' => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
    default => $value,
};

$parameter->type()?->allows($converted);
```
