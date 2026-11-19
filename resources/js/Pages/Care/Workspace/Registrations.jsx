import { Link, router } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Registrations({ registrations, filters }) {
    const approve = (id) => router.post(`/care/registrations/${id}/review`, {
        decision: 'approved',
        identity_verified: true,
        evidence_verified: true,
        sponsor_approved: true,
    });

    const reject = (id) => router.post(`/care/registrations/${id}/review`, {
        decision: 'rejected',
        identity_verified: true,
        evidence_verified: true,
        sponsor_approved: true,
    });

    return (
        <Layout>
            <PageHeader title="Care Registrations" subtitle="Review identity, evidence and sponsorship before approval" />
            <div className="bg-white border rounded-xl mt-5 overflow-hidden">
                <div className="p-4 border-b flex gap-2 text-sm">
                    {['', 'pending', 'approved', 'rejected'].map((status) => (
                        <Link key={status || 'all'} href={status ? `/care/registrations?status=${status}` : '/care/registrations'} className={`px-2 py-1 rounded border ${filters.status === status ? 'bg-[#0b3d0b] text-white' : ''}`}>
                            {status || 'All'}
                        </Link>
                    ))}
                </div>
                {registrations.data.length ? (
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left"><tr><th className="p-3">Number</th><th className="p-3">Patient</th><th className="p-3">Category</th><th className="p-3">Fee</th><th className="p-3">Status</th><th className="p-3">Action</th></tr></thead>
                        <tbody>
                            {registrations.data.map((item) => (
                                <tr key={item.id} className="border-t">
                                    <td className="p-3 font-mono text-xs">{item.registration_number}</td>
                                    <td className="p-3">{item.patient?.first_name} {item.patient?.last_name}</td>
                                    <td className="p-3">{item.category}</td>
                                    <td className="p-3">{item.payable_amount}</td>
                                    <td className="p-3"><StatusBadge value={item.status} /></td>
                                    <td className="p-3 flex gap-1">
                                        <button
                                            className="bg-[#0b3d0b] text-white px-2 py-1 rounded text-xs"
                                            onClick={() => approve(item.id)}
                                        >Approve</button>
                                        <button
                                            className="border px-2 py-1 rounded text-xs"
                                            onClick={() => reject(item.id)}
                                        >Reject</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : <div className="p-4"><EmptyState message="No registrations found." /></div>}
            </div>
        </Layout>
    );
}
