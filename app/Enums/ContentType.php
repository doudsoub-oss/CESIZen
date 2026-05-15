<?php

namespace App\Enums;

enum ContentType: string
{
    case Page = 'page';
    case Article = 'article';
    case Resource = 'resource';

    public function label(): string
    {
        return match ($this) {
            self::Page => 'Page',
            self::Article => 'Article',
            self::Resource => 'Ressource',
        };
    }

    public function requiresCategory(): bool
    {
        return $this !== self::Page;
    }
}
