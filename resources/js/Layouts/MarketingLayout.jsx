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
    children,
}) {
    const { url } = usePage();
    const [mobileOpen, setMobileOpen] = useState(false);
    const [isScrolled, setIsScrolled] = useState(false);

    const currentYear = useMemo(() => new Date().getFullYear(), []);

    useEffect(() => {
        const handleScroll = () => {
            setIsScrolled(window.scrollY > 8);
        };

        handleScroll();
        window.addEventListener('scroll', handleScroll, { passive: true });

        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

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
        >
            <Head title={title}>
                {description ? (
                    <meta name="description" content={description} />
                ) : null}
                {ogTitle ? <meta property="og:title" content={ogTitle} /> : null}
                {ogDescription ? (
                    <meta property="og:description" content={ogDescription} />
                ) : null}
                <meta property="og:type" content="website" />
                <meta name="twitter:card" content="summary_large_image" />
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link
                    href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700&display=swap"
                    rel="stylesheet"
                />
            </Head>

            <header
                className={`sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur transition-all duration-300 ${
                    isScrolled ? 'shadow-sm shadow-slate-200/70' : 'shadow-none'
                }`}
            >
                <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                    <Link href="/" className="flex items-center gap-3">
                        <img
                            src="/images/boardmaster.png"
                            alt="Boardmaster Review Center"
                            className="h-11 w-11 rounded-full border border-slate-200 bg-white p-1"
                        />
                        <div className="leading-tight">
                            <p className="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                Board Masters
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
                                className="inline-flex items-center gap-2 rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                            >
                                <img
                                    src="https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/68edddcd5c07668c00987d2a_c1cd2666a020fc416efc02f9e22a61be_play-store-color-icon-elements-brix-templates.svg"
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
                                    className="flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700"
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
                    </div>
                )}
            </header>

            <div className="relative overflow-x-hidden">
                <div className="pointer-events-none absolute -left-20 -top-24 h-80 w-80 rounded-full bg-cyan-200/55 blur-3xl" />
                <div className="pointer-events-none absolute right-0 top-32 h-72 w-72 rounded-full bg-amber-200/40 blur-3xl" />

                <main>{children}</main>
            </div>

            <footer className="border-t border-slate-200 bg-white">
                <div className="mx-auto flex w-full max-w-6xl flex-col gap-5 px-4 py-8 text-sm text-slate-600 sm:px-6 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p className="text-sm font-semibold text-slate-900" style={headingFont}>
                            Board Masters Review Center
                        </p>
                        <p className="mt-1 text-xs text-slate-500">
                            Digital board review focused on Nursing and allied programs.
                        </p>
                    </div>

                    <div className="flex items-center gap-4">
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
                        Copyright {currentYear} Board Masters Review Center
                    </p>
                </div>
            </footer>
        </div>
    );
}
