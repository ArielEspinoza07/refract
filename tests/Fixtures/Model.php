<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

abstract class Model implements Identifiable
{
    public const string CONNECTION = 'default';

    protected int $key = 1;

    final public function id(): int
    {
        return $this->key;
    }
}
