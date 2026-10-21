<?php

declare(strict_types=1);

namespace Refract\Exceptions;

use LogicException;

final class FeatureNotSupported extends LogicException implements RefractException
{
    public static function requires(string $feature, string $phpVersion): self
    {
        return new self("{$feature} require PHP {$phpVersion} or later; running ".PHP_VERSION.'.');
    }
}
