import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ mode, offer, subjects = [] }) {
    const initialData = {
        title: offer?.title || '',
        description: offer?.description || '',
        points_cost: offer?.points_cost ?? 0,
        subject_id: offer?.subject_id ?? '',
        question_limit: offer?.question_limit ?? '',
        access_duration_days: offer?.access_duration_days ?? '',
        sort_order: offer?.sort_order ?? 0,
        starts_at: offer?.starts_at || '',
        ends_at: offer?.ends_at || '',
        is_active: offer?.is_active ?? true,
        is_featured: offer?.is_featured ?? false,
    };

    if (mode === 'edit') {
        initialData.category = offer?.category || '';
        initialData.brand = offer?.brand || '';
        initialData.image_url = offer?.image_url || '';
    }

    const { data, setData, post, put, processing, errors } = useForm(initialData);

    const submit = (event) => {
        event.preventDefault();

        if (mode === 'edit') {
            put(route('admin.referral-offers.update', offer.id));
            return;
        }

        post(route('admin.referral-offers.store'));
    };

    return (
        <AdminLayout title={mode === 'edit' ? 'Edit Offer' : 'Create Offer'}>
            <Head title={mode === 'edit' ? 'Edit Offer' : 'Create Offer'} />

            <form onSubmit={submit} className="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
                <div>
                    <InputLabel htmlFor="title" value="Offer Title" />
                    <TextInput
                        id="title"
                        value={data.title}
                        onChange={(event) => setData('title', event.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.title} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="description" value="Description" />
                    <textarea
                        id="description"
                        rows={3}
                        value={data.description}
                        onChange={(event) =>
                            setData('description', event.target.value)
                        }
                        className="mt-1 block w-full rounded-md border-slate-300"
                    />
                    <InputError message={errors.description} className="mt-2" />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="points_cost" value="Points Cost" />
                        <TextInput
                            id="points_cost"
                            type="number"
                            min="0"
                            value={data.points_cost}
                            onChange={(event) =>
                                setData('points_cost', event.target.value)
                            }
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.points_cost} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel htmlFor="sort_order" value="Sort Order" />
                        <TextInput
                            id="sort_order"
                            type="number"
                            min="0"
                            value={data.sort_order}
                            onChange={(event) =>
                                setData('sort_order', event.target.value)
                            }
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.sort_order} className="mt-2" />
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="subject_id" value="Subject Unlock" />
                        <select
                            id="subject_id"
                            value={data.subject_id}
                            onChange={(event) =>
                                setData('subject_id', event.target.value)
                            }
                            className="mt-1 block w-full rounded-md border-slate-300"
                        >
                            <option value="">No subject</option>
                            {subjects.map((subject) => (
                                <option key={subject.id} value={subject.id}>
                                    {subject.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.subject_id} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel htmlFor="question_limit" value="Question Limit" />
                        <TextInput
                            id="question_limit"
                            type="number"
                            min="1"
                            value={data.question_limit}
                            onChange={(event) =>
                                setData('question_limit', event.target.value)
                            }
                            className="mt-1 block w-full"
                            placeholder="Leave empty for no limit"
                        />
                        <InputError message={errors.question_limit} className="mt-2" />
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="access_duration_days" value="Access Duration (days)" />
                        <TextInput
                            id="access_duration_days"
                            type="number"
                            min="1"
                            value={data.access_duration_days}
                            onChange={(event) =>
                                setData('access_duration_days', event.target.value)
                            }
                            className="mt-1 block w-full"
                            placeholder="Leave empty for no expiry"
                        />
                        <InputError message={errors.access_duration_days} className="mt-2" />
                    </div>
                    <div>
                        {mode === 'edit' && (
                            <>
                                <InputLabel htmlFor="image_url" value="Image URL" />
                                <TextInput
                                    id="image_url"
                                    value={data.image_url}
                                    onChange={(event) =>
                                        setData('image_url', event.target.value)
                                    }
                                    className="mt-1 block w-full"
                                    placeholder="https://..."
                                />
                                <InputError message={errors.image_url} className="mt-2" />
                            </>
                        )}
                    </div>
                </div>

                {mode === 'edit' && (
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="category" value="Category" />
                            <TextInput
                                id="category"
                                value={data.category}
                                onChange={(event) =>
                                    setData('category', event.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError message={errors.category} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="brand" value="Brand" />
                            <TextInput
                                id="brand"
                                value={data.brand}
                                onChange={(event) =>
                                    setData('brand', event.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError message={errors.brand} className="mt-2" />
                        </div>
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="starts_at" value="Starts At" />
                        <TextInput
                            id="starts_at"
                            type="datetime-local"
                            value={data.starts_at}
                            onChange={(event) =>
                                setData('starts_at', event.target.value)
                            }
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.starts_at} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel htmlFor="ends_at" value="Ends At" />
                        <TextInput
                            id="ends_at"
                            type="datetime-local"
                            value={data.ends_at}
                            onChange={(event) =>
                                setData('ends_at', event.target.value)
                            }
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.ends_at} className="mt-2" />
                    </div>
                </div>

                <div className="flex flex-wrap gap-4">
                    <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            checked={Boolean(data.is_active)}
                            onChange={(event) =>
                                setData('is_active', event.target.checked)
                            }
                            className="rounded border-slate-300 text-sky-700 focus:ring-sky-500"
                        />
                        Active
                    </label>
                    <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            checked={Boolean(data.is_featured)}
                            onChange={(event) =>
                                setData('is_featured', event.target.checked)
                            }
                            className="rounded border-slate-300 text-sky-700 focus:ring-sky-500"
                        />
                        Featured
                    </label>
                </div>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {mode === 'edit' ? 'Update Offer' : 'Create Offer'}
                    </button>
                    <Link
                        href={route('admin.referral-offers.index')}
                        className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
