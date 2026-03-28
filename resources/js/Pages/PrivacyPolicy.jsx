import bodyHtml from '../webflow/privacy-policy-body.html?raw';
import PolicyPage from '../Components/PolicyPage';

export default function PrivacyPolicy() {
    return (
        <PolicyPage
            title="Privacy Policy | Boardmasters Review Center"
            heading="Privacy Policy"
            description="Read how Boardmasters Review Center collects, uses, and safeguards your data."
            ogTitle="Privacy Policy | Boardmasters Review Center"
            ogDescription="Read Boardmasters Review Center's privacy policy and learn how we protect your data for board exam review services."
            bodyHtml={bodyHtml}
        />
    );
}

