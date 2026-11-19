import { useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Appointments({ appointments }) {
    const form = useForm({ requested_for: '', reason: '' });

    return (
        <Layout>
            <PageHeader title="My Appointments" subtitle="Requests are not confirmed until Reception confirms" />
            <form onSubmit={(e) => { e.preventDefault(); form.post('/portal/appointments'); }} className="bg-white border rounded-xl p-4 mt-5 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <input type="datetime-local" className="border rounded px-3 py-2" value={form.data.requested_for} onChange={(e) => form.setData('requested_for', e.target.value)} />
                <input className="border rounded px-3 py-2" placeholder="Reason" value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} />
                <button className="bg-[#0b3d0b] text-white rounded px-3 py-2">Request appointment</button>
            </form>
            <div className="bg-white border rounded-xl mt-5 overflow-hidden">
                {appointments.length ? appointments.map((item) => (
                    <div key={item.id} className="p-4 border-t flex justify-between text-sm">
                        <div><span className="font-mono text-xs">{item.appointment_number}</span> · {item.requested_for} · {item.hospital?.name}</div>
                        <StatusBadge value={item.status} />
                    </div>
                )) : <div className="p-4"><EmptyState message="No appointment requests yet." /></div>}
            </div>
        </Layout>
    );
}
