<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'description', 'instructions', 'is_active', 'created_by'])]
class Questionnaire extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function interpretations(): HasMany
    {
        return $this->hasMany(ResultInterpretation::class)->orderBy('min_score');
    }

    public function diagnostics(): HasMany
    {
        return $this->hasMany(Diagnostic::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getInterpretationForScore(int $score): ?ResultInterpretation
    {
        return $this->interpretations()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
    }
}
