import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const defaultChoices = [
    { choice_label: 'A', choice_text: '', is_correct: true, explanation: '' },
    { choice_label: 'B', choice_text: '', is_correct: false, explanation: '' },
    { choice_label: 'C', choice_text: '', is_correct: false, explanation: '' },
    { choice_label: 'D', choice_text: '', is_correct: false, explanation: '' },
];

export default function Form({ mode, question, subjects, admins }) {
    const initialChoices =
        question?.choices?.length > 0
            ? question.choices
            : defaultChoices;

    const { data, setData, post, put, processing, errors } = useForm({
        subject_id: question?.subject_id || '',
        question: question?.question || '',
        created_by: question?.created_by || admins[0]?.id || '',
        choices: initialChoices.map((choice) => ({ ...choice })),
    });

    const updateChoice = (index, field, value) => {
        const nextChoices = [...data.choices];

        if (field === 'is_correct') {
            nextChoices.forEach((choice, choiceIndex) => {
                nextChoices[choiceIndex] = {
                    ...choice,
                    is_correct: choiceIndex === index,
                };
            });
        } else {
            nextChoices[index] = {
                ...nextChoices[index],
                [field]: value,
            };
        }

        setData('choices', nextChoices);
    };

    const addChoice = () => {
        if (data.choices.length >= 6) {
            return;
        }

        const nextLabel = String.fromCharCode(65 + data.choices.length);

        setData('choices', [
            ...data.choices,
            {
                choice_label: nextLabel,
                choice_text: '',
                is_correct: false,
                explanation: '',
            },
        ]);
    };

    const removeChoice = (index) => {
        if (data.choices.length <= 2) {
            return;
        }

        const filteredChoices = data.choices.filter(
            (_, choiceIndex) => choiceIndex !== index,
        );

        if (!filteredChoices.some((choice) => choice.is_correct)) {
            filteredChoices[0].is_correct = true;
        }

        setData(
            'choices',
            filteredChoices.map((choice, choiceIndex) => ({
                ...choice,
                choice_label: String.fromCharCode(65 + choiceIndex),
            })),
        );
    };

    const submit = (event) => {
        event.preventDefault();

        if (mode === 'edit') {
            put(route('admin.questions.update', question.id));
            return;
        }

        post(route('admin.questions.store'));
    };

    return (
        <AdminLayout
            title={mode === 'edit' ? 'Edit Question' : 'Create Question'}
        >
            <Head title={mode === 'edit' ? 'Edit Question' : 'Create Question'} />

            <form onSubmit={submit} className="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                <div>
                    <InputLabel htmlFor="subject_id" value="Subject" />
                    <select
                        id="subject_id"
                        value={data.subject_id}
                        onChange={(event) =>
                            setData('subject_id', event.target.value)
                        }
                        className="mt-1 block w-full rounded-md border-slate-300"
                    >
                        <option value="">Select subject</option>
                        {subjects.map((subject) => (
                            <option key={subject.id} value={subject.id}>
                                {subject.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.subject_id} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="created_by" value="Created By (Admin)" />
                    <select
                        id="created_by"
                        value={data.created_by}
                        onChange={(event) =>
                            setData('created_by', event.target.value)
                        }
                        className="mt-1 block w-full rounded-md border-slate-300"
                    >
                        <option value="">Select admin</option>
                        {admins.map((admin) => (
                            <option key={admin.id} value={admin.id}>
                                {admin.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.created_by} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="question" value="Question Text" />
                    <textarea
                        id="question"
                        rows={4}
                        value={data.question}
                        onChange={(event) => setData('question', event.target.value)}
                        className="mt-1 block w-full rounded-md border-slate-300"
                    />
                    <InputError message={errors.question} className="mt-2" />
                </div>

                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <InputLabel value="Choices" />
                        <button
                            type="button"
                            onClick={addChoice}
                            className="text-sm text-sky-700 hover:text-sky-900"
                        >
                            Add Choice
                        </button>
                    </div>

                    {data.choices.map((choice, index) => (
                        <div
                            key={index}
                            className="space-y-2 rounded-lg border border-slate-200 p-3"
                        >
                            <div className="grid gap-3 md:grid-cols-4">
                                <div>
                                    <InputLabel value="Label" />
                                    <TextInput
                                        value={choice.choice_label}
                                        onChange={(event) =>
                                            updateChoice(
                                                index,
                                                'choice_label',
                                                event.target.value.toUpperCase(),
                                            )
                                        }
                                        className="mt-1 block w-full"
                                    />
                                </div>
                                <div className="md:col-span-2">
                                    <InputLabel value="Choice Text" />
                                    <TextInput
                                        value={choice.choice_text}
                                        onChange={(event) =>
                                            updateChoice(
                                                index,
                                                'choice_text',
                                                event.target.value,
                                            )
                                        }
                                        className="mt-1 block w-full"
                                    />
                                </div>
                                <div className="flex items-end gap-2">
                                    <label className="inline-flex items-center gap-2">
                                        <input
                                            type="radio"
                                            checked={choice.is_correct}
                                            onChange={() =>
                                                updateChoice(index, 'is_correct', true)
                                            }
                                        />
                                        Correct
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() => removeChoice(index)}
                                        className="rounded-md border border-red-300 px-3 py-1 text-sm text-red-700"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Explanation (optional)" />
                                <textarea
                                    rows={2}
                                    value={choice.explanation || ''}
                                    onChange={(event) =>
                                        updateChoice(
                                            index,
                                            'explanation',
                                            event.target.value,
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border-slate-300"
                                />
                            </div>
                        </div>
                    ))}

                    <InputError message={errors.choices} className="mt-2" />
                </div>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {mode === 'edit' ? 'Update Question' : 'Create Question'}
                    </button>
                    <Link
                        href={route('admin.questions.index')}
                        className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
