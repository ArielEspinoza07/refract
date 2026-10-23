# Errors

Every failure has its own exception, and all of them implement `Refract\Exceptions\RefractException`. Native
`ReflectionException`s never reach your code.

```php
use Refract\Exceptions\RefractException;

try {
    $method = ReflectedMethod::from($class, $name);
} catch (RefractException $exception) {
    // anything Refract throws
}
```

## Something does not exist

These extend `InvalidArgumentException`.

| Exception | Thrown when |
|---|---|
| `ClassNotFound` | a class, interface, trait or enum name does not exist |
| `EnumNotFound` | a name is not an enum, in `ReflectedEnum::from()` and `ReflectedClass::asEnum()` |
| `FunctionNotFound` | no function has the given name |
| `MethodNotFound` | a class has no such method, or an object has no `__invoke()` |
| `PropertyNotFound` | a class has no such property, or no such static property for `staticPropertyValue()` |
| `ConstantNotFound` | a class has no such constant, or an enum no such case |
| `ParameterNotFound` | a function has no parameter with that name or position |
| `AttributeNotFound` | a declaration does not have the attribute |
| `PropertyHookNotFound` | a property does not declare the hook |
| `InstanceRequired` | an instance method or property is used without an object |
| `InvalidFunctionReference` | a callable array is not `[class-or-object, method]`, or a string uses `self::`, `parent::` or `static::` |

## Something cannot be done

These extend `LogicException`.

| Exception | Thrown when |
|---|---|
| `ClassNotInstantiable` | an abstract class, interface, trait, enum or internal final class is instantiated, or made lazy |
| `AttributeNotInstantiable` | PHP refuses to create an attribute object; the message includes PHP's reason |
| `MethodNotInvocable` | a method without a body, like an interface method, is invoked |
| `DefaultValueNotAvailable` | a property or parameter without a default value is asked for one |
| `TypeNotResolvable` | `self`, `static` or `parent` is checked on a type without a class scope |
| `FeatureNotSupported` | a PHP 8.4 feature, like lazy objects, is used on an older PHP version |
| `NotSerializable` | a reflection is serialized or unserialized |

Every message names the declaration involved, using the same names as `qualifiedName()`, for example:
`Parameter [$missing] does not exist on [App\User::save()].`

## Reflections cannot be serialized

Every reflection wraps a native `Reflection*` object, which PHP cannot serialize. `serialize()` throws
`NotSerializable` instead of PHP's error about the native object, and `unserialize()` is refused too, so a crafted
string cannot create an instance without its constructor.

Do not store reflections in a cache, a session or a queued job. Store the name you reflected instead, and call
`from()` again when you need it; reflecting is cheap.
