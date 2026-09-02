<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $score
 */
#[Fillable(['diagnostic_id', 'question_id', 'answer_option_id', 'score'])]
class DiagnosticResponse extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            // Chiffrés au repos (L05) : le score de la réponse et la réponse
            // choisie elle-même. Chiffrer answer_option_id retire la jointure SQL
            // qui reconstruirait le score (scénario R1). Les libellés de réponse
            // se chargent ensuite via Eloquent (clés collectées en PHP).
            'score' => 'encrypted',
            'answer_option_id' => 'encrypted',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DiagnosticResponse $response) {
            $response->created_at = now();
            if (! $response->score && $response->answerOption) {
                $response->score = $response->answerOption->score;
            }
        });
    }

    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(Diagnostic::class);
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** @return BelongsTo<AnswerOption, $this> */
    public function answerOption(): BelongsTo
    {
        return $this->belongsTo(AnswerOption::class);
    }
}
