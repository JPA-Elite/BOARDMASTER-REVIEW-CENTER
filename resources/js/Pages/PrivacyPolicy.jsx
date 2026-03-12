import bodyHtml from '../webflow/privacy-policy-body.html?raw';
import OneTakeWebflowPage from '../Components/OneTakeWebflowPage';

export default function PrivacyPolicy() {
    return (
        <OneTakeWebflowPage
            title="Privacy Policy | OneTake CPA Exam Practice App"
            description="Read OneTake's privacy policy for CPALE Reviewer, US CPA Exam Practice App. Learn how we protect your data and respect your privacy."
            ogTitle="Privacy Policy | OneTake CPA Exam Practice App"
            ogDescription="Read OneTake's privacy policy for CPALE Reviewer, US CPA Exam Practice App. Learn how we protect your data and respect your privacy."
            ogType="website"
            twitterTitle="Privacy Policy | OneTake CPA Exam Practice App"
            twitterDescription="Read OneTake's privacy policy for CPALE Reviewer, US CPA Exam Practice App. Learn how we protect your data and respect your privacy."
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
