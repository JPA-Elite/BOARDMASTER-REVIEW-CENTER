<?php

namespace App\Services\Admin;

use App\Models\Question;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\SubjectRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $subjectId = $filters['subject_id'] ?? null;
        $perPage = $perPage > 0 ? $perPage : 15;

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

        $questions = $query->paginate($perPage)->appends([
            'search' => $search,
            'subject_id' => $subjectId,
            'per_page' => $perPage,
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

    public function importFromFile(int $subjectId, UploadedFile $file, int $createdBy): int
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        $rows = $extension === 'xlsx'
            ? $this->readXlsxRows($file)
            : $this->readCsvRows($file);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'import_file' => 'The import file is empty.',
            ]);
        }

        $header = $this->normalizeHeader(array_shift($rows));
        $headerMap = $this->buildHeaderMap($header);

        $required = ['question', 'choice_a', 'choice_b', 'correct_label'];
        $missing = array_values(array_diff($required, array_keys($headerMap)));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'import_file' => 'Invalid header. Required columns: question, choice_a, choice_b, correct_label.',
            ]);
        }

        $payloads = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;
            $values = $this->mapRowValues($row, $headerMap);

            if ($this->rowIsEmpty($values)) {
                continue;
            }

            $questionText = trim((string) ($values['question'] ?? ''));
            $correctLabel = strtoupper(trim((string) ($values['correct_label'] ?? '')));
            $explanation = trim((string) ($values['explanation'] ?? ''));

            if ($questionText === '') {
                $errors[] = "Row {$lineNumber}: Question text is required.";
                continue;
            }

            $choices = $this->extractChoices($values);

            if (count($choices) < 2) {
                $errors[] = "Row {$lineNumber}: Provide at least two choices.";
                continue;
            }

            if ($correctLabel === '') {
                $errors[] = "Row {$lineNumber}: Correct label is required.";
                continue;
            }

            if (!array_key_exists($correctLabel, $choices)) {
                $errors[] = "Row {$lineNumber}: Correct label must match one of the provided choices.";
                continue;
            }

            $payloads[] = [
                'subject_id' => $subjectId,
                'question' => $questionText,
                'created_by' => $createdBy,
                'choices' => $this->buildChoicesPayload($choices, $correctLabel, $explanation),
            ];
        }

        if ($errors !== []) {
            $message = implode(' ', array_slice($errors, 0, 4));

            if (count($errors) > 4) {
                $message .= ' Additional rows contain errors.';
            }

            throw ValidationException::withMessages([
                'import_file' => $message,
            ]);
        }

        if ($payloads === []) {
            throw ValidationException::withMessages([
                'import_file' => 'No valid rows were found in the import file.',
            ]);
        }

        DB::transaction(function () use ($payloads) {
            foreach ($payloads as $payload) {
                $question = $this->questions->create([
                    'subject_id' => $payload['subject_id'],
                    'question' => $payload['question'],
                    'created_by' => $payload['created_by'],
                ]);

                $question->choices()->createMany($payload['choices']);
            }
        });

        return count($payloads);
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

    private function readCsvRows(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if (!is_resource($handle)) {
            return [];
        }

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $rows[] = $data;
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(UploadedFile $file): array
    {
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, false, false, false);
    }

    private function normalizeHeader(array $header): array
    {
        return array_map(function ($value) {
            $value = is_string($value) ? trim($value) : '';
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

            return strtolower($value ?? '');
        }, $header);
    }

    private function buildHeaderMap(array $header): array
    {
        $map = [];

        foreach ($header as $index => $column) {
            if ($column === '' || array_key_exists($column, $map)) {
                continue;
            }

            $map[$column] = $index;
        }

        return $map;
    }

    private function mapRowValues(array $row, array $headerMap): array
    {
        $values = [];

        foreach ($headerMap as $column => $index) {
            $values[$column] = $row[$index] ?? null;
        }

        return $values;
    }

    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function extractChoices(array $values): array
    {
        $choiceMap = [
            'A' => 'choice_a',
            'B' => 'choice_b',
            'C' => 'choice_c',
            'D' => 'choice_d',
            'E' => 'choice_e',
            'F' => 'choice_f',
        ];

        $choices = [];

        foreach ($choiceMap as $label => $column) {
            if (!array_key_exists($column, $values)) {
                continue;
            }

            $text = trim((string) $values[$column]);

            if ($text === '') {
                continue;
            }

            $choices[$label] = $text;
        }

        return $choices;
    }

    private function buildChoicesPayload(array $choices, string $correctLabel, string $explanation): array
    {
        $payload = [];

        foreach ($choices as $label => $text) {
            $payload[] = [
                'choice_label' => $label,
                'choice_text' => $text,
                'is_correct' => $label === $correctLabel,
                'explanation' => $label === $correctLabel && $explanation !== ''
                    ? $explanation
                    : null,
            ];
        }

        return $payload;
    }
}
