<?php

declare(strict_types=1);

namespace Refract\Concerns;

use Refract\Exceptions\NotSerializable;

/**
 * Makes serialization fail with a clear RefractException instead of PHP's error about the native
 * reflection inside, which names a class the user never touched.
 *
 * Unserializing is refused too, so a crafted string cannot create an instance without its constructor.
 *
 * @internal
 */
trait PreventsSerialization
{
    /**
     * @throws NotSerializable Always.
     */
    public function __serialize(): never
    {
        throw NotSerializable::type(static::class);
    }

    /**
     * @param  array<mixed>  $data
     *
     * @throws NotSerializable Always.
     */
    public function __unserialize(array $data): never
    {
        throw NotSerializable::type(static::class);
    }
}
