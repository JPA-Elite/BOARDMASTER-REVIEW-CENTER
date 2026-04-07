import bodyHtml from '../webflow/privacy-policy-body.html?raw';
import PolicyPage from '../Components/PolicyPage';

export default function PrivacyPolicy() {
    return (
        <PolicyPage
            title="Privacy Policy | BoardMasters Review Center"
            heading="Privacy Policy"
            description="Read how BoardMasters Review Center collects, uses, and safeguards your data."
            ogTitle="Privacy Policy | BoardMasters Review Center"
            ogDescription="Read BoardMasters Review Center's privacy policy and learn how we protect your data for board exam review services."
            bodyHtml={bodyHtml}
        />
    );
}

