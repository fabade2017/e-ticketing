import { useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Reports({ reports }) {
    const form = useForm({ month: new Date().getMonth() + 1, year: new Date().getFullYear(), hospital_id: '' });

    return (
        <Layout>
            <PageHeader title="Care Reports" subtitle="Monthly patient, visit, testing and disease summaries" />
            <form onSubmit={(e) => { e.preventDefault(); form.post('/care/reports'); }} className="bg-white border rounded-xl p-4 mt-5 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                <input className="border rounded px-3 py-2" type="number" min="1" max="12" placeholder="Month" value={form.data.month} onChange={(e) => form.setData('month', e.target.value)} />
                <input className="border rounded px-3 py-2" type="number" min="2020" max="2100" placeholder="Year" value={form.data.year} onChange={(e) => form.setData('year', e.target.value)} />
                <input className="border rounded px-3 py-2" placeholder="Hospital ID (blank for network)" value={form.data.hospital_id} onChange={(e) => form.setData('hospital_id', e.target.value)} />
                <button className="bg-[#0b3d0b] text-white rounded px-3 py-2">Generate report</button>
            </form>
            <div className="bg-white border rounded-xl mt-5 overflow-hidden">
                {reports.data.length ? (
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left"><tr><th className="p-3">Month</th><th className="p-3">Scope</th><th className="p-3">Patients</th><th className="p-3">Visits</th><th className="p-3">Tests</th><th className="p-3">Download</th></tr></thead>
                        <tbody>
                            {reports.data.map((report) => (
                                <tr key={report.id} className="border-t">
                                    <td className="p-3">{report.report_month}</td>
                                    <td className="p-3">{report.scope}</td>
                                    <td className="p-3">{report.distinct_patients}</td>
                                    <td className="p-3">{report.completed_visits}</td>
                                    <td className="p-3">{report.tests_performed}</td>
                                    <td className="p-3"><a className="text-[#0b3d0b] underline" href={`/care/reports/${report.id}/csv`}>CSV</a></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : <div className="p-4"><EmptyState message="No reports generated yet." /></div>}
            </div>
        </Layout>
    );
}
