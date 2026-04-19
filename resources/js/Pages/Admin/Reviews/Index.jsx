import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { ChevronDown, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ reviews, filters = {} }) {
    const [confirmingId, setConfirmingId] = useState(null);
    const [search, setSearch] = useState(filters.search || '');
    const [perPage, setPerPage] = useState(
        filters.per_page ? String(filters.per_page) : '10',
    );

    const openConfirm = (id) => setConfirmingId(id);
    const closeConfirm = () => setConfirmingId(null);

    const confirmDelete = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.reviews.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    const toggleReview = (review) => {
        router.patch(route('admin.reviews.toggle', review.id), {
            is_active: !review.is_active,
        });
    };

    const submitSearch = (event) => {
        event.preventDefault();
        router.get(
            route('admin.reviews.index'),
            { search, per_page: perPage },
            { preserveState: true, replace: true },
        );
    };

    const applyPerPage = (value) => {
        setPerPage(value);
        router.get(
            route('admin.reviews.index'),
            { search, per_page: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title="Reviews">
            <Head title="Admin Reviews" />

            <div className="max-w-full overflow-hidden rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="group relative">
                            <select
                                aria-label="Per page"
                                value={perPage}
                                onChange={(event) =>
                                    applyPerPage(event.target.value)
                                }
                                className="no-arrow w-[66px] rounded-md border border-slate-300 bg-white px-2 py-2 pr-8 text-sm"
                            >
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <ChevronDown className="pointer-events-none absolute right-2 top-1/2 size-4 -translate-y-1/2 text-slate-500 transition-transform group-focus-within:rotate-180" />
                        </div>
                        <form
                            onSubmit={submitSearch}
                            className="flex w-full gap-2 sm:w-auto"
                        >
                            <input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                className="w-full flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm sm:w-64 sm:flex-none"
                                placeholder="Search reviews..."
                            />
                            <button
                                type="submit"
                                className="shrink-0 rounded-md bg-slate-900 px-4 py-2 text-sm text-white"
                            >
                                Search
                            </button>
                        </form>
                    </div>
                </div>
                <div className="max-w-full overflow-x-auto overscroll-x-contain">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Reviewer</th>
                                <th className="hidden px-3 py-2 sm:table-cell">
                                    School
                                </th>
                                <th className="px-3 py-2">Rating</th>
                                <th className="hidden px-3 py-2 sm:table-cell">
                                    Quote
                                </th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {reviews.data.map((review) => (
                                <tr key={review.id} className="border-t border-slate-200">
                                    <td className="px-3 py-2 align-top">
                                        <p className="font-medium text-slate-900">
                                            {review.reviewer_name}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            Added {review.created_at}
                                        </p>
                                        <p className="mt-1 text-xs text-slate-500 sm:hidden">
                                            {review.reviewer_school || '—'}
                                        </p>
                                        <p className="mt-1 break-words text-xs text-slate-500 sm:hidden">
                                            "{review.quote}"
                                        </p>
                                    </td>
                                    <td className="hidden px-3 py-2 sm:table-cell">
                                        {review.reviewer_school || '—'}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex items-center gap-1 text-amber-500">
                                            {Array.from({ length: 5 }).map((_, index) => (
                                                <Star
                                                    key={`rating-${review.id}-${index}`}
                                                    className={`size-4 ${
                                                        index < (review.rating ?? 5)
                                                            ? 'fill-amber-400'
                                                            : 'fill-transparent text-slate-300'
                                                    }`}
                                                />
                                            ))}
                                        </div>
                                    </td>
                                    <td className="hidden px-3 py-2 text-slate-600 sm:table-cell">
                                        "{review.quote}"
                                    </td>
                                    <td className="px-3 py-2">
                                        <span
                                            className={`rounded-full px-2 py-1 text-xs font-semibold ${
                                                review.is_active
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-slate-100 text-slate-500'
                                            }`}
                                        >
                                            {review.is_active ? 'Active' : 'Hidden'}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex items-center gap-3 whitespace-nowrap">
                                            <label className="inline-flex cursor-pointer items-center">
                                                <input
                                                    type="checkbox"
                                                    checked={review.is_active}
                                                    onChange={() => toggleReview(review)}
                                                    className="peer sr-only"
                                                />
                                                <span className="relative inline-flex h-6 w-12 flex-shrink-0 rounded-full bg-slate-200 transition-colors duration-200 peer-checked:bg-emerald-500 after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform after:duration-200 after:content-[''] peer-checked:after:translate-x-6" />
                                            </label>
                                            <button
                                                type="button"
                                                onClick={() => openConfirm(review.id)}
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
                            {reviews.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No reviews available.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={reviews.links} />
                </div>
            </div>

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete Review"
                message="Are you sure you want to delete this review? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
