<?php

declare(strict_types=1);

namespace Refract\Enums;

/**
 * A property hook (PHP 8.4+).
 */
enum PropertyHook: string
{
    case Get = 'get';
    case Set = 'set';
}
