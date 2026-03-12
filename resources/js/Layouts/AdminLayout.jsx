import { Link, usePage } from '@inertiajs/react';

const navItems = [
    { label: 'Dashboard', routeName: 'admin.dashboard' },
    { label: 'Users', routeName: 'admin.users.index' },
    { label: 'Referrals', routeName: 'admin.referrals.index' },
    { label: 'Plans', routeName: 'admin.plans.index' },
    { label: 'Subscriptions', routeName: 'admin.subscriptions.index' },
    { label: 'Subjects', routeName: 'admin.subjects.index' },
    { label: 'Questions', routeName: 'admin.questions.index' },
    { label: 'Quiz Attempts', routeName: 'admin.quiz-attempts.index' },
];

export default function AdminLayout({ title, children }) {
    const { auth, flash } = usePage().props;

    return (
        <div className="min-h-screen bg-slate-100">
            <div className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:flex-row lg:px-8">
                <aside className="w-full rounded-xl bg-slate-900 p-5 text-slate-100 lg:w-64">
                    <div className="mb-6">
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">
                            Admin
                        </p>
                        <h1 className="mt-2 text-xl font-semibold">
                            Review Center
                        </h1>
                    </div>

                    <nav className="space-y-1">
                        {navItems.map((item) => (
                            <Link
                                key={item.routeName}
                                href={route(item.routeName)}
                                className={`block rounded-md px-3 py-2 text-sm transition ${
                                    route().current(item.routeName) ||
                                    route().current(
                                        `${item.routeName.replace('.index', '')}.*`,
                                    )
                                        ? 'bg-slate-700 text-white'
                                        : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                                }`}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="mt-8 border-t border-slate-700 pt-4 text-sm text-slate-300">
                        <p className="font-medium">{auth.user?.name}</p>
                        <p className="text-xs">{auth.user?.email}</p>
                        <div className="mt-4 space-y-2">
                            <Link
                                href={route('dashboard')}
                                className="block text-sky-300 hover:text-sky-200"
                            >
                                User Dashboard
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="text-red-300 hover:text-red-200"
                            >
                                Log Out
                            </Link>
                        </div>
                    </div>
                </aside>

                <main className="flex-1 space-y-4">
                    <div className="rounded-xl bg-white px-6 py-5 shadow-sm">
                        <h2 className="text-2xl font-semibold text-slate-900">
                            {title}
                        </h2>
                    </div>

                    {flash?.success && (
                        <div className="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-700">
                            {flash.success}
                        </div>
                    )}

                    {flash?.error && (
                        <div className="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-700">
                            {flash.error}
                        </div>
                    )}

                    {children}
                </main>
            </div>
        </div>
    );
}
