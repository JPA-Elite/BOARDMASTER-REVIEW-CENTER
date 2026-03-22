import MarketingLayout from '@/Layouts/MarketingLayout';
import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpenText,
    BrainCircuit,
    ChartLine,
    Clock3,
    GraduationCap,
    ShieldCheck,
} from 'lucide-react';

const headingFont = { fontFamily: '"Sora", "Segoe UI", sans-serif' };

const heroImage =
    'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/691311bfb9ad0af2fcda9014_OneTake%20Banner%20(7).png';

const collageImages = [
    'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/6912aab9280ef627c50c6f1d_Screenshot%202025-11-11%20at%2011.16.49%E2%80%AFam.png',
    'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/6912a7718f9f8d7fdd67a940_b3609a748d082a59c30c590e24706470_imansyah-muhamad-putera-KchIV_GDy6U-unsplash.jpg',
    'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/69131f3a230396bbbb7191b4_Screenshot%202025-11-11%20at%207.33.35%E2%80%AFpm.png',
    'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/6912ab0a70d8ddd44e546312_Screenshot%202025-11-11%20at%2011.18.19%E2%80%AFam.png',
];

const features = [
    {
        title: 'Rationalized Answers',
        description: 'Every question includes a practical explanation so students understand why each answer is correct.',
        icon: BookOpenText,
    },
    {
        title: 'Nursing-Focused Review',
        description: 'Priority content for PNLE plus dedicated tracks for other major licensure programs.',
        icon: GraduationCap,
    },
    {
        title: 'Timed Mock Board Sessions',
        description: 'Simulate the real exam pressure with timed sets, randomization, and readiness scoring.',
        icon: Clock3,
    },
    {
        title: 'Progress Analytics',
        description: 'Track weak topics, review streaks, and subject-level performance in one dashboard.',
        icon: ChartLine,
    },
    {
        title: 'Smart Study Guidance',
        description: 'Focus on what to review next using objective data from your recent attempts.',
        icon: BrainCircuit,
    },
    {
        title: 'Reliable Learning Standards',
        description: 'Question quality is reviewed with consistency checks for accuracy and clarity.',
        icon: ShieldCheck,
    },
];

const examTracks = [
    'Nursing (PNLE)',
    'Teacher Education (LET)',
    'Criminology (CLE)',
    'Civil Engineering (CELE)',
    'Midwifery (MLE)',
    'Radiologic Technology (RTLE)',
];

const steps = [
    {
        label: 'Step 01',
        title: 'Choose your board track',
        description: 'Start with Nursing or your specific licensure program and set your study target date.',
    },
    {
        label: 'Step 02',
        title: 'Answer focused daily sets',
        description: 'Practice by subject and topic to build speed, recall, and confidence each week.',
    },
    {
        label: 'Step 03',
        title: 'Review rationales and weak areas',
        description: 'Use explanations and analytics to adjust your strategy before exam week.',
    },
    {
        label: 'Step 04',
        title: 'Take mock boards and validate readiness',
        description: 'Run full simulations to measure improvement and improve test-day consistency.',
    },
];

const outcomes = [
    {
        title: 'Study Plan Builder',
        description: 'Turn your target exam date into a week-by-week plan with daily sets and review checkpoints.',
    },
    {
        title: 'Mock Board Readiness',
        description: 'Timed full-length simulations that mirror pacing, pressure, and scoring confidence.',
    },
    {
        title: 'Weak Area Focus',
        description: 'Automatic recommendations highlight the topics that need the most attention right now.',
    },
    {
        title: 'Answer Rationales',
        description: 'Detailed explanations support long-term retention and help you avoid repeated mistakes.',
    },
];

const visionCopy =
    'Boardmasters Review Center (BMRC) envisions itself to be the premier digital review provider in the Philippines, producing top-performing professionals through expert mentorship and cutting-edge technology to prepare future professionals for board examination success.';

const missionPoints = [
    'Deliver high-impact review programs aligned with current board examination standards and trends.',
    'Integrate advanced digital solutions, including an Online App for mock board examinations, current and relevant practice tests.',
    'Equip learners with critical thinking, test-taking mastery, and professional competence by blending traditional expertise with modern digital tools.',
    'Provide personalized guidance and performance analytics to help reviewees track progress and maximize results.',
];

