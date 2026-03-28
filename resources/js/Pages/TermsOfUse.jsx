import bodyHtml from '../webflow/terms-of-use-body.html?raw';
import PolicyPage from '../Components/PolicyPage';

export default function TermsOfUse() {
    return (
        <PolicyPage
            title="Terms of Use | Boardmasters Review Center"
            heading="Terms of Use"
            description="Review the terms and conditions for using Boardmasters Review Center."
            ogTitle="Terms of Use | Boardmasters Review Center"
            ogDescription="Read the Boardmasters Review Center terms of use for the mobile app and related services."
            bodyHtml={bodyHtml}
        />
    );
}

