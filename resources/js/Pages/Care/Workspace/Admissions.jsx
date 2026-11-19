import { router, useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Admissions({ admissions, beds }) {
    const form = useForm({ patient_id: '', bed_id: '', diagnosis: '', notes: '' });

    return (
        <Layout>
            <PageHeader title="Care Admissions" subtitle="Bed occupancy and checkout" />
            <form onSubmit={(e) => { e.preventDefault(); form.post('/care/admissions'); }} className="bg-white border rounded-xl p-4 mt-5 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                <input className="border rounded px-3 py-2" placeholder="Patient ID" value={form.data.patient_id} onChange={(e) => form.setData('patient_id', e.target.value)} />
                <select className="border rounded px-3 py-2" value={form.data.bed_id} onChange={(e) => form.setData('bed_id', e.target.value)}>
                    <option value="">Select available bed</option>
                    {beds.map((bed) => <option key={bed.id} value={bed.id}>{bed.ward?.name || 'Ward'} · {bed.code}</option>)}
                </select>
                <input className="border rounded px-3 py-2" placeholder="Diagnosis" value={form.data.diagnosis} onChange={(e) => form.setData('diagnosis', e.target.value)} />
                <button className="bg-[#0b3d0b] text-white rounded px-3 py-2">Admit patient</button>
            </form>
            <div className="bg-white border rounded-xl mt-5 overflow-hidden">
                {admissions.data.length ? (
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left"><tr><th className="p-3">Number</th><th className="p-3">Patient</th><th className="p-3">Bed</th><th className="p-3">Admitted</th><th className="p-3">Status</th><th className="p-3">Action</th></tr></thead>
                        <tbody>
                            {admissions.data.map((item) => (
                                <tr key={item.id} className="border-t">
                                    <td className="p-3 font-mono text-xs">{item.admission_number}</td>
                                    <td className="p-3">{item.patient?.first_name} {item.patient?.last_name}</td>
                                    <td className="p-3">{item.bed?.code}</td>
                                    <td className="p-3">{item.admitted_at}</td>
                                    <td className="p-3"><StatusBadge value={item.status} /></td>
                                    <td className="p-3">
                                        {item.status === 'admitted' && <button onClick={() => router.post(`/care/admissions/${item.id}/discharge`)} className="border rounded px-2 py-1 text-xs">Check out</button>}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : <div className="p-4"><EmptyState message="No admissions found." /></div>}
            </div>
        </Layout>
    );
}
