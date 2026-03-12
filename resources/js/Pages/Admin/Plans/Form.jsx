import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ mode, plan }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: plan?.name || '',
        price: plan?.price || '',
        description: plan?.description || '',
        features:
            plan?.features?.length > 0
                ? plan.features
                : ['', ''],
    });

    const updateFeature = (index, value) => {
        const nextFeatures = [...data.features];
        nextFeatures[index] = value;
        setData('features', nextFeatures);
    };

    const addFeature = () => {
        setData('features', [...data.features, '']);
    };

    const removeFeature = (index) => {
        if (data.features.length <= 1) {
            return;
        }

        setData(
            'features',
            data.features.filter((_, featureIndex) => featureIndex !== index),
        );
    };

    const submit = (event) => {
        event.preventDefault();

        if (mode === 'edit') {
            put(route('admin.plans.update', plan.id));
            return;
        }

        post(route('admin.plans.store'));
    };

    return (
        <AdminLayout title={mode === 'edit' ? 'Edit Plan' : 'Create Plan'}>
            <Head title={mode === 'edit' ? 'Edit Plan' : 'Create Plan'} />

            <form onSubmit={submit} className="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                <div>
                    <InputLabel htmlFor="name" value="Plan Name" />
                    <TextInput
                        id="name"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="price" value="Price" />
                    <TextInput
                        id="price"
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.price}
                        onChange={(event) => setData('price', event.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.price} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="description" value="Description" />
                    <textarea
                        id="description"
                        rows={4}
                        value={data.description}
                        onChange={(event) =>
                            setData('description', event.target.value)
                        }
                        className="mt-1 block w-full rounded-md border-slate-300"
                    />
                    <InputError message={errors.description} className="mt-2" />
                </div>

                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <InputLabel value="Plan Features" />
                        <button
                            type="button"
                            onClick={addFeature}
                            className="text-sm text-sky-700 hover:text-sky-900"
                        >
                            Add Feature
                        </button>
                    </div>
                    {data.features.map((feature, index) => (
                        <div key={index} className="flex gap-2">
                            <TextInput
                                value={feature}
                                onChange={(event) =>
                                    updateFeature(index, event.target.value)
                                }
                                className="block w-full"
                                placeholder={`Feature ${index + 1}`}
                            />
                            <button
                                type="button"
                                onClick={() => removeFeature(index)}
                                className="rounded-md border border-red-300 px-3 text-red-700"
                            >
                                Remove
                            </button>
                        </div>
                    ))}
                    <InputError message={errors.features} className="mt-2" />
                </div>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {mode === 'edit' ? 'Update Plan' : 'Create Plan'}
                    </button>
                    <Link
                        href={route('admin.plans.index')}
                        className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
