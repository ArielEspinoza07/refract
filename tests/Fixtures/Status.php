<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
