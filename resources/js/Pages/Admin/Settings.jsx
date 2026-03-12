import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm';

export default function Settings({ mustVerifyEmail, status }) {
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

            </div>
        </AdminLayout>
    );
}
