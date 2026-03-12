import bodyHtml from '../webflow/privacy-policy-body.html?raw';
import PolicyPage from '../Components/PolicyPage';

export default function PrivacyPolicy() {
    return (
        <PolicyPage
            title="Privacy Policy | Board Masters Review Center"
            heading="Privacy Policy"
            description="Read how Board Masters Review Center collects, uses, and safeguards your data."
            ogTitle="Privacy Policy | Board Masters Review Center"
            ogDescription="Read Board Masters Review Center's privacy policy and learn how we protect your data for board exam review services."
            bodyHtml={bodyHtml}
        />
    );
}
