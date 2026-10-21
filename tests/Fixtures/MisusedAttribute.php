<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

/**
 * Declares an attribute where PHP refuses to instantiate it, to test error handling.
 */
final class MisusedAttribute
{
    /** @phpstan-ignore attribute.target (deliberate: Table only allows class targets, so newInstance() must fail) */
    #[Table('misused')]
    public function handle(): void {}
}
