import bodyHtml from '../webflow/privacy-policy-body.html?raw';
import OneTakeWebflowPage from '../Components/OneTakeWebflowPage';

export default function PrivacyPolicy() {
    return (
        <OneTakeWebflowPage
            title="Privacy Policy | Board Masters Review Center"
            description="Read Board Masters Review Center's privacy policy and learn how we protect your data for board exam review services."
            ogTitle="Privacy Policy | Board Masters Review Center"
            ogDescription="Read Board Masters Review Center's privacy policy and learn how we protect your data for board exam review services."
            ogType="website"
            twitterTitle="Privacy Policy | Board Masters Review Center"
            twitterDescription="Read Board Masters Review Center's privacy policy and learn how we protect your data for board exam review services."
            twitterCard="summary_large_image"
            pageId="68c80be89141ce6abe633fb9"
            bodyHtml={bodyHtml}
            mainScript={{
                src: 'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/js/webflow.b7eec951.d3ca8c534583b4a1.js',
                integrity:
                    'sha384-WvIyXmitX07J9CEJX4xELe+RH5xrilHUiQY07wAUSHV8/2aen2jVoYbImiUavC38',
            }}
        />
    );
}
