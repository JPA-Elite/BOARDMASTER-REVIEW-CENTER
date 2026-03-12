import homeBody from '../webflow/home-body.html?raw';
import OneTakeWebflowPage from '../Components/OneTakeWebflowPage';

export default function Welcome() {
    return (
        <OneTakeWebflowPage
            title="Board Masters Review Center | Nursing and Board Exam Reviewer"
            description="10,000+ board exam practice questions with answer rationales, including Nursing and other major Philippine licensure exams."
            ogTitle="Board Masters Review Center | Nursing and Board Exam Reviewer"
            ogDescription="10,000+ board exam practice questions with answer rationales, including Nursing and other major Philippine licensure exams."
            ogImage="https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/6912d21b832905c56a978aea_Untitled%20design%20(2).png"
            ogType="website"
            twitterTitle="Board Masters Review Center | Nursing and Board Exam Reviewer"
            twitterDescription="10,000+ board exam practice questions with answer rationales, including Nursing and other major Philippine licensure exams."
            twitterCard="summary_large_image"
            pageId="68c80be89141ce6abe633fa4"
            bodyHtml={homeBody}
            inlinePageStyle='@media (min-width:992px) {html.w-mod-js:not(.w-mod-ix) [data-w-id="ab387576-97dd-bac4-9d3f-606780dc1495"] {opacity:0;}html.w-mod-js:not(.w-mod-ix) [data-w-id="e01f2b66-f38a-8f0b-11f3-00a46bbbdb11"] {opacity:0;}}'
            mainScript={{
                src: 'https://cdn.prod.website-files.com/68c80be79141ce6abe633f58/js/webflow.8f467c73.dc8900b69d2b2e1a.js',
                integrity:
                    'sha384-yGv/1zN/9x5lnFKMstTrQmjNCkk6iQpKcvx0QUXy3tReiR/XguL+nQ68x/VDXS0r',
            }}
        />
    );
}
