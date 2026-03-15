<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanSubjectAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'subject_id',
        'question_limit',
    ];

    protected function casts(): array
    {
        return [
            'question_limit' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
