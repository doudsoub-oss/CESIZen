<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['diagnostic_id', 'question_id', 'answer_option_id', 'score'])]
class DiagnosticResponse extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'score' => 'integer',
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

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answerOption(): BelongsTo
    {
        return $this->belongsTo(AnswerOption::class);
    }
}
