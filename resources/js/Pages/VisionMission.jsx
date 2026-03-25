import MarketingLayout from '@/Layouts/MarketingLayout';

const headingFont = { fontFamily: '"Sora", "Segoe UI", sans-serif' };

const visionCopy =
    'Boardmasters Review Center (BMRC) envisions itself to be the premier digital review provider in the Philippines, producing top-performing professionals through expert mentorship and cutting-edge technology to prepare future professionals for board examination success.';

const missionPoints = [
    'Deliver high-impact review programs aligned with current board examination standards and trends.',
    'Integrate advanced digital solutions, including an Online App for mock board examinations, current and relevant practice tests.',
    'Equip learners with critical thinking, test-taking mastery, and professional competence by blending traditional expertise with modern digital tools.',
    'Provide personalized guidance and performance analytics to help reviewees track progress and maximize results.',
];

const philosophyItems = [
    { letter: 'B', meaning: 'Building Board Passers' },
    { letter: 'M', meaning: 'Mastery through Review Innovation' },
    { letter: 'R', meaning: 'Readiness for Licensure' },
    { letter: 'C', meaning: 'Commitment for Excellent Results' },
];

const tagline = 'We Shape Board Exam Masters';

export default function VisionMission() {
    return (
        <MarketingLayout
            title="Vision & Mission | Board Masters Review Center"
            description="Learn about the vision, mission, philosophy, and tagline of Board Masters Review Center."
            ogTitle="Vision & Mission | Board Masters Review Center"
            ogDescription="Board Masters Review Center vision, mission, philosophy, and tagline."
        >
            <section className="reveal-on-scroll px-4 pb-10 pt-12 sm:px-6">
                <div className="mx-auto w-full max-w-6xl">
                    <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                        Board Masters Review Center
                    </p>
                    <h1 className="mt-3 text-4xl font-bold text-slate-900 sm:text-5xl" style={headingFont}>
                        Vision, mission, and philosophy that guide every reviewee.
                    </h1>
                    <p className="mt-4 max-w-3xl text-base text-slate-600 sm:text-lg">
                        Our statements define how we serve future board exam professionals through digital review
                        excellence.
                    </p>
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
                <div className="mx-auto w-full max-w-6xl">
                    <div className="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 lg:p-8">
                        <p className="text-xs font-semibold uppercase tracking-[0.15em] text-cyan-700">
                            Philosophy
                        </p>
                        <h2 className="mt-3 text-3xl font-bold text-slate-900" style={headingFont}>
                            The BMRC principles in action.
                        </h2>
                        <div className="mt-6 grid gap-4 sm:grid-cols-2">
                            {philosophyItems.map((item) => (
                                <div
                                    key={item.letter}
                                    className="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4"
                                >
                                    <div
                                        className="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-700 text-xl font-bold text-white"
                                        style={headingFont}
                                    >
                                        {item.letter}
                                    </div>
                                    <p className="text-sm font-semibold text-slate-900 sm:text-base">
                                        {item.meaning}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <section className="reveal-on-scroll px-4 pb-16 sm:px-6">
                <div className="mx-auto w-full max-w-6xl">
                    <div className="rounded-[32px] bg-slate-900 px-6 py-10 text-white shadow-xl shadow-slate-200/60 sm:px-8">
                        <p className="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-300">
                            Tagline
                        </p>
                        <h2 className="mt-3 text-3xl font-bold sm:text-4xl" style={headingFont}>
                            "{tagline}"
                        </h2>
                        <p className="mt-3 max-w-2xl text-sm text-slate-300 sm:text-base">
                            A promise to shape reviewees into confident, board-ready professionals.
                        </p>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
