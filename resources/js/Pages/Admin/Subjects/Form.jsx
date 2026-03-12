import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ mode, subject }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: subject?.name || '',
    });

    const submit = (event) => {
        event.preventDefault();

        if (mode === 'edit') {
            put(route('admin.subjects.update', subject.id));
            return;
        }

        post(route('admin.subjects.store'));
    };

    return (
        <AdminLayout title={mode === 'edit' ? 'Edit Subject' : 'Create Subject'}>
            <Head title={mode === 'edit' ? 'Edit Subject' : 'Create Subject'} />

            <form onSubmit={submit} className="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                <div>
                    <InputLabel htmlFor="name" value="Subject Name" />
                    <TextInput
                        id="name"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {mode === 'edit' ? 'Update Subject' : 'Create Subject'}
                    </button>
                    <Link
                        href={route('admin.subjects.index')}
                        className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
