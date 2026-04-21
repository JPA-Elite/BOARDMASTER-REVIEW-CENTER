import MarketingLayout from '@/Layouts/MarketingLayout';
import ImageWithSkeleton from '@/Components/Marketing/ImageWithSkeleton';
import {
    ArrowRight,
    CircleHelp,
    Mail,
    MessageSquareText,
    ShieldCheck,
} from 'lucide-react';
import { Link } from '@inertiajs/react';

const headingFont = { fontFamily: '"Sora", "Segoe UI", sans-serif' };

const contactHeroImage = '/images/landing/mobile/mobile-auth-profile.webp';

const contactPreviewImage = '/images/landing/mobile/mobile-auth-reviews-2.webp';

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
        question: 'Does BoardMasters focus on Nursing only?',
        answer: 'Nursing is a primary focus, but we also support other major Philippine licensure tracks.',
    },
    {
        question: 'Can review centers request demo access?',
        answer: 'Yes. Contact us by email so we can discuss a pilot setup based on your student batch size.',
    },
    {
        question: 'Where should we report question issues?',
        answer: 'Send the item details and screenshot to boardmastersreviewcenter@gmail.com for content review.',
    },
];

export default function Contact({ faqs = defaultFaqs }) {
    const faqItems = faqs?.data ?? faqs ?? defaultFaqs;
    const faqLinks = faqs?.links ?? [];

    return (
        <MarketingLayout
            title="Contact | BoardMasters Review Center"
            description="Contact BoardMasters Review Center for support, partnership inquiries, and board exam review assistance."
            ogTitle="Contact | BoardMasters Review Center"
            ogDescription="Get in touch with BoardMasters for Nursing and licensure review support."
            preloadImages={[contactHeroImage]}
        >
            <section className="reveal-on-scroll px-4 pb-14 pt-12 sm:px-6">
                <div className="mx-auto grid w-full max-w-6xl gap-8 lg:grid-cols-[1.02fr_0.98fr] lg:items-center">
                    <div>
                        <p className="inline-flex rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">
                            Contact BoardMasters
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
                                href="mailto:boardmastersreviewcenter@gmail.com"
                                className="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                <Mail className="h-4 w-4" />
                                boardmastersreviewcenter@gmail.com
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
                            <ImageWithSkeleton
                                src={contactHeroImage}
                                alt="BoardMasters Contact Preview"
                                wrapperClassName="overflow-hidden rounded-3xl bg-slate-100"
                                className="mx-auto max-h-[620px] w-full rounded-3xl object-contain"
                                skeletonClassName="rounded-3xl"
                                skeletonHeightClassName="h-[620px]"
                                loading="eager"
                                fetchPriority="high"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-14 sm:px-6">
                <div className="mx-auto grid w-full max-w-6xl gap-4 md:grid-cols-3">
                    {contactOptions.map((option) => {
                        const OptionIcon = option.icon;

                        return (
                            <article
                                key={option.title}
                                className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 transition hover:-translate-y-1 hover:border-slate-300 hover:shadow-md hover:shadow-slate-200/70"
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

            <section className="reveal-on-scroll px-4 pb-16 sm:px-6">
                <div className="mx-auto w-full max-w-6xl">
                    <h2 className="text-3xl font-bold text-slate-900" style={headingFont}>
                        Frequently asked questions
                    </h2>
                    <div className="mt-6 grid gap-4 md:grid-cols-3">
                        {faqItems.map((faq, index) => (
                            <article
                                key={`${faq.question}-${index}`}
                                className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 transition hover:-translate-y-1 hover:border-slate-300 hover:shadow-md hover:shadow-slate-200/70"
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
