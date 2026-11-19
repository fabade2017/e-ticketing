import { Link, useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Lab({ orders, filters }) {
    const draft = useForm({ findings: '' });
    const publish = useForm({ findings: '' });
    const image = useForm({ image: null });

    return (
        <Layout>
            <PageHeader title="Care Laboratory" subtitle="Orders, draft results, published findings and images" />
            <div className="bg-white border rounded-xl mt-5 overflow-hidden">
                <div className="p-4 border-b flex gap-2 text-sm">
                    {['', 'ordered', 'collected', 'in_progress', 'completed'].map((status) => (
                        <Link key={status || 'all'} href={status ? `/care/lab?status=${status}` : '/care/lab'} className={`px-2 py-1 rounded border ${filters.status === status ? 'bg-[#0b3d0b] text-white' : ''}`}>
                            {status || 'All'}
                        </Link>
                    ))}
                </div>
                {orders.data.length ? (
                    <div className="divide-y">
                        {orders.data.map((order) => (
                            <div key={order.id} className="p-4 text-sm">
                                <div className="flex justify-between">
                                    <div>
                                        <span className="font-mono text-xs">{order.order_number}</span>
                                        <div>{order.test_name} · {order.patient?.first_name} {order.patient?.last_name}</div>
                                    </div>
                                    <StatusBadge value={order.status} />
                                </div>
                                {order.status !== 'completed' && (
                                    <form onSubmit={(e) => { e.preventDefault(); draft.post(`/care/lab-orders/${order.id}/results`); }} className="mt-2 flex gap-2">
                                        <input className="border rounded px-2 py-1 flex-1" placeholder="Initial findings (optional)" value={draft.data.findings} onChange={(e) => draft.setData('findings', e.target.value)} />
                                        <button className="border rounded px-3 py-1">Create draft</button>
                                    </form>
                                )}
                                {order.result && (
                                    <div className="mt-3 bg-slate-50 rounded p-3 space-y-2">
                                        <div className="text-xs text-slate-500">Result status: {order.result.status}</div>
                                        <form onSubmit={(e) => { e.preventDefault(); publish.post(`/care/lab-results/${order.result.id}/publish`); }} className="flex gap-2">
                                            <input className="border rounded px-2 py-1 flex-1" placeholder="Findings" value={publish.data.findings} onChange={(e) => publish.setData('findings', e.target.value)} />
                                            <button className="bg-[#0b3d0b] text-white rounded px-3 py-1">Publish</button>
                                        </form>
                                        <form onSubmit={(e) => { e.preventDefault(); image.post(`/care/lab-results/${order.result.id}/images`); }} className="flex gap-2">
                                            <input type="file" accept="image/jpeg,image/png" className="border rounded px-2 py-1 flex-1" onChange={(e) => image.setData('image', e.target.files[0])} />
                                            <button className="border rounded px-3 py-1">Upload image</button>
                                        </form>
                                        <div className="flex gap-2 text-xs">
                                            {order.result.images?.map((item) => <span key={item.id} className="border rounded px-2 py-1">{item.original_name}</span>)}
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                ) : <div className="p-4"><EmptyState message="No lab orders found." /></div>}
            </div>
        </Layout>
    );
}
