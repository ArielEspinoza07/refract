# PHP 8.4 features

Refract supports PHP 8.3 and later. Features that only exist since PHP 8.4 are available on every version, with a
safe fallback before 8.4: methods that inspect return `false`, and methods that create something throw
`FeatureNotSupported`.

## Properties

| Method | Returns | Description | Before 8.4 |
|---|---|---|---|
| `isVirtual()` | `bool` | Has no storage of its own: its hooks never touch the backing value | `false` |
| `isFinal()` / `isAbstract()` | `bool` | Declared `final` or `abstract`. `private(set)` properties are implicitly final | `false` |
| `isPrivateSet()` / `isProtectedSet()` | `bool` | Asymmetric visibility: `public private(set)` or `public protected(set)` | `false` |
| `hasHooks()` | `bool` | Declares any hook | `false` |
| `hasHook($hook)` | `bool` | Declares `PropertyHook::Get` or `PropertyHook::Set` | `false` |
| `hook($hook)` | `ReflectedMethod` | The hook as a method; throws `PropertyHookNotFound` when it is not declared | always throws |

```php
use Refract\Enums\PropertyHook;

$property = ReflectedProperty::from(User::class, 'fullName');

if ($property->hasHook(PropertyHook::Get)) {
    $property->hook(PropertyHook::Get)->returnType();
}
```

Reading a hooked property with `value()` runs its `get` hook, as reading it directly would.

## Lazy objects

| Method | Returns | Description | Before 8.4 |
|---|---|---|---|
| `newLazyGhost($initializer)` | `T` | An uninitialized object whose initializer fills it in on first access | throws `FeatureNotSupported` |
| `newLazyProxy($factory)` | `T` | An uninitialized object whose factory returns the real instance on first access | throws `FeatureNotSupported` |
| `isUninitializedLazyObject($object)` | `bool` | Whether a lazy object has not been initialized yet | `false` |

```php
$class = ReflectedClass::from(User::class);

$user = $class->newLazyGhost(function (User $user): void {
    $user->__construct('ada@example.com');
});

$class->isUninitializedLazyObject($user);   // true
$user->email;                              // runs the initializer
```

When PHP cannot make the class lazy, for example because it is abstract or internal, both methods throw
`ClassNotInstantiable`.
