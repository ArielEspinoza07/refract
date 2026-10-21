<?php

declare(strict_types=1);

namespace Refract\Examples\Container;

interface Logger
{
    public function log(string $message): void;
}
