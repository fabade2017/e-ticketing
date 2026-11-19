import { useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function DutyCertificates({ patients, statuses }) {
    const issue = useForm({ patient_id: '', visit_id: '', status: 'FIT', statement: '', review_date: '' });

    return (
        <Layout>
            <PageHeader title="Duty Certificates" subtitle="Only doctors certify duty status; Records Officers maintain administrative fields" />
            <form onSubmit={(e) => { e.preventDefault(); issue.post('/care/duty-certificates'); }} className="bg-white border rounded-xl p-4 mt-5 grid grid-cols-1 md:grid-cols-5 gap-3 text-sm">
                <input className="border rounded px-3 py-2" placeholder="Patient ID" value={issue.data.patient_id} onChange={(e) => issue.setData('patient_id', e.target.value)} />
                <input className="border rounded px-3 py-2" placeholder="Visit ID (optional)" value={issue.data.visit_id} onChange={(e) => issue.setData('visit_id', e.target.value)} />
                <select className="border rounded px-3 py-2" value={issue.data.status} onChange={(e) => issue.setData('status', e.target.value)}>
                    <option value="FIT">FIT</option>
                    <option value="RESTRICTED">RESTRICTED</option>
                    <option value="UNFIT">UNFIT</option>
                </select>
                <input className="border rounded px-3 py-2" placeholder="Review date" type="date" value={issue.data.review_date} onChange={(e) => issue.setData('review_date', e.target.value)} />
                <button className="bg-[#0b3d0b] text-white rounded px-3 py-2">Issue certificate</button>
                <input className="border rounded px-3 py-2 md:col-span-5" placeholder="Duty statement" value={issue.data.statement} onChange={(e) => issue.setData('statement', e.target.value)} />
            </form>
            <div className="bg-white border rounded-xl mt-5 overflow-hidden">
                {patients.data.length ? (
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left"><tr><th className="p-3">Patient</th><th className="p-3">Network number</th><th className="p-3">Status</th><th className="p-3">Administrative note</th></tr></thead>
                        <tbody>
                            {patients.data.map((patient) => (
                                <tr key={patient.id} className="border-t">
                                    <td className="p-3">{patient.first_name} {patient.last_name}</td>
                                    <td className="p-3 font-mono text-xs">{patient.network_number}</td>
                                    <td className="p-3"><StatusBadge value={statuses[patient.id]} /></td>
                                    <td className="p-3">
                                        {patient.duty_certificates?.[0] ? (
                                            <AdministrativeForm certificate={patient.duty_certificates[0]} />
                                        ) : '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : <div className="p-4"><EmptyState message="No active patients found." /></div>}
            </div>
        </Layout>
    );
}

function AdministrativeForm({ certificate }) {
    const form = useForm({ administrative_note: certificate.administrative_note || '' });

    return (
        <form onSubmit={(e) => { e.preventDefault(); form.post(`/care/duty-certificates/${certificate.id}/administrative`); }} className="flex gap-2">
            <input className="border rounded px-2 py-1 text-xs" value={form.data.administrative_note} onChange={(e) => form.setData('administrative_note', e.target.value)} />
            <button className="border rounded px-2 py-1 text-xs">Save</button>
        </form>
    );
}
