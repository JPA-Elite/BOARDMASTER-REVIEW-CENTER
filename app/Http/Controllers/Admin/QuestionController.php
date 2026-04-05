<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Services\Admin\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuestionController extends Controller
{
    public function __construct(private readonly QuestionService $questions)
    {
    }

    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->get('search', '')),
            'subject_id' => $request->integer('subject_id') ?: null,
        ];
        $questions = $this->questions->paginate($filters);

        return Inertia::render('Admin/Questions/Index', [
            'questions' => $questions,
            'subjects' => $this->questions->listSubjects(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Questions/Form', [
            'mode' => 'create',
            'question' => null,
            'subjects' => $this->questions->listSubjects(),
            'admins' => $this->questions->listAdmins(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateQuestion($request);
        $this->questions->create($validated);

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question created successfully.');
    }

    public function edit(Question $question): Response
    {
        return Inertia::render('Admin/Questions/Form', [
            'mode' => 'edit',
            'subjects' => $this->questions->listSubjects(),
            'admins' => $this->questions->listAdmins(),
            'question' => $this->questions->getEditPayload($question),
        ]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $validated = $this->validateQuestion($request);
        $this->questions->update($question, $validated);

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->questions->delete($question);

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question deleted successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $count = $this->questions->importFromFile(
            (int) $validated['subject_id'],
            $request->file('import_file'),
            (int) $request->user()->id,
        );

        return redirect()
            ->route('admin.questions.index')
            ->with('success', "{$count} question(s) imported successfully.");
    }

    public function template(Request $request): BinaryFileResponse
    {
        $format = strtolower((string) $request->query('format', 'csv'));

        if (!in_array($format, ['csv', 'xlsx'], true)) {
            abort(404);
        }

        $path = resource_path("templates/questions-import-template.{$format}");

        if (!is_file($path)) {
            abort(404, 'Template file not found.');
        }

        return response()->download($path, "questions-import-template.{$format}");
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
}
