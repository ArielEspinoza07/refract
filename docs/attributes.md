# Attributes

## On every declaration

Classes, enums, methods, functions, properties, constants and parameters share the `HasAttributes` contract.
Every method also matches attributes that extend the requested class.

| Method | Returns | Description |
|---|---|---|
| `attributes($class = null)` | `Collection<ReflectedAttribute>` | All attributes, or only those of a type |
| `hasAttribute($class)` | `bool` | |
| `attribute($class)` | `ReflectedAttribute` | The first match; throws `AttributeNotFound` |
| `attributeInstances($class)` | `list<T>` | The attribute objects, created and typed |

```php
$class->attribute(Table::class)->newInstance()->name;

foreach ($method->attributeInstances(Route::class) as $route) {   // typed as Route
    $routes[] = [$route->verb, $route->path];
}
```

Prefer `attributeInstances()` over `attributes()->map(fn ($attribute) => $attribute->newInstance())`: a closure
parameter typed `ReflectedAttribute` loses the attribute type for PHPStan.

## ReflectedAttribute

```php
use Refract\ReflectedAttribute;

ReflectedAttribute::from(User::class, Table::class);          // the first one on the class
ReflectedAttribute::collectFrom(User::class, Route::class);   // all of them, optionally of one type
```

The static entry points only find class attributes. Attributes on members, functions and parameters are reached
through those reflections, as above.

| Method | Returns | Description |
|---|---|---|
| `name()` | `string` | The fully qualified attribute class name |
| `arguments()` | `array` | As declared: positional arguments under integer keys, named ones under their name |
| `target()` | `AttributeTarget` | `ClassLike`, `Function`, `Method`, `Property`, `ClassConstant` or `Parameter` |
| `isRepeated()` | `bool` | Whether the same attribute appears more than once on the declaration |
| `newInstance()` | `T` | Creates the attribute object |

Reflecting an attribute never loads its class; only `newInstance()` does. When PHP refuses to create it, because the
class does not exist, is not an attribute, does not allow this target or repetition, or the arguments do not fit,
`newInstance()` throws `AttributeNotInstantiable` with PHP's reason. Exceptions from the attribute's own constructor
pass through unchanged.
