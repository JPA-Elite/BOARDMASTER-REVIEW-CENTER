import { Head, Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const navLinks = [
    { href: '/', label: 'Home' },
    { href: '/partnership', label: 'Partnership' },
    { href: '/contact', label: 'Contact' },
];

const headingFont = { fontFamily: '"Sora", "Segoe UI", sans-serif' };

export default function MarketingLayout({
    title,
    description,
    ogTitle,
    ogDescription,
    ogImage,
    canonicalUrl,
    noindex = false,
    jsonLd,
    children,
    preloadImages = [],
}) {
    const { url } = usePage();
    const [mobileOpen, setMobileOpen] = useState(false);
    const [imagesReady, setImagesReady] = useState(true);
    const currentYear = useMemo(() => new Date().getFullYear(), []);
    const resolvedPreloadImages = useMemo(() => {
        const cleaned = preloadImages
            .map((item) => (item ?? '').trim())
            .filter(Boolean);
        return Array.from(new Set(cleaned));
    }, [preloadImages]);
    const preloadKey = useMemo(
        () => resolvedPreloadImages.join('|'),
        [resolvedPreloadImages],
    );

    const origin =
        typeof window !== 'undefined' && window.location?.origin
            ? window.location.origin
            : '';

    const resolvedCanonical = useMemo(() => {
        if (canonicalUrl && canonicalUrl.trim()) {
            return canonicalUrl.trim();
        }
        if (!origin) {
            return url;
        }
        try {
            return new URL(url, origin).toString();
        } catch (_) {
            return `${origin}${url}`;
        }
    }, [canonicalUrl, origin, url]);

    const resolvedOgTitle = ogTitle || title;
    const resolvedOgDescription = ogDescription || description;
    const defaultOgImage = '/images/boardmaster-square.webp';
    const resolvedOgImage = useMemo(() => {
        if (ogImage && ogImage.trim()) {
            return ogImage.trim();
        }
        if (resolvedPreloadImages.length > 0) {
            return resolvedPreloadImages[0];
        }
        return defaultOgImage;
    }, [ogImage, resolvedPreloadImages]);

    const resolvedOgImageAbsolute = useMemo(() => {
        if (!resolvedOgImage) {
            return null;
        }
        if (!origin) {
            return resolvedOgImage;
        }
        try {
            return new URL(resolvedOgImage, origin).toString();
        } catch (_) {
            return resolvedOgImage.startsWith('/')
                ? `${origin}${resolvedOgImage}`
                : resolvedOgImage;
        }
    }, [origin, resolvedOgImage]);

    const resolvedJsonLd = useMemo(() => {
        if (!jsonLd) {
            return null;
        }
        try {
            return JSON.stringify(jsonLd);
        } catch (_) {
            return null;
        }
    }, [jsonLd]);

    useEffect(() => {
        const elements = Array.from(
            document.querySelectorAll('.reveal-on-scroll'),
        );

        if (!('IntersectionObserver' in window) || elements.length === 0) {
            elements.forEach((el) => el.classList.add('is-revealed'));
            return;
        }

        const observer = new IntersectionObserver(
            (entries, currentObserver) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                        currentObserver.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.15, rootMargin: '0px 0px -10% 0px' },
        );

        elements.forEach((el) => observer.observe(el));

        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (resolvedPreloadImages.length === 0) {
            return;
        }

        let isCancelled = false;
        let loadedCount = 0;

        const handleDone = () => {
            loadedCount += 1;
            if (!isCancelled && loadedCount >= resolvedPreloadImages.length) {
                setImagesReady(true);
            }
        };

        resolvedPreloadImages.forEach((src) => {
            const img = new Image();
            img.onload = handleDone;
            img.onerror = handleDone;
            img.src = src;
        });

        return () => {
            isCancelled = true;
        };
    }, [preloadKey, resolvedPreloadImages]);

    const isActive = (href) => {
        if (href === '/') {
            return url === '/';
        }

        return url.startsWith(href);
    };

    return (
        <div
            className="min-h-screen bg-[#eef4f9] text-slate-900"
            style={{ fontFamily: '"Manrope", "Segoe UI", sans-serif' }}
            aria-busy={!imagesReady}
        >
            <Head title={title}>
                {description ? (
                    <meta name="description" content={description} />
                ) : null}
                {noindex ? (
                    <meta name="robots" content="noindex,nofollow" />
                ) : (
                    <meta name="robots" content="index,follow" />
                )}
                {resolvedCanonical ? (
                    <link rel="canonical" href={resolvedCanonical} />
                ) : null}
                {resolvedOgTitle ? (
                    <meta property="og:title" content={resolvedOgTitle} />
                ) : null}
                {resolvedOgDescription ? (
                    <meta
                        property="og:description"
                        content={resolvedOgDescription}
                    />
                ) : null}
                <meta property="og:type" content="website" />
                {resolvedCanonical ? (
                    <meta property="og:url" content={resolvedCanonical} />
                ) : null}
                <meta
                    property="og:site_name"
                    content="BoardMasters Review Center"
                />
                {resolvedOgImageAbsolute ? (
                    <meta property="og:image" content={resolvedOgImageAbsolute} />
                ) : null}
                {resolvedOgImageAbsolute ? (
                    <meta
                        property="twitter:image"
                        content={resolvedOgImageAbsolute}
                    />
                ) : null}
                <meta name="twitter:card" content="summary_large_image" />
                {resolvedOgTitle ? (
                    <meta name="twitter:title" content={resolvedOgTitle} />
                ) : null}
                {resolvedOgDescription ? (
                    <meta
                        name="twitter:description"
                        content={resolvedOgDescription}
                    />
                ) : null}
                {resolvedJsonLd ? (
                    <script
                        type="application/ld+json"
                        dangerouslySetInnerHTML={{ __html: resolvedJsonLd }}
                    />
                ) : null}
                {resolvedPreloadImages.map((src) => (
                    <link
                        key={`preload-${src}`}
                        rel="preload"
                        as="image"
                        href={src}
                    />
                ))}
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link
                    href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700&display=swap"
                    rel="stylesheet"
                />
            </Head>

            <div className="transition-opacity duration-300 opacity-100">
                <header className="fixed top-0 left-0 right-0 z-30 border-b border-slate-200 bg-white">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                        <Link href="/" className="flex items-center gap-3">
                        <img
                            src="/images/boardmaster.webp"
                            alt="Boardmaster Review Center"
                            className="h-11 w-11 rounded-full border border-slate-200 bg-white p-1"
                        />
                            <div className="leading-tight">
                                <p className="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                    BoardMasters
                                </p>
                                <p className="text-sm font-semibold text-slate-900" style={headingFont}>
                                    Review Center
                                </p>
                            </div>
                        </Link>

                        <nav className="hidden items-center gap-7 text-sm font-semibold text-slate-600 md:flex">
                            {navLinks.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={isActive(item.href) ? 'text-slate-900' : 'hover:text-slate-900'}
                                >
                                    {item.label}
                                </Link>
                            ))}
                            <div className="flex items-center gap-2">
                                <a
                                    href="https://apps.apple.com/ph/app/onetake-ph/id6752534980"
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                >
                                    <img
                                        src="/images/store-icons/apple-white-icon.svg"
                                        alt="Apple icon"
                                        className="h-4 w-4 invert"
                                    />
                                    iOS App
                                </a>
                                <a
                                    href="https://play.google.com/store/apps/details?id=ph.boardly"
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                >
                                    <img
                                        src="/images/store-icons/play-store-color-icon.svg"
                                        alt="Play Store icon"
                                        className="h-4 w-4"
                                    />
                                    Android App
                                </a>
                            </div>
                        </nav>

                        <button
                            type="button"
                            onClick={() => setMobileOpen((prev) => !prev)}
                            className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 md:hidden"
                        >
                            {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                        </button>
                    </div>

                    {mobileOpen && (
                        <div className="border-t border-slate-200 bg-white px-4 py-3 md:hidden">
                            <div className="space-y-2">
                                {navLinks.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        onClick={() => setMobileOpen(false)}
                                        className={`block rounded-lg px-3 py-2 text-sm font-semibold ${
                                            isActive(item.href)
                                                ? 'bg-slate-100 text-slate-900'
                                                : 'text-slate-600'
                                        }`}
                                    >
                                        {item.label}
                                    </Link>
                                ))}
                                <div className="mt-3 grid gap-2">
                                    <a
                                        href="https://apps.apple.com/ph/app/onetake-ph/id6752534980"
                                        target="_blank"
                                        rel="noreferrer"
                                        className="flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700"
                                    >
                                        <img
                                            src="/images/store-icons/apple-white-icon.svg"
                                            alt="Apple icon"
                                            className="h-4 w-4 invert"
                                        />
                                        iOS App
                                    </a>
                                    <a
                                        href="https://play.google.com/store/apps/details?id=ph.boardly"
                                        target="_blank"
                                        rel="noreferrer"
                                        className="flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700"
                                    >
                                        <img
                                            src="/images/store-icons/play-store-color-icon.svg"
                                            alt="Play Store icon"
                                            className="h-4 w-4"
                                        />
                                        Android App
                                    </a>
                                </div>
                            </div>
                        </div>
                    )}
                </header>

                <div className="relative overflow-hidden pt-20">
                    <div className="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-cyan-200/55 blur-3xl" />
                    <div className="pointer-events-none absolute -right-24 top-28 h-72 w-72 rounded-full bg-amber-200/40 blur-3xl" />

                    <main>{children}</main>
                </div>

                <footer className="border-t border-slate-200 bg-white">
                    <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 px-4 py-6 text-sm text-slate-600 sm:px-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-slate-900" style={headingFont}>
                                BoardMasters Review Center
                            </p>
                            <p className="mt-1 text-xs text-slate-500">
                                Digital board review focused on Nursing and allied programs.
                            </p>
                        </div>

                        <div className="flex items-center gap-4">
                            <Link href="/vision-mission" className="hover:text-slate-900">
                                Vision & Mission
                            </Link>
                            <Link href="/terms-of-use" className="hover:text-slate-900">
                                Terms
                            </Link>
                            <Link href="/privacy-policy" className="hover:text-slate-900">
                                Privacy
                            </Link>
                            <Link href="/contact" className="hover:text-slate-900">
                                Contact
                            </Link>
                        </div>

                        <p className="text-xs text-slate-500">
                            Copyright {currentYear} BoardMasters Review Center
                        </p>
                    </div>
                </footer>
            </div>
        </div>
    );
}
