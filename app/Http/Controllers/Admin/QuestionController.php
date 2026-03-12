<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function index(): Response
    {
        $questions = Question::query()
            ->with(['subject:id,name', 'creator:id,name'])
            ->withCount('choices')
            ->latest()
            ->paginate(15)
            ->through(fn (Question $question) => [
                'id' => $question->id,
                'subject' => $question->subject?->name,
                'question' => $question->question,
                'created_by' => $question->creator?->name,
                'choices_count' => $question->choices_count,
                'created_at' => $question->created_at?->format('M d, Y'),
            ]);

        return Inertia::render('Admin/Questions/Index', [
            'questions' => $questions,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Questions/Form', [
            'mode' => 'create',
            'question' => null,
            'subjects' => $this->subjectsList(),
            'admins' => $this->adminsList(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateQuestion($request);
        $choices = $this->normalizeChoices($validated['choices']);
        $this->assertChoices($choices);

        $question = Question::create([
            'subject_id' => $validated['subject_id'],
            'question' => $validated['question'],
            'created_by' => $validated['created_by'],
        ]);

        $question->choices()->createMany($choices->all());

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question created successfully.');
    }

    public function edit(Question $question): Response
    {
        $question->load(['choices' => fn ($query) => $query->orderBy('choice_label')]);

        return Inertia::render('Admin/Questions/Form', [
            'mode' => 'edit',
            'subjects' => $this->subjectsList(),
            'admins' => $this->adminsList(),
            'question' => [
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
            ],
        ]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $validated = $this->validateQuestion($request);
        $choices = $this->normalizeChoices($validated['choices']);
        $this->assertChoices($choices);

        $question->update([
            'subject_id' => $validated['subject_id'],
            'question' => $validated['question'],
            'created_by' => $validated['created_by'],
        ]);

        $question->choices()->delete();
        $question->choices()->createMany($choices->all());

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question deleted successfully.');
    }

    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'question' => ['required', 'string'],
            'created_by' => ['required', 'integer', 'exists:users,id'],
            'choices' => ['required', 'array', 'min:2', 'max:6'],
            'choices.*.choice_label' => ['required', 'string', 'max:5'],
            'choices.*.choice_text' => ['required', 'string'],
            'choices.*.is_correct' => ['required', 'boolean'],
            'choices.*.explanation' => ['nullable', 'string'],
        ]);
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

    private function subjectsList()
    {
        return Subject::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function adminsList()
    {
        return User::query()
            ->select('id', 'name')
            ->where('role', 'admin')
            ->orderBy('name')
            ->get();
    }
}
