<?php

declare(strict_types=1);

namespace Refract\Tests\Fixtures;

#[Table('users')]
final class User extends Model
{
    use HasTimestamps;

    public const string TABLE = 'users';

    public function __construct(
        public string $email,
    ) {}

    public function save(): void {}
}
