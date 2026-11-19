import { Link, useForm } from '@inertiajs/react';
import Layout from '../../Components/Layout';
import PageHeader from '../../Components/ui/PageHeader';
import StatusBadge from '../../Components/ui/StatusBadge';
import EmptyState from '../../Components/ui/EmptyState';

export default function Show({ workOrder, technicians, items }) {
    const assign = useForm({ technician_id: '' });
    const parts = useForm({ item_id: '', quantity: '', batch_id: '', purpose: '', idempotency_key: '' });
    const complete = useForm({});

    return (
        <Layout>
            <PageHeader
                title={workOrder.work_order_number}
                subtitle={workOrder.title}
                actions={<><Link href="/maintenance" className="border rounded px-3 py-2 text-sm">Back</Link><button disabled={complete.processing} onClick={() => complete.post(`/maintenance/${workOrder.id}/complete`)} className="bg-[#0b3d0b] text-white rounded px-3 py-2 text-sm">Complete</button></>}
            />
            <div className="grid md:grid-cols-2 gap-4 mt-5">
                <div className="bg-white border rounded-xl p-5">
                    <div className="flex justify-between items-center"><h2 className="font-semibold">Details</h2><StatusBadge value={workOrder.status} /></div>
                    <dl className="grid grid-cols-2 gap-3 text-sm mt-4">
                        <div><dt className="text-slate-400 text-xs">Equipment</dt><dd>{workOrder.equipment?.name || '—'}</dd></div>
                        <div><dt className="text-slate-400 text-xs">Asset</dt><dd>{workOrder.equipment?.asset_id || '—'}</dd></div>
                        <div><dt className="text-slate-400 text-xs">Priority</dt><dd>{workOrder.priority}</dd></div>
                        <div><dt className="text-slate-400 text-xs">Due</dt><dd>{workOrder.due_at || '—'}</dd></div>
                        <div className="col-span-2"><dt className="text-slate-400 text-xs">Description</dt><dd>{workOrder.description || '—'}</dd></div>
                    </dl>
                    <form className="mt-5 border-t pt-4" onSubmit={(e) => { e.preventDefault(); assign.post(`/maintenance/${workOrder.id}/assign`); }}>
                        <h3 className="text-sm font-semibold">Assign technician</h3>
                        <div className="flex gap-2 mt-2">
                            <select className="flex-1 border rounded px-2 py-2 text-sm" value={assign.data.technician_id} onChange={(e) => assign.setData('technician_id', e.target.value)}><option value="">Select technician</option>{technicians.map((technician) => <option key={technician.id} value={technician.id}>{technician.name} · {technician.service_number}</option>)}</select>
                            <button disabled={assign.processing || !assign.data.technician_id} className="border rounded px-3 py-2 text-sm">Assign</button>
                        </div>
                    </form>
                </div>
                <div className="bg-white border rounded-xl p-5">
                    <h2 className="font-semibold">Issue parts</h2>
                    <form className="mt-3 space-y-2" onSubmit={(e) => { e.preventDefault(); parts.post(`/maintenance/${workOrder.id}/parts`); }}>
                        <select className="w-full border rounded px-2 py-2 text-sm" value={parts.data.item_id} onChange={(e) => parts.setData('item_id', e.target.value)}><option value="">Select item</option>{items.map((item) => <option key={item.id} value={item.id}>{item.name} ({item.sku})</option>)}</select>
                        <input className="w-full border rounded px-2 py-2 text-sm" type="number" step="0.001" placeholder="Quantity" value={parts.data.quantity} onChange={(e) => parts.setData('quantity', e.target.value)} />
                        <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Purpose" value={parts.data.purpose} onChange={(e) => parts.setData('purpose', e.target.value)} />
                        <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Idempotency key" value={parts.data.idempotency_key} onChange={(e) => parts.setData('idempotency_key', e.target.value)} />
                        {Object.values(parts.errors).map((error, index) => <p key={index} className="text-red-600 text-xs">{error}</p>)}
                        <button disabled={parts.processing} className="w-full border rounded py-2 text-sm">Issue parts</button>
                    </form>
                    <h3 className="font-semibold mt-6">Parts used</h3>
                    {workOrder.parts.length ? <ul className="divide-y text-sm mt-2">{workOrder.parts.map((part) => <li key={part.id} className="py-2 flex justify-between"><span>Issue #{part.supply_issue_id}</span><span>{part.quantity}</span></li>)}</ul> : <div className="mt-2"><EmptyState message="No parts issued." /></div>}
                </div>
            </div>
            <div className="bg-white border rounded-xl p-5 mt-4">
                <h2 className="font-semibold">Service log</h2>
                {workOrder.serviceLogs.length ? <ul className="divide-y mt-2 text-sm">{workOrder.serviceLogs.map((log) => <li key={log.id} className="py-2"><span className="font-mono text-xs text-slate-400">{log.created_at}</span> · {log.action} · {log.note}</li>)}</ul> : <div className="mt-2"><EmptyState message="No service log entries." /></div>}
            </div>
        </Layout>
    );
}
