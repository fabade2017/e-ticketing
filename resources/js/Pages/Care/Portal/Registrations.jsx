import { useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Registrations({ patient, registrations }) {
    const form = useForm({ category: patient.category || 'military', sponsor_id: '', relationship: '' });

    return (
        <Layout>
            <PageHeader title="My Registrations" subtitle="Submit a registration for care network review" />
            <form onSubmit={(e) => { e.preventDefault(); form.post('/portal/registrations'); }} className="bg-white border rounded-xl p-4 mt-5 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                <input className="border rounded px-3 py-2" placeholder="Category" value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} />
                <input className="border rounded px-3 py-2" placeholder="Sponsor ID (optional)" value={form.data.sponsor_id} onChange={(e) => form.setData('sponsor_id', e.target.value)} />
                <input className="border rounded px-3 py-2" placeholder="Relationship" value={form.data.relationship} onChange={(e) => form.setData('relationship', e.target.value)} />
                <button className="bg-[#0b3d0b] text-white rounded px-3 py-2">Submit registration</button>
            </form>
            <div className="bg-white border rounded-xl mt-5 overflow-hidden">
                {registrations.length ? registrations.map((item) => (
                    <div key={item.id} className="p-4 border-t flex justify-between text-sm">
                        <div><span className="font-mono text-xs">{item.registration_number}</span> · {item.category}</div>
                        <StatusBadge value={item.status} />
                    </div>
                )) : <div className="p-4"><EmptyState message="No registrations submitted yet." /></div>}
            </div>
        </Layout>
    );
}
