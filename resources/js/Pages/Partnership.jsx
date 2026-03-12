import bodyHtml from '../webflow/partnership-body.html?raw';
import OneTakeWebflowPage from '../Components/OneTakeWebflowPage';

export default function Partnership() {
    return (
        <OneTakeWebflowPage
            title="Partnership"
            ogTitle="Partnership"
            twitterTitle="Partnership"
            pageId="69448b6c3a43702b6608c578"
            bodyHtml={bodyHtml}
            inlinePageStyle='@media (min-width:992px) {html.w-mod-js:not(.w-mod-ix) [data-w-id="dce03e3f-1ce7-8419-c611-380ac29c4ff5"] {opacity:0;}}'
            mainScript={{
                src: 'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/js/webflow.b7eec951.d3ca8c534583b4a1.js',
                integrity:
                    'sha384-WvIyXmitX07J9CEJX4xELe+RH5xrilHUiQY07wAUSHV8/2aen2jVoYbImiUavC38',
            }}
        />
    );
}
