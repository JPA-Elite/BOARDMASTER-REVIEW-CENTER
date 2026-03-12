import { useMemo } from 'react';
import MarketingLayout from '@/Layouts/MarketingLayout';

const headingFont = { fontFamily: '"Sora", "Segoe UI", sans-serif' };

const extractContentBlocks = (html) => {
    if (!html) {
        return [];
    }

    const match = html.match(
        /<p class="brix---paragraph-default-5">([\s\S]*?)<\/p>/,
    );
    const content = match ? match[1] : html;

    return content
        .split(/<br\s*\/?>\s*<br\s*\/?>/gi)
        .map((block) => block.trim())
        .filter(Boolean);
};

export default function PolicyPage({
    title,
    heading,
    description,
    ogTitle,
    ogDescription,
    bodyHtml,
    subtitle = 'Legal',
}) {
    const blocks = useMemo(() => extractContentBlocks(bodyHtml), [bodyHtml]);

    return (
        <MarketingLayout
            title={title}
            description={description}
            ogTitle={ogTitle}
            ogDescription={ogDescription}
        >
            <section className="px-4 pb-8 pt-12 sm:px-6">
                <div className="mx-auto w-full max-w-5xl">
                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">
                        {subtitle}
                    </p>
                    <h1
                        className="mt-3 text-3xl font-bold text-slate-900 sm:text-4xl"
                        style={headingFont}
                    >
                        {heading ?? title}
                    </h1>
                    {description ? (
                        <p className="mt-4 max-w-3xl text-sm text-slate-600 sm:text-base">
                            {description}
                        </p>
                    ) : null}
                </div>
            </section>

            <section className="px-4 pb-16 sm:px-6">
                <div className="mx-auto w-full max-w-5xl">
                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-10">
                        <div className="space-y-5 policy-content">
                            {blocks.map((block, index) => (
                                <p
                                    key={`policy-block-${index}`}
                                    className="m-0 text-sm leading-7 text-slate-700 sm:text-base"
                                    dangerouslySetInnerHTML={{ __html: block }}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
