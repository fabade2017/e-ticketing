import { Link, useForm } from '@inertiajs/react';
import Layout from '../../Components/Layout';
import PageHeader from '../../Components/ui/PageHeader';
import StatusBadge from '../../Components/ui/StatusBadge';
import EmptyState from '../../Components/ui/EmptyState';

export default function Approvals({ approvals, filters }) {
    const approve = useForm({});
    const reject = useForm({});

    return (
        <Layout>
            <PageHeader title="Stock approvals" subtitle="Review pending stock movements and reconciliations" actions={<Link href="/supply" className="border rounded px-3 py-2 text-sm">Back to supply</Link>} />
            <div className="bg-white border rounded-xl mt-5 p-4 flex gap-3 text-sm">
                {['pending', 'approved', 'rejected'].map((status) => (
                    <Link key={status} href={`/supply/approvals?status=${status}`} className={`px-3 py-1 rounded border ${filters.status === status ? 'bg-[#0b3d0b] text-white' : ''}`}>{status}</Link>
                ))}
            </div>
            <div className="bg-white border rounded-xl mt-4 overflow-hidden">
                {approvals.data.length ? (
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left"><tr><th className="p-3">Request</th><th className="p-3">Subject</th><th className="p-3">Requested by</th><th className="p-3">Status</th><th className="p-3">Action</th></tr></thead>
                        <tbody>
                            {approvals.data.map((approval) => (
                                <tr key={approval.id} className="border-t">
                                    <td className="p-3">{approval.type}<div className="text-xs text-slate-400">{approval.created_at}</div></td>
                                    <td className="p-3">#{approval.subject_id}</td>
                                    <td className="p-3">{approval.requestedBy?.name || '—'}</td>
                                    <td className="p-3"><StatusBadge value={approval.status} /></td>
                                    <td className="p-3 flex gap-2">
                                        {approval.status === 'pending' && (
                                            <>
                                                <button disabled={approve.processing} onClick={() => approve.post(`/supply/approvals/${approval.id}/approve`)} className="bg-[#0b3d0b] text-white px-2 py-1 rounded text-xs">Approve</button>
                                                <button disabled={reject.processing} onClick={() => reject.post(`/supply/approvals/${approval.id}/reject`)} className="border px-2 py-1 rounded text-xs">Reject</button>
                                            </>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : <div className="p-4"><EmptyState message="No approval requests found." /></div>}
            </div>
        </Layout>
    );
}
