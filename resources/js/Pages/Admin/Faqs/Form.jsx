import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ mode, faq }) {
    const { data, setData, post, put, processing, errors } = useForm({
        question: faq?.question || '',
        answer: faq?.answer || '',
        is_active: faq?.is_active ?? true,
        sort_order: faq?.sort_order ?? 0,
    });

    const submit = (event) => {
        event.preventDefault();

        if (mode === 'edit') {
            put(route('admin.faqs.update', faq.id));
            return;
        }

        post(route('admin.faqs.store'));
    };

    return (
        <AdminLayout title={mode === 'edit' ? 'Edit FAQ' : 'Create FAQ'}>
            <Head title={mode === 'edit' ? 'Edit FAQ' : 'Create FAQ'} />

            <form onSubmit={submit} className="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
                <div>
                    <InputLabel htmlFor="question" value="Question" />
                    <TextInput
                        id="question"
                        value={data.question}
                        onChange={(event) => setData('question', event.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.question} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="answer" value="Answer" />
                    <textarea
                        id="answer"
                        value={data.answer}
                        onChange={(event) => setData('answer', event.target.value)}
                        rows={5}
                        className="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                    />
                    <InputError message={errors.answer} className="mt-2" />
                </div>

                <div className="grid gap-4 sm:grid-cols-[200px_1fr] sm:items-center">
                    <div>
                        <InputLabel htmlFor="sort_order" value="Sort Order" />
                        <TextInput
                            id="sort_order"
                            type="number"
                            value={data.sort_order}
                            onChange={(event) => setData('sort_order', event.target.value)}
                            className="mt-1 block w-full"
                            min="0"
                        />
                        <InputError message={errors.sort_order} className="mt-2" />
                    </div>
                    <div className="flex items-center gap-2 pt-6">
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(event) => setData('is_active', event.target.checked)}
                            className="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-900"
                        />
                        <label htmlFor="is_active" className="text-sm text-slate-700">
                            Show on contact page
                        </label>
                    </div>
                </div>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {mode === 'edit' ? 'Update FAQ' : 'Create FAQ'}
                    </button>
                    <Link
                        href={route('admin.faqs.index')}
                        className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
