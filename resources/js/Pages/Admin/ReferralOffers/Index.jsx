import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronDown, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ offers, settings, filters = {} }) {
    const [confirmingId, setConfirmingId] = useState(null);
    const [search, setSearch] = useState(filters.search || '');
    const [perPage, setPerPage] = useState(
        filters.per_page ? String(filters.per_page) : '12',
    );
    const { data, setData, patch, processing, errors } = useForm({
        points_per_referral: settings?.points_per_referral ?? 10,
    });

    const openConfirm = (id) => setConfirmingId(id);
    const closeConfirm = () => setConfirmingId(null);

    const confirmDelete = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.referral-offers.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    const submitSettings = (event) => {
        event.preventDefault();
        patch(route('admin.referral-offers.settings'));
    };

    const submitSearch = (event) => {
        event.preventDefault();
        router.get(
            route('admin.referral-offers.index'),
            { search, per_page: perPage },
            { preserveState: true, replace: true },
        );
    };

    const applyPerPage = (value) => {
        setPerPage(value);
        router.get(
            route('admin.referral-offers.index'),
            { search, per_page: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title="Referral Offers">
            <Head title="Referral Offers" />

            <form
                onSubmit={submitSettings}
                className="rounded-2xl border border-slate-200 bg-white p-5"
            >
                <div className="flex flex-wrap items-end gap-4">
                    <div className="min-w-[220px]">
                        <InputLabel
                            htmlFor="points_per_referral"
                            value="Points per Referral"
                        />
                        <TextInput
                            id="points_per_referral"
                            type="number"
                            min="1"
                            value={data.points_per_referral}
                            onChange={(event) =>
                                setData('points_per_referral', event.target.value)
                            }
                            className="mt-1 block w-full"
                        />
                        <InputError
                            message={errors.points_per_referral}
                            className="mt-2"
                        />
                    </div>
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        Save Settings
                    </button>
                </div>
            </form>

            <div className="mt-4 rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div className="order-1 group relative">
                        <select
                            aria-label="Per page"
                            value={perPage}
                            onChange={(event) => applyPerPage(event.target.value)}
                            className="no-arrow w-[66px] rounded-md border border-slate-300 bg-white px-2 py-2 pr-8 text-sm"
                        >
                            <option value="10">10</option>
                            <option value="12">12</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <ChevronDown className="pointer-events-none absolute right-2 top-1/2 size-4 -translate-y-1/2 text-slate-500 transition-transform group-focus-within:rotate-180" />
                    </div>

                    <form
                        onSubmit={submitSearch}
                        className="order-3 flex w-full gap-2 sm:order-2 sm:w-auto"
                    >
                        <input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="w-full flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm sm:w-64 sm:flex-none"
                            placeholder="Search offers..."
                        />
                        <button
                            type="submit"
                            className="shrink-0 rounded-md bg-slate-900 px-4 py-2 text-sm text-white"
                        >
                            Search
                        </button>
                    </form>
                    <Link
                        href={route('admin.referral-offers.create')}
                        className="order-2 ml-auto rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800 sm:order-3"
                    >
                        New Offer
                    </Link>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Title</th>
                                <th className="px-3 py-2">Points</th>
                                <th className="px-3 py-2">Subject</th>
                                <th className="px-3 py-2">Limit</th>
                                <th className="px-3 py-2">Duration</th>
                                <th className="px-3 py-2">Category</th>
                                <th className="px-3 py-2">Brand</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {offers.data.map((offer) => (
                                <tr
                                    key={offer.id}
                                    className="border-t border-slate-200"
                                >
                                    <td className="px-3 py-2">
                                        <p className="font-semibold text-slate-900">
                                            {offer.title}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        {offer.points_cost}
                                    </td>
                                    <td className="px-3 py-2">
                                        {offer.subject || '-'}
                                    </td>
                                    <td className="px-3 py-2">
                                        {offer.question_limit || 'No limit'}
                                    </td>
                                    <td className="px-3 py-2">
                                        {offer.access_duration_days
                                            ? `${offer.access_duration_days} days`
                                            : 'No expiry'}
                                    </td>
                                    <td className="px-3 py-2">
                                        {offer.category || '-'}
                                    </td>
                                    <td className="px-3 py-2">
                                        {offer.brand || '-'}
                                    </td>
                                    <td className="px-3 py-2">
                                        <span
                                            className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${
                                                offer.is_active
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-slate-100 text-slate-600'
                                            }`}
                                        >
                                            {offer.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex gap-2">
                                            <Link
                                                href={route(
                                                    'admin.referral-offers.edit',
                                                    offer.id,
                                                )}
                                                className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sky-700 hover:bg-sky-50 hover:text-sky-900"
                                                title="Edit"
                                                aria-label="Edit"
                                            >
                                                <Pencil className="size-4" />
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openConfirm(offer.id)
                                                }
                                                className="inline-flex h-8 w-8 items-center justify-center rounded-md text-red-700 hover:bg-red-50 hover:text-red-900"
                                                title="Delete"
                                                aria-label="Delete"
                                            >
                                                <Trash2 className="size-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {offers.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={9}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No referral offers yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={offers.links} />
                </div>
            </div>

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete Offer"
                message="Are you sure you want to delete this referral offer? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
