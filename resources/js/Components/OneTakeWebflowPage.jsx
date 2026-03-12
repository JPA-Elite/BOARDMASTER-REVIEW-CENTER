import { Head } from '@inertiajs/react';
import { useEffect } from 'react';

const SITE_ID = '68c80be79141ce6abe633f58';
const DOMAIN = 'www.onetake.ph';
const GENERATOR = 'Webflow';

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

const WEBFLOW_BASE_SCRIPTS = [
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
];

const DEFAULT_FONT_SMOOTHING_STYLE =
    '* {-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;}';

const loadScript = ({ id, src, integrity, crossOrigin }) =>
    new Promise((resolve, reject) => {
        const existing = document.getElementById(id);

        if (existing) {
            existing.dataset.loaded = 'true';
            resolve();
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

export default function OneTakeWebflowPage({
    title,
    description,
    ogTitle,
    ogDescription,
    ogImage,
    ogType,
    twitterTitle,
    twitterDescription,
    twitterImage,
    twitterCard,
    pageId,
    bodyHtml,
    inlinePageStyle = '',
    fontSmoothingStyle = DEFAULT_FONT_SMOOTHING_STYLE,
    mainScript,
}) {
    useEffect(() => {
        const htmlElement = document.documentElement;
        const previousDomain = htmlElement.getAttribute('data-wf-domain');
        const previousPage = htmlElement.getAttribute('data-wf-page');
        const previousSite = htmlElement.getAttribute('data-wf-site');
        const hadWModJs = htmlElement.classList.contains('w-mod-js');
        const hadWModTouch = htmlElement.classList.contains('w-mod-touch');

        htmlElement.setAttribute('data-wf-domain', DOMAIN);
        htmlElement.setAttribute('data-wf-page', pageId);
        htmlElement.setAttribute('data-wf-site', SITE_ID);
        htmlElement.classList.add('w-mod-js');

        if (
            'ontouchstart' in window ||
            (window.DocumentTouch && document instanceof window.DocumentTouch)
        ) {
            htmlElement.classList.add('w-mod-touch');
        }

        const scripts = [
            WEBFONT_SCRIPT,
            ...WEBFLOW_BASE_SCRIPTS,
            {
                id: `onetake-webflow-main-${pageId}`,
                src: mainScript.src,
                integrity: mainScript.integrity,
                crossOrigin: 'anonymous',
            },
        ];

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

                for (const script of scripts.slice(1)) {
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
    }, [pageId, mainScript.src, mainScript.integrity]);

    return (
        <>
            <Head title={title}>
                {description ? (
                    <meta name="description" content={description} />
                ) : null}
                {ogTitle ? <meta property="og:title" content={ogTitle} /> : null}
                {ogDescription ? (
                    <meta property="og:description" content={ogDescription} />
                ) : null}
                {ogImage ? <meta property="og:image" content={ogImage} /> : null}
                {ogType ? <meta property="og:type" content={ogType} /> : null}
                {twitterTitle ? (
                    <meta property="twitter:title" content={twitterTitle} />
                ) : null}
                {twitterDescription ? (
                    <meta
                        property="twitter:description"
                        content={twitterDescription}
                    />
                ) : null}
                {twitterImage ? (
                    <meta property="twitter:image" content={twitterImage} />
                ) : null}
                {twitterCard ? (
                    <meta name="twitter:card" content={twitterCard} />
                ) : null}
                <meta name="generator" content={GENERATOR} />
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
                {inlinePageStyle ? <style>{inlinePageStyle}</style> : null}
                {fontSmoothingStyle ? <style>{fontSmoothingStyle}</style> : null}
            </Head>
            <div dangerouslySetInnerHTML={{ __html: bodyHtml }} />
        </>
    );
}