const defaultReviews = [
    {
        quote:
            'Board Masters is the biggest game changer. It stopped me from spending too much time on topics I already mastered.',
        reviewer_name: 'Patrick Bat-Og',
        reviewer_school: 'Asia Pacific College',
    },
    {
        quote:
            'The step-by-step explanations made the lessons stick. It finally clicked for me.',
        reviewer_name: 'Sophie Dizon',
        reviewer_school: 'Wesleyan University',
    },
    {
        quote:
            'Everything is organized in one app. I can practice on the bus and still track my progress.',
        reviewer_name: 'Matt Singson',
        reviewer_school: 'Ateneo de Naga',
    },
];

const defaultFaqs = [
    {
        question: 'Does Board Masters cover Nursing and other board exam tracks?',
        answer:
            'Yes. We support Nursing (PNLE), Teacher Education (LET), Criminology (CLE), Civil Engineering (CELE), Midwifery (MLE), and Radiologic Technology (RTLE).',
    },
    {
        question: 'How often is the question bank updated?',
        answer:
            'Our content team continuously reviews and updates questions and explanations to align with current coverage and guidelines.',
    },
    {
        question: 'Can I use Board Masters offline?',
        answer:
            'You need internet for login and syncing. Once content is loaded, many practice features can be accessed offline.',
    },
    {
        question: 'What makes Board Masters different from printed reviewers?',
        answer:
            'We provide objective analytics, weak-topic tracking, and targeted practice so your time is spent on what matters most.',
    },
];

