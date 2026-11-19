import { router, useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Referrals({ referrals, hospitals }) {
    const form = useForm({ patient_id: '', to_hospital_id: '', visit_id: '', reason: '' });

    return (
        <Layout>
            <PageHeader title="Care Referrals" subtitle="Request and accept transfers between hospitals" />
            <form onSubmit={(e) => { e.preventDefault(); form.post('/care/referrals'); }} className="bg-white border rounded-xl p-4 mt-5 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                <input className="border rounded px-3 py-2" placeholder="Patient ID" value={form.data.patient_id} onChange={(e) => form.setData('patient_id', e.target.value)} />
                <select className="border rounded px-3 py-2" value={form.data.to_hospital_id} onChange={(e) => form.setData('to_hospital_id', e.target.value)}>
                    <option value="">Receiving hospital</option>
                    {hospitals.map((hospital) => <option key={hospital.id} value={hospital.id}>{hospital.name}</option>)}
                </select>
                <input className="border rounded px-3 py-2" placeholder="Visit ID (optional)" value={form.data.visit_id} onChange={(e) => form.setData('visit_id', e.target.value)} />
                <input className="border rounded px-3 py-2" placeholder="Reason" value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} />
                <button className="bg-[#0b3d0b] text-white rounded px-3 py-2 md:col-span-4">Send referral</button>
            </form>
            <div className="bg-white border rounded-xl mt-5 overflow-hidden">
                {referrals.data.length ? (
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left"><tr><th className="p-3">Number</th><th className="p-3">Patient</th><th className="p-3">To</th><th className="p-3">Reason</th><th className="p-3">Status</th><th className="p-3">Action</th></tr></thead>
                        <tbody>
                            {referrals.data.map((item) => (
                                <tr key={item.id} className="border-t">
                                    <td className="p-3 font-mono text-xs">{item.referral_number}</td>
                                    <td className="p-3">{item.patient?.first_name} {item.patient?.last_name}</td>
                                    <td className="p-3">{item.to_hospital?.name}</td>
                                    <td className="p-3">{item.reason}</td>
                                    <td className="p-3"><StatusBadge value={item.status} /></td>
                                    <td className="p-3">
                                        {item.status === 'pending' && <button onClick={() => router.post(`/care/referrals/${item.id}/accept`)} className="border rounded px-2 py-1 text-xs">Accept</button>}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : <div className="p-4"><EmptyState message="No referrals found." /></div>}
            </div>
        </Layout>
    );
}
