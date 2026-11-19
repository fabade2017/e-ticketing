import { Link, useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Visits({ visits, filters }) {
    const form = useForm({ patient_id: '', appointment_id: '', registration_id: '', visit_type: 'consultation' });

    return (
        <Layout>
            <PageHeader title="Care Visits" subtitle="Open, document and complete patient visits" />
            <form onSubmit={(e) => { e.preventDefault(); form.post('/care/visits'); }} className="bg-white border rounded-xl p-4 mt-5 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                <input className="border rounded px-3 py-2" placeholder="Patient ID" value={form.data.patient_id} onChange={(e) => form.setData('patient_id', e.target.value)} />
                <input className="border rounded px-3 py-2" placeholder="Appointment ID (optional)" value={form.data.appointment_id} onChange={(e) => form.setData('appointment_id', e.target.value)} />
                <input className="border rounded px-3 py-2" placeholder="Registration ID (optional)" value={form.data.registration_id} onChange={(e) => form.setData('registration_id', e.target.value)} />
                <button className="bg-[#0b3d0b] text-white rounded px-3 py-2" disabled={form.processing}>Open visit</button>
            </form>
            {form.errors.patient_id && <p className="text-red-600 text-xs mt-1">{form.errors.patient_id}</p>}
            <div className="bg-white border rounded-xl mt-5 overflow-hidden">
                <div className="p-4 border-b flex gap-2 text-sm">
                    {['', 'open', 'completed'].map((status) => (
                        <Link key={status || 'all'} href={status ? `/care/visits?status=${status}` : '/care/visits'} className={`px-2 py-1 rounded border ${filters.status === status ? 'bg-[#0b3d0b] text-white' : ''}`}>
                            {status || 'All'}
                        </Link>
                    ))}
                </div>
                {visits.data.length ? (
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left"><tr><th className="p-3">Number</th><th className="p-3">Patient</th><th className="p-3">Type</th><th className="p-3">Status</th><th className="p-3">Opened</th></tr></thead>
                        <tbody>
                            {visits.data.map((visit) => (
                                <tr key={visit.id} className="border-t">
                                    <td className="p-3"><Link href={`/care/visits/${visit.id}`} className="text-[#0b3d0b] underline font-mono text-xs">{visit.visit_number}</Link></td>
                                    <td className="p-3">{visit.patient?.first_name} {visit.patient?.last_name}</td>
                                    <td className="p-3">{visit.visit_type}</td>
                                    <td className="p-3"><StatusBadge value={visit.status} /></td>
                                    <td className="p-3">{visit.checked_in_at}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : <div className="p-4"><EmptyState message="No visits found." /></div>}
            </div>
        </Layout>
    );
}
