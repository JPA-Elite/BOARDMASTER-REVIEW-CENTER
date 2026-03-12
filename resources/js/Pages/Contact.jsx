import bodyHtml from '../webflow/contact-body.html?raw';
import OneTakeWebflowPage from '../Components/OneTakeWebflowPage';

export default function Contact() {
    return (
        <OneTakeWebflowPage
            title="Contact"
            ogTitle="Contact"
            twitterTitle="Contact"
            pageId="69446da221a37ca243d37d80"
            bodyHtml={bodyHtml}
            mainScript={{
                src: 'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/js/webflow.b7eec951.d3ca8c534583b4a1.js',
                integrity:
                    'sha384-WvIyXmitX07J9CEJX4xELe+RH5xrilHUiQY07wAUSHV8/2aen2jVoYbImiUavC38',
            }}
        />
    );
}
