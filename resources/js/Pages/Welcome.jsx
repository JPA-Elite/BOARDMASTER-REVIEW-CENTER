import { Head } from '@inertiajs/react';
import { useEffect } from 'react';
import onetakeBody from '../onetake-body.html?raw';

const WEBFONT_FAMILIES = [
    'Montserrat:100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic',
    'Open Sans:300,300italic,400,400italic,600,600italic,700,700italic,800,800italic',
    'Archivo:300,400,500,600,700',
    'Inter:300,400,500,600,700',
];

const WEBFONT_SCRIPT = {
    id: 'onetake-webfont-loader',
    src: 'https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js',
};

const WEBFLOW_SCRIPTS = [
    {
        id: 'onetake-jquery',
        src: 'https://d3e54v103j8qbb.cloudfront.net/js/jquery-3.5.1.min.dc5e7f18c8.js?site=68c80be79141ce6abe633f58',
        integrity:
            'sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=',
        crossOrigin: 'anonymous',
    },
    {
        id: 'onetake-webflow-chunk-1',
        src: 'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/js/webflow.schunk.59c6248219f37ae8.js',
        integrity:
            'sha384-SiwpjJNf9ZitucDlWa0ARYbaOcz0PUxo+U1JokcCeias7QOSgb93xI+PogbjFilN',
        crossOrigin: 'anonymous',
    },
    {
        id: 'onetake-webflow-chunk-2',
        src: 'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/js/webflow.schunk.3049a587d79d27a3.js',
        integrity:
            'sha384-GU3qYUDrhyQFFuGaBQGvUqRgmikdxIE/XB0ZO4l5AyPWlEYWPxh1/+1HSj8p3j+t',
        crossOrigin: 'anonymous',
    },
    {
        id: 'onetake-webflow-main',
        src: 'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/js/webflow.8f467c73.dc8900b69d2b2e1a.js',
        integrity:
            'sha384-yGv/1zN/9x5lnFKMstTrQmjNCkk6iQpKcvx0QUXy3tReiR/XguL+nQ68x/VDXS0r',
        crossOrigin: 'anonymous',
    },
];

const INLINE_PAGE_STYLE =
    '@media (min-width:992px) {html.w-mod-js:not(.w-mod-ix) [data-w-id="ab387576-97dd-bac4-9d3f-606780dc1495"] {opacity:0;}html.w-mod-js:not(.w-mod-ix) [data-w-id="e01f2b66-f38a-8f0b-11f3-00a46bbbdb11"] {opacity:0;}}';

const FONT_SMOOTHING_STYLE =
    '* {-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;}';

const loadScript = ({ id, src, integrity, crossOrigin }) =>
    new Promise((resolve, reject) => {
        const existing = document.getElementById(id);

        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve();
                return;
            }

            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener(
                'error',
                () => reject(new Error(`Failed to load ${src}`)),
                { once: true },
            );
            return;
        }

        const script = document.createElement('script');

        script.id = id;
        script.src = src;
        script.type = 'text/javascript';
        script.async = false;

        if (integrity) {
            script.integrity = integrity;
        }

        if (crossOrigin) {
            script.crossOrigin = crossOrigin;
        }

        script.addEventListener(
            'load',
            () => {
                script.dataset.loaded = 'true';
                resolve();
            },
            { once: true },
        );

        script.addEventListener(
            'error',
            () => reject(new Error(`Failed to load ${src}`)),
            { once: true },
        );

        document.body.appendChild(script);
    });

export default function Welcome() {
    useEffect(() => {
        const htmlElement = document.documentElement;
        const previousDomain = htmlElement.getAttribute('data-wf-domain');
        const previousPage = htmlElement.getAttribute('data-wf-page');
        const previousSite = htmlElement.getAttribute('data-wf-site');
        const hadWModJs = htmlElement.classList.contains('w-mod-js');
        const hadWModTouch = htmlElement.classList.contains('w-mod-touch');

        htmlElement.setAttribute('data-wf-domain', 'www.onetake.ph');
        htmlElement.setAttribute('data-wf-page', '68c80be89141ce6abe633fa4');
        htmlElement.setAttribute('data-wf-site', '68c80be79141ce6abe633f58');
        htmlElement.classList.add('w-mod-js');

        if (
            'ontouchstart' in window ||
            (window.DocumentTouch && document instanceof window.DocumentTouch)
        ) {
            htmlElement.classList.add('w-mod-touch');
        }

        let isCancelled = false;

        const loadAssets = async () => {
            try {
                await loadScript(WEBFONT_SCRIPT);

                if (!isCancelled && window.WebFont) {
                    window.WebFont.load({
                        google: {
                            families: WEBFONT_FAMILIES,
                        },
                    });
                }

                for (const script of WEBFLOW_SCRIPTS) {
                    await loadScript(script);
                }
            } catch (error) {
                console.error('OneTake asset loading failed.', error);
            }
        };

        loadAssets();

        return () => {
            isCancelled = true;

            if (previousDomain === null) {
                htmlElement.removeAttribute('data-wf-domain');
            } else {
                htmlElement.setAttribute('data-wf-domain', previousDomain);
            }

            if (previousPage === null) {
                htmlElement.removeAttribute('data-wf-page');
            } else {
                htmlElement.setAttribute('data-wf-page', previousPage);
            }

            if (previousSite === null) {
                htmlElement.removeAttribute('data-wf-site');
            } else {
                htmlElement.setAttribute('data-wf-site', previousSite);
            }

            if (!hadWModJs) {
                htmlElement.classList.remove('w-mod-js');
            }

            if (!hadWModTouch) {
                htmlElement.classList.remove('w-mod-touch');
            }
        };
    }, []);

    return (
        <>
            <Head title="The Best Philippine CPALE Reviewer App | OneTake">
                <meta
                    name="description"
                    content="10,000+ Philippine CPA board exam questions. Unlimited practice tests and mock board exams. DOWNLOAD APP FREE (iOS & Android)"
                />
                <meta
                    property="og:title"
                    content="The Best Philippine CPALE Reviewer App | OneTake"
                />
                <meta
                    property="og:description"
                    content="10,000+ Philippine CPA board exam questions. Unlimited practice tests and mock board exams. DOWNLOAD APP FREE (iOS & Android)"
                />
                <meta
                    property="og:image"
                    content="https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/6912d21b832905c56a978aea_Untitled%20design%20(2).png"
                />
                <meta
                    property="twitter:title"
                    content="The Best Philippine CPALE Reviewer App | OneTake"
                />
                <meta
                    property="twitter:description"
                    content="10,000+ Philippine CPA board exam questions. Unlimited practice tests and mock board exams. DOWNLOAD APP FREE (iOS & Android)"
                />
                <meta property="og:type" content="website" />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="generator" content="Webflow" />
                <link rel="stylesheet" href="/onetake.webflow.css" />
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link
                    rel="preconnect"
                    href="https://fonts.gstatic.com"
                    crossOrigin="anonymous"
                />
                <link
                    rel="shortcut icon"
                    type="image/x-icon"
                    href="https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/69363aabaddb13811d011553_3.png"
                />
                <link
                    rel="apple-touch-icon"
                    href="https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/69363a91695f7f1080826a53_3.png"
                />
                <style>{INLINE_PAGE_STYLE}</style>
                <style>{FONT_SMOOTHING_STYLE}</style>
            </Head>
            <div dangerouslySetInnerHTML={{ __html: onetakeBody }} />
        </>
    );
}
