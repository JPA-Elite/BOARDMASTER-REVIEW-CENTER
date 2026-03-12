import bodyHtml from '../webflow/terms-of-use-body.html?raw';
import OneTakeWebflowPage from '../Components/OneTakeWebflowPage';

export default function TermsOfUse() {
    return (
        <OneTakeWebflowPage
            title="Terms of Use"
            ogTitle="Terms of Use"
            ogImage="https://cdn.prod.website-files.com/63337525695d8ba70ab44222/63565e26a7b90c920bd3e9a4_OpenGraph.png"
            twitterTitle="Terms of Use"
            twitterImage="https://cdn.prod.website-files.com/63337525695d8ba70ab44222/63565e26a7b90c920bd3e9a4_OpenGraph.png"
            pageId="68c80be89141ce6abe633fbb"
            bodyHtml={bodyHtml}
            mainScript={{
                src: 'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/js/webflow.b7eec951.d3ca8c534583b4a1.js',
                integrity:
                    'sha384-WvIyXmitX07J9CEJX4xELe+RH5xrilHUiQY07wAUSHV8/2aen2jVoYbImiUavC38',
            }}
        />
    );
}
