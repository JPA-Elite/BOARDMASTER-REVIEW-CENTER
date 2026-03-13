import MarketingLayout from '@/Layouts/MarketingLayout';
import { Link } from '@inertiajs/react';

export default function Success() {
    return (
        <MarketingLayout
            title="Payment Successful"
            description="Your payment has been received. Thank you for your purchase."
            ogTitle="Payment Successful"
            ogDescription="Your payment has been received."
        >
            <section className="mx-auto flex w-full max-w-4xl flex-col items-center px-4 py-20 text-center sm:px-6">
                <div className="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        className="h-7 w-7"
                    >
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                </div>
                <h1 className="mt-6 text-3xl font-semibold text-slate-900 sm:text-4xl">
                    Payment confirmed
                </h1>
                <p className="mt-3 max-w-xl text-base text-slate-600">
                    Thanks for completing your payment. Your subscription will be
                    activated shortly after we receive confirmation from PayMongo.
                </p>
                <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <Link
                        href="/"
                        className="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Back to Home
                    </Link>
                    <Link
                        href="/dashboard"
                        className="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-slate-900 hover:text-slate-900"
                    >
                        Go to Dashboard
                    </Link>
                </div>
            </section>
        </MarketingLayout>
    );
}
