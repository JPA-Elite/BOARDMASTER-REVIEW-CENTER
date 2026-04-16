<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'total_questions',
        'score',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function answers(): HasMany
    {
        // Preserve the submission order of answers (createMany inserts sequential IDs).
        // Without an explicit order, MySQL may return rows ordered by an index such as
        // (quiz_attempt_id, question_id), which reorders question/answer display in the app.
        return $this->hasMany(UserAnswer::class)->orderBy('id');
    }
}
