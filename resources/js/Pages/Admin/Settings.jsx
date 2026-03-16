import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm';

export default function Settings({
    mustVerifyEmail,
    status,
}) {
    return (
        <AdminLayout title="Admin Settings">
            <Head title="Admin Settings" />

            <div className="space-y-6">
                <div className="rounded-2xl border border-slate-200 bg-white p-6">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-xl"
                    />
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-6">
                    <UpdatePasswordForm className="max-w-xl" />
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 className="text-lg font-semibold text-slate-900">
                        Payment Gateway Test
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Configure and test PayMongo checkout in a dedicated screen.
                    </p>
                    <div className="mt-4">
                        <Link
                            href={route('admin.payment-gateway')}
                            className="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Open Payment Gateway
                        </Link>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
