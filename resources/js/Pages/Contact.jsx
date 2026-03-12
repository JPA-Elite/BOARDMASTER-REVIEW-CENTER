import MarketingLayout from '@/Layouts/MarketingLayout';
import {
    ArrowRight,
    CircleHelp,
    Mail,
    MessageSquareText,
    ShieldCheck,
} from 'lucide-react';
import { Link } from '@inertiajs/react';

const headingFont = { fontFamily: '"Sora", "Segoe UI", sans-serif' };

const contactHeroImage =
    'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/6912d21b832905c56a978aea_Untitled%20design%20(2).png';

const contactPreviewImage =
    'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/69131f3adfe009893d6711c3_Screenshot%202025-11-11%20at%207.34.00%E2%80%AFpm.png';

const contactOptions = [
    {
        title: 'General inquiries',
        description: 'Questions about plans, access, and setup.',
        icon: MessageSquareText,
    },
    {
        title: 'Academic support',
        description: 'Content clarifications and review guidance requests.',
        icon: ShieldCheck,
    },
    {
        title: 'Partnership requests',
        description: 'School and review center collaboration discussions.',
        icon: CircleHelp,
    },
];

const defaultFaqs = [
    {
        question: 'Does Board Masters focus on Nursing only?',
        answer: 'Nursing is a primary focus, but we also support other major Philippine licensure tracks.',
    },
    {
        question: 'Can review centers request demo access?',
        answer: 'Yes. Contact us by email so we can discuss a pilot setup based on your student batch size.',
    },
    {
        question: 'Where should we report question issues?',
        answer: 'Send the item details and screenshot to support@boardmasters.ph for content review.',
    },
];

export default function Contact({ faqs = defaultFaqs }) {
    const faqItems = faqs?.data ?? faqs ?? defaultFaqs;
    const faqLinks = faqs?.links ?? [];

    return (
        <MarketingLayout
            title="Contact | Board Masters Review Center"
            description="Contact Board Masters Review Center for support, partnership inquiries, and board exam review assistance."
            ogTitle="Contact | Board Masters Review Center"
            ogDescription="Get in touch with Board Masters for Nursing and licensure review support."
        >
            <section className="px-4 pb-14 pt-12 sm:px-6">
                <div className="mx-auto grid w-full max-w-6xl gap-8 lg:grid-cols-[1.02fr_0.98fr] lg:items-center">
                    <div>
                        <p className="inline-flex rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">
                            Contact Board Masters
                        </p>
                        <h1 className="mt-5 text-4xl font-bold leading-tight text-slate-900 sm:text-5xl" style={headingFont}>
                            Let us support your board review journey.
                        </h1>
                        <p className="mt-5 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
                            For student support, review concerns, or institutional requests, our team is ready to help.
                            Email us and include your exam track so we can respond faster.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center gap-3">
                            <a
                                href="mailto:support@boardmasters.ph"
                                className="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                <Mail className="h-4 w-4" />
                                support@boardmasters.ph
                            </a>
                            <a
                                href="/partnership"
                                className="inline-flex items-center gap-2 rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                            >
                                Partnership page
                                <ArrowRight className="h-4 w-4" />
                            </a>
                        </div>
                    </div>

                    <div className="relative">
                        <div className="absolute -left-6 -top-6 h-24 w-24 rounded-full bg-amber-300/60 blur-2xl" />
                        <div className="rounded-[30px] border border-slate-200 bg-white p-3 shadow-xl shadow-slate-200/60">
                            <img
                                src={contactHeroImage}
                                alt="Board Masters contact preview"
                                className="h-full w-full rounded-3xl object-cover"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section className="px-4 pb-14 sm:px-6">
                <div className="mx-auto grid w-full max-w-6xl gap-4 md:grid-cols-3">
                    {contactOptions.map((option) => {
                        const OptionIcon = option.icon;

                        return (
                            <article
                                key={option.title}
                                className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60"
                            >
                                <span className="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-700">
                                    <OptionIcon className="h-5 w-5" />
                                </span>
                                <h2 className="mt-4 text-lg font-bold text-slate-900" style={headingFont}>
                                    {option.title}
                                </h2>
                                <p className="mt-2 text-sm leading-relaxed text-slate-600">{option.description}</p>
                            </article>
                        );
                    })}
                </div>
            </section>

            <section className="px-4 pb-14 sm:px-6">
                <div className="mx-auto grid w-full max-w-6xl gap-6 rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 lg:grid-cols-[1fr_1fr] lg:p-8">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                            Support Channel
                        </p>
                        <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                            Email is our official support line.
                        </h2>
                        <p className="mt-3 text-sm leading-relaxed text-slate-600">
                            Please include your full name, exam track, and concern details so we can route your request
                            to the right team immediately.
                        </p>
                        <div className="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p className="text-xs font-semibold uppercase tracking-[0.15em] text-slate-500">
                                Official support email
                            </p>
                            <a
                                href="mailto:support@boardmasters.ph"
                                className="mt-2 inline-flex items-center gap-2 text-lg font-bold text-slate-900"
                                style={headingFont}
                            >
                                <Mail className="h-5 w-5" />
                                support@boardmasters.ph
                            </a>
                        </div>
                    </div>
                    <img
                        src={contactPreviewImage}
                        alt="Board Masters application preview"
                        className="h-full w-full rounded-3xl border border-slate-200 object-cover"
                    />
                </div>
            </section>

            <section className="px-4 pb-16 sm:px-6">
                <div className="mx-auto w-full max-w-6xl">
                    <h2 className="text-3xl font-bold text-slate-900" style={headingFont}>
                        Frequently asked questions
                    </h2>
                    <div className="mt-6 grid gap-4 md:grid-cols-3">
                        {faqItems.map((faq, index) => (
                            <article
                                key={`${faq.question}-${index}`}
                                className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60"
                            >
                                <h3 className="text-base font-bold text-slate-900" style={headingFont}>
                                    {faq.question}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-slate-600">{faq.answer}</p>
                            </article>
                        ))}
                    </div>

                    {faqLinks.length > 0 && (
                        <div className="mt-8 flex flex-wrap justify-center gap-2">
                            {faqLinks.map((link, index) => (
                                <Link
                                    key={`${link.label}-${index}`}
                                    href={link.url || '#'}
                                    preserveScroll
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className={`rounded-full border px-3 py-1.5 text-xs font-semibold transition ${
                                        link.active
                                            ? 'border-slate-900 bg-slate-900 text-white'
                                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                    } ${!link.url ? 'pointer-events-none opacity-60' : ''}`}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </section>
        </MarketingLayout>
    );
}
