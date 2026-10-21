<?php

declare(strict_types=1);

use Refract\Exceptions\FeatureNotSupported;

it('names the feature, the required version and the running version', function (): void {
    expect(FeatureNotSupported::requires('Lazy objects', '8.4')->getMessage())
        ->toBe('Lazy objects require PHP 8.4 or later; running '.PHP_VERSION.'.');
});
