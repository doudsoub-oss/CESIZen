<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'questionnaire_id', 'score_total', 'result_interpretation_id', 'completed_at', 'consented_at'])]
class Diagnostic extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            // Chiffré au repos (L05). Restitué en clair côté application ; la
            // valeur déchiffrée est une chaîne numérique.
            'score_total' => 'encrypted',
            'completed_at' => 'datetime',
            'consented_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function resultInterpretation(): BelongsTo
    {
        return $this->belongsTo(ResultInterpretation::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(DiagnosticResponse::class);
    }
}
