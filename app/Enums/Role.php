<?php

namespace App\Enums;

enum Role: string
{
    case User = 'user';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Utilisateur',
            self::Admin => 'Administrateur',
            self::SuperAdmin => 'Super-administrateur',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::User => 0,
            self::Admin => 10,
            self::SuperAdmin => 20,
        };
    }

    public function isAtLeast(self $other): bool
    {
        return $this->level() >= $other->level();
    }
}
