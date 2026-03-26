<?php

namespace App\Services\Admin;

use App\Models\Question;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\SubjectRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class QuestionService
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questions,
        private readonly SubjectRepositoryInterface $subjects,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    /**
     * @param array{search?: string, subject_id?: int|null} $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $subjectId = $filters['subject_id'] ?? null;

        $query = $this->questions->query()
            ->with(['subject:id,name', 'creator:id,name'])
            ->withCount('choices')
            ->latest();

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('question', 'like', "%{$search}%")
                    ->orWhereHas('subject', function ($subjectQuery) use ($search) {
                        $subjectQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $questions = $query->paginate(15)->appends([
            'search' => $search,
            'subject_id' => $subjectId,
        ]);

        $questions->through(fn (Question $question) => [
            'id' => $question->id,
            'subject' => $question->subject?->name,
            'question' => $question->question,
            'created_by' => $question->creator?->name,
            'choices_count' => $question->choices_count,
            'created_at' => $question->created_at?->format('M d, Y'),
        ]);

        return $questions;
    }

    public function listSubjects(): Collection
    {
        return $this->subjects->listAll();
    }

    public function listAdmins(): Collection
    {
        return $this->users->listAdmins();
    }

    public function create(array $data): Question
    {
        $choices = $this->normalizeChoices($data['choices']);
        $this->assertChoices($choices);

        $question = $this->questions->create([
            'subject_id' => $data['subject_id'],
            'question' => $data['question'],
            'created_by' => $data['created_by'],
        ]);

        $question->choices()->createMany($choices->all());

        return $question;
    }

    public function update(Question $question, array $data): Question
    {
        $choices = $this->normalizeChoices($data['choices']);
        $this->assertChoices($choices);

        $question = $this->questions->update($question, [
            'subject_id' => $data['subject_id'],
            'question' => $data['question'],
            'created_by' => $data['created_by'],
        ]);

        $question->choices()->delete();
        $question->choices()->createMany($choices->all());

        return $question;
    }

    public function delete(Question $question): void
    {
        $this->questions->delete($question);
    }

    public function getEditPayload(Question $question): array
    {
        $question->load(['choices' => fn ($query) => $query->orderBy('choice_label')]);

        return [
            'id' => $question->id,
            'subject_id' => $question->subject_id,
            'question' => $question->question,
            'created_by' => $question->created_by,
            'choices' => $question->choices->map(fn ($choice) => [
                'choice_label' => $choice->choice_label,
                'choice_text' => $choice->choice_text,
                'is_correct' => (bool) $choice->is_correct,
                'explanation' => $choice->explanation,
            ])->values(),
        ];
    }

    private function normalizeChoices(array $choices): Collection
    {
        return collect($choices)->map(fn (array $choice) => [
            'choice_label' => strtoupper(trim($choice['choice_label'])),
            'choice_text' => trim($choice['choice_text']),
            'is_correct' => (bool) $choice['is_correct'],
            'explanation' => $choice['explanation'] ? trim($choice['explanation']) : null,
        ]);
    }

    private function assertChoices(Collection $choices): void
    {
        $labelCount = $choices->pluck('choice_label')->count();
        $uniqueLabels = $choices->pluck('choice_label')->unique()->count();
        $correctCount = $choices->where('is_correct', true)->count();

        if ($uniqueLabels !== $labelCount) {
            throw ValidationException::withMessages([
                'choices' => 'Choice labels must be unique for each question.',
            ]);
        }

        if ($correctCount !== 1) {
            throw ValidationException::withMessages([
                'choices' => 'You must mark exactly one correct answer.',
            ]);
        }
    }
}
