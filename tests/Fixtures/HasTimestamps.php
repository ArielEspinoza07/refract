<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

trait HasTimestamps
{
    public ?int $touchedAt = null;

    public function touch(): void
    {
        $this->touchedAt = time();
    }
}
