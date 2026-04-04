import MarketingLayout from '@/Layouts/MarketingLayout';
import ImageWithSkeleton from '@/Components/Marketing/ImageWithSkeleton';
import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    ChartNoAxesCombined,
    GraduationCap,
    Handshake,
    UsersRound,
} from 'lucide-react';

const headingFont = { fontFamily: '"Sora", "Segoe UI", sans-serif' };

const partnershipHeroImage =
    '/images/landing/mobile/mobile-auth-dashboard-mid.webp';

const partnershipSecondaryImage =
    '/images/landing/mobile/mobile-auth-dashboard-bottom.webp';

const partnershipBenefits = [
    {
        title: 'Structured cohort rollout',
        description: 'Launch your review batches with aligned subjects, schedules, and milestone checks.',
        icon: Building2,
    },
    {
        title: 'Faculty and mentor visibility',
        description: 'Monitor learner trends and identify topics where classes need reinforcement.',
        icon: UsersRound,
    },
    {
        title: 'Performance-driven outcomes',
        description: 'Use objective data to improve completion rates and board exam readiness.',
        icon: ChartNoAxesCombined,
    },
];

const models = [
    {
        title: 'School partnership package',
        description: 'For colleges that want a unified digital board review layer for graduating students.',
    },
    {
        title: 'Review center integration',
        description: 'For review centers that need high-volume question banks with answer rationales and analytics.',
    },
    {
        title: 'Mock board campaign',
        description: 'For institutions that want timed pre-board events and post-exam analytics reports.',
    },
];

const process = [
    {
        label: '01',
        title: 'Discovery meeting',
        description: 'We align on your board programs, student volume, and expected outcomes.',
    },
    {
        label: '02',
        title: 'Pilot setup',
        description: 'A guided launch with selected learners and reporting checkpoints.',
    },
    {
        label: '03',
        title: 'Full implementation',
        description: 'Scale to all target cohorts with support for onboarding and engagement.',
    },
];

export default function Partnership() {
    return (
        <MarketingLayout
            title="Partnership | Boardmasters Review Center"
            description="Partner with Boardmasters Review Center for Nursing and licensure-focused digital board exam preparation."
            ogTitle="Partnership | Boardmasters Review Center"
            ogDescription="Collaborate with Boardmasters for school and review center board exam solutions."
            preloadImages={[partnershipHeroImage, partnershipSecondaryImage]}
        >
            <section className="reveal-on-scroll px-4 pb-14 pt-12 sm:px-6">
                <div className="mx-auto grid w-full max-w-6xl gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                    <div>
                        <p className="inline-flex rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">
                            Partnership Program
                        </p>
                        <h1 className="mt-5 text-4xl font-bold leading-tight text-slate-900 sm:text-5xl" style={headingFont}>
                            Build stronger board exam outcomes with a smarter review partnership.
                        </h1>
                        <p className="mt-5 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
                            Boardmasters Review Center works with schools, departments, and review centers to deliver
                            structured digital board preparation, especially for Nursing and allied programs.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center gap-3">
                            <Link
                                href="/contact"
                                className="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                Start partnership discussion
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                            <a
                                href="mailto:boardmastersreviewcenter@gmail.com"
                                className="inline-flex items-center rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                            >
                                boardmastersreviewcenter@gmail.com
                            </a>
                        </div>
                    </div>

                    <div className="relative">
                        <div className="absolute -left-6 -top-6 h-24 w-24 rounded-full bg-amber-300/60 blur-2xl" />
                        <div className="rounded-[30px] border border-slate-200 bg-white p-3 shadow-xl shadow-slate-200/60">
                            <ImageWithSkeleton
                                src={partnershipHeroImage}
                                alt="Boardmasters partnership analytics preview"
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
                <div className="mx-auto w-full max-w-6xl">
                    <h2 className="text-3xl font-bold text-slate-900" style={headingFont}>
                        Why institutions partner with Boardmasters
                    </h2>
                    <div className="mt-6 grid gap-4 md:grid-cols-3">
                        {partnershipBenefits.map((benefit) => {
                            const BenefitIcon = benefit.icon;

                            return (
                                <article
                                    key={benefit.title}
                                    className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60"
                                >
                                    <span className="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-700">
                                        <BenefitIcon className="h-5 w-5" />
                                    </span>
                                    <h3 className="mt-4 text-lg font-bold text-slate-900" style={headingFont}>
                                        {benefit.title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-slate-600">
                                        {benefit.description}
                                    </p>
                                </article>
                            );
                        })}
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-14 sm:px-6">
                <div className="mx-auto grid w-full max-w-6xl gap-6 rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 lg:grid-cols-[1fr_1fr] lg:p-8">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                            Collaboration Models
                        </p>
                        <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                            Flexible formats for schools and review centers.
                        </h2>
                        <div className="mt-6 space-y-3">
                            {models.map((model) => (
                                <article key={model.title} className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <h3 className="text-base font-bold text-slate-900" style={headingFont}>
                                        {model.title}
                                    </h3>
                                    <p className="mt-1 text-sm text-slate-600">{model.description}</p>
                                </article>
                            ))}
                        </div>
                    </div>
                    <ImageWithSkeleton
                        src={partnershipSecondaryImage}
                        alt="Boardmasters collaboration model interface preview"
                        wrapperClassName="overflow-hidden rounded-3xl border border-slate-200 bg-slate-100"
                        className="mx-auto max-h-[620px] w-full rounded-3xl object-contain"
                        skeletonClassName="rounded-3xl"
                        skeletonHeightClassName="h-[620px]"
                    />
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-14 sm:px-6">
                <div className="mx-auto w-full max-w-6xl">
                    <h2 className="text-3xl font-bold text-slate-900" style={headingFont}>
                        Partnership process
                    </h2>
                    <div className="mt-6 grid gap-4 md:grid-cols-3">
                        {process.map((item) => (
                            <article
                                key={item.label}
                                className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60"
                            >
                                <p className="text-xs font-semibold uppercase tracking-[0.15em] text-amber-600">
                                    Step {item.label}
                                </p>
                                <h3 className="mt-2 text-lg font-bold text-slate-900" style={headingFont}>
                                    {item.title}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-slate-600">
                                    {item.description}
                                </p>
                            </article>
                        ))}
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-16 sm:px-6">
                <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 rounded-[32px] bg-slate-900 px-6 py-8 text-white sm:px-8 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.15em] text-cyan-300">
                            <Handshake className="h-4 w-4" />
                            Institutional Collaboration
                        </p>
                        <h2 className="mt-2 text-3xl font-bold leading-tight" style={headingFont}>
                            Let us help your students prepare with confidence.
                        </h2>
                        <p className="mt-2 max-w-2xl text-sm text-slate-300">
                            Share your board program goals and we will recommend a practical rollout plan.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <Link
                            href="/contact"
                            className="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900"
                        >
                            Contact us
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                        <a
                            href="mailto:boardmastersreviewcenter@gmail.com"
                            className="inline-flex items-center gap-2 rounded-full border border-white/40 px-4 py-2 text-sm font-semibold text-white"
                        >
                            <GraduationCap className="h-4 w-4" />
                            boardmastersreviewcenter@gmail.com
                        </a>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}

