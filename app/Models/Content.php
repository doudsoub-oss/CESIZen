<?php

namespace App\Models;

use App\Enums\ContentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['category_id', 'title', 'slug', 'excerpt', 'body', 'type', 'is_published', 'published_at', 'created_by'])]
class Content extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'type' => ContentType::class,
        ];
    }

    /**
     * Render the Markdown body to HTML for public display. Raw HTML is escaped
     * and unsafe links are stripped as defense in depth, even though bodies are
     * authored by administrators. Appended on demand by the public viewers.
     */
    protected function bodyHtml(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Str::markdown((string) $this->body, [
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
            ]),
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