export default function Welcome({ faqs = defaultFaqs, reviews = defaultReviews }) {
    const faqItems = faqs?.data ?? faqs ?? defaultFaqs;
    const reviewItems = reviews?.data ?? reviews ?? defaultReviews;

    return (
        <MarketingLayout
            title="Board Masters Review Center | Nursing and Board Exam Review"
            description="Creative and professional board exam review website focused on Nursing and other Philippine licensure tracks."
            ogTitle="Board Masters Review Center | Nursing and Board Exam Review"
            ogDescription="Board Masters provides board exam practice questions with rationalized answers and structured study tracking."
        >
            <section className="reveal-on-scroll px-4 pb-14 pt-12 sm:px-6">
                <div className="mx-auto grid w-full max-w-6xl gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                    <div>
                        <p className="inline-flex rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">
                            Board Exam Review Platform
                        </p>
                        <h1 className="mt-5 text-4xl font-bold leading-tight text-slate-900 sm:text-5xl" style={headingFont}>
                            Build your board exam confidence with guided, answer-based review.
                        </h1>
                        <p className="mt-5 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
                            Board Masters Review Center helps students prepare for Nursing and other licensure exams
                            through realistic question sets, clear rationales, and progress analytics.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center gap-3">
                            <Link
                                href="/contact"
                                className="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                Talk to our team
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                            <Link
                                href="/partnership"
                                className="inline-flex items-center rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                            >
                                Partner with us
                            </Link>
                        </div>
                        <div className="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <p className="text-2xl font-bold text-slate-900" style={headingFont}>
                                    10k+
                                </p>
                                <p className="text-xs text-slate-500">Practice questions</p>
                            </div>
                            <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <p className="text-2xl font-bold text-slate-900" style={headingFont}>
                                    6
                                </p>
                                <p className="text-xs text-slate-500">Review tracks</p>
                            </div>
                            <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <p className="text-2xl font-bold text-slate-900" style={headingFont}>
                                    24/7
                                </p>
                                <p className="text-xs text-slate-500">Online access</p>
                            </div>
                            <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <p className="text-2xl font-bold text-slate-900" style={headingFont}>
                                    1
                                </p>
                                <p className="text-xs text-slate-500">Unified review hub</p>
                            </div>
                        </div>
                    </div>

                    <div className="relative">
                        <div className="absolute -left-6 -top-6 h-24 w-24 rounded-full bg-amber-300/60 blur-2xl" />
                        <div className="rounded-[30px] border border-slate-200 bg-white p-3 shadow-xl shadow-slate-200/60">
                            <img
                                src={heroImage}
                                alt="Board Masters Review Center dashboard preview"
                                className="h-full w-full rounded-3xl object-cover"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 py-14 sm:px-6">
                <div className="mx-auto w-full max-w-6xl">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                                Why students choose Board Masters
                            </p>
                            <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                                Professional review tools built for real board exam preparation.
                            </h2>
                        </div>
                    </div>

                    <div className="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {features.map((feature) => {
                            const FeatureIcon = feature.icon;

                            return (
                                <article
                                    key={feature.title}
                                    className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60"
                                >
                                    <span className="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-700">
                                        <FeatureIcon className="h-5 w-5" />
                                    </span>
                                    <h3 className="mt-4 text-lg font-bold text-slate-900" style={headingFont}>
                                        {feature.title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-slate-600">
                                        {feature.description}
                                    </p>
                                </article>
                            );
                        })}
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-14 sm:px-6">
                <div className="mx-auto grid w-full max-w-6xl gap-6 rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 lg:grid-cols-[1.1fr_0.9fr] lg:p-8">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                            Programs Covered
                        </p>
                        <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                            Review tracks for Nursing and other high-demand licensure exams.
                        </h2>
                        <div className="mt-6 grid gap-2 sm:grid-cols-2">
                            {examTracks.map((track) => (
                                <div
                                    key={track}
                                    className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700"
                                >
                                    {track}
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        {collageImages.map((image, index) => (
                            <img
                                key={image}
                                src={image}
                                alt={`Board Masters review preview ${index + 1}`}
                                className="h-full min-h-[130px] w-full rounded-2xl border border-slate-200 object-cover"
                            />
                        ))}
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-14 sm:px-6">
                <div className="mx-auto w-full max-w-6xl">
                    <h2 className="text-3xl font-bold text-slate-900" style={headingFont}>
                        A clear review process from day one to exam day.
                    </h2>
                    <div className="mt-6 grid gap-4 md:grid-cols-2">
                        {steps.map((step) => (
                            <article
                                key={step.label}
                                className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60"
                            >
                                <p className="text-xs font-semibold uppercase tracking-[0.15em] text-amber-600">
                                    {step.label}
                                </p>
                                <h3 className="mt-2 text-lg font-bold text-slate-900" style={headingFont}>
                                    {step.title}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-slate-600">
                                    {step.description}
                                </p>
                            </article>
                        ))}
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-14 sm:px-6">
                <div className="mx-auto w-full max-w-6xl">
                    <div className="grid gap-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                        <div className="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 lg:p-8">
                            <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                                Vision
                            </p>
                            <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                                A premier digital review provider for board exam success.
                            </h2>
                            <p className="mt-4 text-sm leading-relaxed text-slate-600 sm:text-base">
                                {visionCopy}
                            </p>
                        </div>
                        <div className="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 lg:p-8">
                            <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                                Mission
                            </p>
                            <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                                How BMRC delivers impact for reviewees.
                            </h2>
                            <ul className="mt-4 space-y-3 text-sm text-slate-600 sm:text-base">
                                {missionPoints.map((point) => (
                                    <li key={point} className="flex gap-3">
                                        <span className="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-cyan-600" />
                                        <span className="leading-relaxed">{point}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-14 sm:px-6">
                <div className="mx-auto grid w-full max-w-6xl gap-6 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <div className="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 lg:p-8">
                        <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                            What you gain
                        </p>
                        <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                            A smarter, data-driven review experience.
                        </h2>
                        <p className="mt-3 text-sm text-slate-600 sm:text-base">
                            Board Masters combines structured content, performance insights, and daily habits to keep
                            you on track from your first quiz up to exam day.
                        </p>
                        <div className="mt-6 space-y-4">
                            {outcomes.map((item) => (
                                <div key={item.title} className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p className="text-sm font-semibold text-slate-900">{item.title}</p>
                                    <p className="mt-2 text-sm text-slate-600">{item.description}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                            <p className="text-sm font-semibold text-slate-900">Weekly focus reports</p>
                            <p className="mt-2 text-sm text-slate-600">
                                See mastery scores by subject and drill down into weak topics every week.
                            </p>
                        </div>
                        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                            <p className="text-sm font-semibold text-slate-900">Daily set generator</p>
                            <p className="mt-2 text-sm text-slate-600">
                                Build balanced daily sets that mix new topics with reinforcement drills.
                            </p>
                        </div>
                        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                            <p className="text-sm font-semibold text-slate-900">Leaderboard motivation</p>
                            <p className="mt-2 text-sm text-slate-600">
                                Track your standing in group reviews without exposing personal data.
                            </p>
                        </div>
                        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                            <p className="text-sm font-semibold text-slate-900">Progress streaks</p>
                            <p className="mt-2 text-sm text-slate-600">
                                Consistency tools keep you accountable and highlight your review rhythm.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-14 sm:px-6">
                <div className="mx-auto w-full max-w-6xl">
                    <div className="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 lg:p-8">
                        <div className="flex flex-wrap items-center justify-between gap-6">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                                    Trusted by reviewees
                                </p>
                                <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                                    Stories from students who prepared with Board Masters.
                                </h2>
                            </div>
                            <div className="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600">
                                1,500+ students took our mock board last semester.
                            </div>
                        </div>
                        <div className="mt-6 grid gap-4 md:grid-cols-3">
                            {reviewItems.map((item, index) => (
                                <div
                                    key={`${item.reviewer_name}-${index}`}
                                    className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60"
                                >
                                    <p className="text-sm text-slate-700">"{item.quote}"</p>
                                    <p className="mt-4 text-sm font-semibold text-slate-900">
                                        {item.reviewer_name}
                                    </p>
                                    <p className="text-xs text-slate-500">{item.reviewer_school}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-14 sm:px-6">
                <div className="mx-auto w-full max-w-6xl">
                    <div className="grid gap-6 lg:grid-cols-[0.95fr_1.05fr] lg:items-start">
                        <div className="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 lg:p-8">
                            <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                                Support built in
                            </p>
                            <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                                Answers, accountability, and a clear roadmap.
                            </h2>
                            <p className="mt-3 text-sm text-slate-600 sm:text-base">
                                Whether you are reviewing alone or with a group, Board Masters gives you a structured
                                roadmap plus insights that show exactly what to work on next.
                            </p>
                            <div className="mt-6 space-y-4">
                                <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p className="text-sm font-semibold text-slate-900">Weekly performance snapshot</p>
                                    <p className="mt-2 text-sm text-slate-600">
                                        See your average scores, top strengths, and the subjects that need attention.
                                    </p>
                                </div>
                                <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <p className="text-sm font-semibold text-slate-900">Exam-day checklist</p>
                                    <p className="mt-2 text-sm text-slate-600">
                                        Stay confident with a final week checklist and readiness milestones.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 lg:p-8">
                            <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                                Frequently asked questions
                            </p>
                            <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                                Quick answers before you start.
                            </h2>
                            <div className="mt-6 space-y-4">
                                {faqItems.map((faq, index) => (
                                    <div
                                        key={`${faq.question}-${index}`}
                                        className="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                                    >
                                        <p className="text-sm font-semibold text-slate-900">{faq.question}</p>
                                        <p className="mt-2 text-sm text-slate-600">{faq.answer}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-16 sm:px-6">
                <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 rounded-[32px] bg-slate-900 px-6 py-8 text-white sm:px-8 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-300">
                            Ready to start?
                        </p>
                        <h2 className="mt-2 text-3xl font-bold leading-tight" style={headingFont}>
                            Prepare smarter with Board Masters Review Center.
                        </h2>
                        <p className="mt-2 max-w-2xl text-sm text-slate-300">
                            Launch your review with structured practice, answer rationales, and clear progress tracking.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <a
                            href="https://apps.apple.com/ph/app/onetake-ph/id6752534980"
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-900"
                        >
                            <img
                                src="https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/68edddcd5c07668c00987d2b_ee73b4d94460f8a2c07e5e266ffc8b3d_apple-white-icon-elements-brix-templates.svg"
                                alt="Apple icon"
                                className="h-4 w-4 invert"
                            />
                            iOS App
                        </a>
                        <a
                            href="https://play.google.com/store/apps/details?id=ph.boardly"
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 rounded-full border border-white/40 px-4 py-2 text-sm font-semibold text-white"
                        >
                            <img
                                src="https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/68edddcd5c07668c00987d2a_c1cd2666a020fc416efc02f9e22a61be_play-store-color-icon-elements-brix-templates.svg"
                                alt="Play Store icon"
                                className="h-4 w-4"
                            />
                            Android App
                        </a>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
