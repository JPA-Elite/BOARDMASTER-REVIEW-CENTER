<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlanCatalogSeeder extends Seeder
{
    /**
     * Seed grouped plans, subjects, access rules, and baseline questions.
     */
    public function run(): void
    {
        $admin = User::query()->where('role', 'admin')->orderBy('id')->first();

        $planAConcepts = [
            'Anatomy and Physiology',
            'Theoretical Foundation in Nursing Health Ethics (Bioethics)',
            'Health Assessment',
            'Fundamentals of Nursing Practice Nursing Informatics Nutrition and Diet Therapy',
            'Microbiology and Parasitology',
            'Pharmacology and Nursing Therapeutics',
            'Care of Maternal and Child (Well Clients)',
            'Care of Maternal and Child (At-risk or with Problems: Acute and Chronic)',
            'Medical Surgical Nursing 101: Infectious and Immunologic Diseases, Alteration in Fluids and Electrolytes (Acid Base Balance), Respiratory Disorders, Renal and Hematology Disorders, Reproductive System Disorders, Cellular Aberration',
            'Medical Surgical Nursing 102: Neurologic Disorders, Endocrine Disorders, Alteration in Perception and Coordination, Gastrointestinal Disorders',
            'Medical Surgical Nursing 103: Emergency and Disaster Nursing, Complications in Metabolism, Gastrointestinal and Liver, Multi-System Problems, Critical and Intensive Care',
            'Community Health Nursing',
            'Nursing Research',
            'Geriatric Nursing',
            'Mental Health Disorders Psychiatric Nursing',
            'Nursing Leadership and Management',
        ];

        $mockBoardSubjects = [
            'Nursing Practice 1: Community Health Nursing',
            'Nursing Practice 2: Care of Healthy/At Risk Mother and Child',
            'Nursing Practice 3: Care of Clients with Physiologic and Psychosocial Alterations (Part A)',
            'Nursing Practice 4: Care of Clients with Physiologic and Psychosocial Alterations (Part B)',
            'Nursing Practice 5: Care of Clients with Physiologic and Psychosocial Alterations (Part C)',
        ];

        $subjects = collect(array_merge($planAConcepts, $mockBoardSubjects))
            ->values()
            ->map(function (string $name) {
                return Subject::query()->firstOrCreate(['name' => $name]);
            });

        $subjectByName = $subjects->keyBy('name');

        $planDefinitions = [
            [
                'name' => 'Free Trial',
                'plan_group' => 'free_trial',
                'group_label' => 'A. Free Trial (Limited Access)',
                'sub_plan_label' => '3 days FREE',
                'price' => 0,
                'billing_cycle' => Plan::BILLING_TRIAL,
                'duration_days' => 3,
                'description' => '3 days free trial with limited nursing concepts access.',
                'is_default' => true,
                'sort_order' => 100,
                'features' => [
                    '3 days FREE',
                    'Limited access to nursing concepts',
                    'Upgrade anytime to unlock full access',
                ],
                'subject_names' => array_slice($planAConcepts, 0, 3),
                'question_limit' => 30,
            ],
            [
                'name' => 'Plan A - Quarterly (3 months)',
                'plan_group' => 'plan_a',
                'group_label' => 'PLAN A: ALL NURSING CONCEPTS',
                'sub_plan_label' => 'Quarterly (3 months)',
                'price' => 299,
                'billing_cycle' => Plan::BILLING_QUARTERLY,
                'duration_days' => 90,
                'description' => 'All Nursing Concepts access.',
                'is_default' => false,
                'sort_order' => 200,
                'features' => [
                    'All Nursing Concepts',
                ],
                'subject_names' => $planAConcepts,
                'question_limit' => 100,
            ],
            [
                'name' => 'Plan A - Semi-Annual (6 months)',
                'plan_group' => 'plan_a',
                'group_label' => 'PLAN A: ALL NURSING CONCEPTS',
                'sub_plan_label' => 'Semi-Annual (6 months)',
                'price' => 499,
                'billing_cycle' => Plan::BILLING_SEMI_ANNUAL,
                'duration_days' => 180,
                'description' => 'All Nursing Concepts access.',
                'is_default' => false,
                'sort_order' => 210,
                'features' => [
                    'All Nursing Concepts',
                ],
                'subject_names' => $planAConcepts,
                'question_limit' => 100,
            ],
            [
                'name' => 'Plan A - Annual (12 months)',
                'plan_group' => 'plan_a',
                'group_label' => 'PLAN A: ALL NURSING CONCEPTS',
                'sub_plan_label' => 'Annual (12 months)',
                'price' => 799,
                'billing_cycle' => Plan::BILLING_ANNUAL,
                'duration_days' => 365,
                'description' => 'All Nursing Concepts access.',
                'is_default' => false,
                'sort_order' => 220,
                'features' => [
                    'All Nursing Concepts',
                ],
                'subject_names' => $planAConcepts,
                'question_limit' => 100,
            ],
            [
                'name' => 'Plan B - Quarterly (3 months)',
                'plan_group' => 'plan_b',
                'group_label' => 'PLAN B: MOCK BOARD EXAM + ALL NURSING CONCEPTS',
                'sub_plan_label' => 'Quarterly (3 months)',
                'price' => 799,
                'billing_cycle' => Plan::BILLING_QUARTERLY,
                'duration_days' => 90,
                'description' => 'Full access: Mock Board Exam + All Nursing Concepts.',
                'is_default' => false,
                'sort_order' => 300,
                'features' => [
                    'Mock Board Exam',
                    'All Nursing Concepts',
                ],
                'subject_names' => array_merge($planAConcepts, $mockBoardSubjects),
                'question_limit' => 100,
            ],
            [
                'name' => 'Plan B - Semi-Annual (6 months)',
                'plan_group' => 'plan_b',
                'group_label' => 'PLAN B: MOCK BOARD EXAM + ALL NURSING CONCEPTS',
                'sub_plan_label' => 'Semi-Annual (6 months)',
                'price' => 1299,
                'billing_cycle' => Plan::BILLING_SEMI_ANNUAL,
                'duration_days' => 180,
                'description' => 'Full access: Mock Board Exam + All Nursing Concepts.',
                'is_default' => false,
                'sort_order' => 310,
                'features' => [
                    'Mock Board Exam',
                    'All Nursing Concepts',
                ],
                'subject_names' => array_merge($planAConcepts, $mockBoardSubjects),
                'question_limit' => 100,
            ],
            [
                'name' => 'Plan B - Annual (12 months)',
                'plan_group' => 'plan_b',
                'group_label' => 'PLAN B: MOCK BOARD EXAM + ALL NURSING CONCEPTS',
                'sub_plan_label' => 'Annual (12 months)',
                'price' => 1999,
                'billing_cycle' => Plan::BILLING_ANNUAL,
                'duration_days' => 365,
                'description' => 'Full access: Mock Board Exam + All Nursing Concepts.',
                'is_default' => false,
                'sort_order' => 320,
                'features' => [
                    'Mock Board Exam',
                    'All Nursing Concepts',
                ],
                'subject_names' => array_merge($planAConcepts, $mockBoardSubjects),
                'question_limit' => 100,
            ],
        ];

        Plan::query()->update(['is_default' => false]);

        foreach ($planDefinitions as $definition) {
            /** @var Plan $plan */
            $plan = Plan::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'plan_group' => $definition['plan_group'],
                    'group_label' => $definition['group_label'],
                    'sub_plan_label' => $definition['sub_plan_label'],
                    'price' => $definition['price'],
                    'billing_cycle' => $definition['billing_cycle'],
                    'duration_days' => $definition['duration_days'],
                    'sort_order' => $definition['sort_order'],
                    'description' => $definition['description'],
                    'is_default' => $definition['is_default'],
                ]
            );

            $plan->features()->delete();
            $plan->features()->createMany(
                collect($definition['features'])
                    ->map(fn (string $feature) => ['feature_name' => $feature])
                    ->all()
            );

            $plan->subjectAccesses()->delete();
            foreach ($definition['subject_names'] as $subjectName) {
                /** @var Subject|null $subject */
                $subject = $subjectByName->get($subjectName);
                if (! $subject) {
                    continue;
                }

                $plan->subjectAccesses()->create([
                    'subject_id' => $subject->id,
                    'question_limit' => (int) $definition['question_limit'],
                ]);
            }
        }

        $this->seedQuestions(
            subjectNames: array_merge($planAConcepts, $mockBoardSubjects),
            subjectByName: $subjectByName->all(),
            createdBy: $admin?->id,
        );
    }

    /**
     * @param array<int, string> $subjectNames
     * @param array<string, Subject> $subjectByName
     */
    private function seedQuestions(array $subjectNames, array $subjectByName, ?int $createdBy): void
    {
        $subjectIds = collect($subjectNames)
            ->map(fn (string $name) => $subjectByName[$name]->id ?? null)
            ->filter()
            ->values();

        if ($subjectIds->isEmpty()) {
            return;
        }

        Question::query()->whereIn('subject_id', $subjectIds)->delete();

        foreach ($subjectNames as $subjectName) {
            /** @var Subject|null $subject */
            $subject = $subjectByName[$subjectName] ?? null;
            if (! $subject) {
                continue;
            }

            for ($index = 1; $index <= 120; $index++) {
                $labels = ['A', 'B', 'C', 'D'];
                $correctLabel = $labels[$index % 4];

                $question = Question::query()->create([
                    'subject_id' => $subject->id,
                    'question' => sprintf(
                        '[%s] Sample Board Question %d: What is the safest priority nursing action in this scenario?',
                        $subjectName,
                        $index
                    ),
                    'created_by' => $createdBy,
                ]);

                $choices = [
                    'A' => 'Delay patient assessment until all paperwork is complete.',
                    'B' => 'Assess the patient first and prioritize safety needs immediately.',
                    'C' => 'Ignore current signs and wait for the next duty shift.',
                    'D' => 'Apply interventions without assessment or verification.',
                ];

                foreach ($choices as $label => $choiceText) {
                    $isCorrect = $label === $correctLabel;
                    $question->choices()->create([
                        'choice_label' => $label,
                        'choice_text' => $choiceText,
                        'is_correct' => $isCorrect,
                        'explanation' => $isCorrect
                            ? sprintf('Correct. In %s, patient safety and assessment are prioritized first.', $subjectName)
                            : 'Not the best answer. This option does not prioritize immediate safety and assessment.',
                    ]);
                }
            }
        }
    }
}

