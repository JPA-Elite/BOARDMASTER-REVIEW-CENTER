<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionChoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'choice_label',
        'choice_text',
        'is_correct',
        'explanation',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class, 'selected_choice_id');
    }
}
