import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Results({ visits, results }) {
    return (
        <Layout>
            <PageHeader title="My Results" subtitle="Only published consultation summaries and lab findings" />
            <div className="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold mb-3">Consultation summaries</h2>
                    {visits.length ? visits.map((visit) => (
                        <div key={visit.id} className="border-t py-3 text-sm">
                            <div className="font-mono text-xs">{visit.visit_number}</div>
                            <div className="mt-1 whitespace-pre-wrap">{visit.published_summary}</div>
                        </div>
                    )) : <EmptyState message="No published summaries yet." />}
                </div>
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold mb-3">Lab findings</h2>
                    {results.length ? results.map((result) => (
                        <div key={result.id} className="border-t py-3 text-sm">
                            <div className="font-medium">{result.lab_order?.test_name}</div>
                            <div className="whitespace-pre-wrap">{result.findings}</div>
                            <div className="flex gap-2 mt-2">
                                {result.images?.map((image) => <span key={image.id} className="text-xs border rounded px-2 py-1">{image.original_name}</span>)}
                            </div>
                        </div>
                    )) : <EmptyState message="No published lab results yet." />}
                </div>
            </div>
        </Layout>
    );
}
