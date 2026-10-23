# Collections

Lists of reflections come back as immutable, ordered collections. They are iterable and countable, and every
operation returns a new collection of the same type.

```php
$class->methods()->public()->withAttribute(Route::class)->names();
$class->properties()->promoted()->readonly()->keyByName();
$function->parameters()->every(fn (ReflectedParameter $parameter) => $parameter->type() !== null);
```

## Collection

The base class, also returned directly by `attributes()`, `interfaces()`, `traits()` and `ReflectedType::types()`.

| Method | Returns | Description |
|---|---|---|
| `all()` | `list<T>` | The items, as a plain list for PHP's array functions |
| `first($callback = null)` / `last($callback = null)` | `?T` | The first or last item, or the first or last the callback accepts |
| `count()` / `isEmpty()` / `isNotEmpty()` | `int` / `bool` | |
| `any($callback)` | `bool` | Whether at least one item is accepted; always false when empty |
| `every($callback)` | `bool` | Whether all items are accepted; always true when empty |
| `filter($callback)` / `reject($callback)` | `static` | Keep the accepted, or the rejected, items |
| `map($callback)` | `list<TResult>` | A plain list of whatever the callback returns |
| `names()` | `list<string>` | The names of the items, in order |
| `keyByName()` | `array<string, T>` | The items keyed by name; when names repeat, like repeated attributes, the last wins |
| `getIterator()` | `Iterator<int, T>` | Used by `foreach` |

## Member collections

`MethodCollection`, `PropertyCollection` and `ConstantCollection` share the `MemberCollection` filters:

| Filter | Keeps |
|---|---|
| `public()` / `protected()` / `private()` | members with that visibility |
| `declaredBy($class)` | members declared by that class, not inherited from a parent |
| `withAttribute($class)` | members with that attribute, or one that extends it |

And each adds its own:

| Collection | Filters |
|---|---|
| `MethodCollection` | `static()`, `abstract()`, `final()` |
| `PropertyCollection` | `static()`, `readonly()`, `promoted()` |
| `ConstantCollection` | `final()` |

## ParameterCollection

| Filter | Keeps |
|---|---|
| `required()` | parameters a caller must pass |
| `optional()` | parameters a caller can leave out, see `ReflectedParameter::isOptional()` |
| `variadic()` | the variadic parameter |
| `promoted()` | constructor parameters that also declare a property |
| `withAttribute($class)` | parameters with that attribute, or one that extends it |
