import { Link, useForm } from '@inertiajs/react';
import Layout from '../../Components/Layout';
import PageHeader from '../../Components/ui/PageHeader';
import StatCard from '../../Components/ui/StatCard';
import StatusBadge from '../../Components/ui/StatusBadge';
import EmptyState from '../../Components/ui/EmptyState';

export default function Index({ balances, items, approvals, insights, filters, canSeeAll, isAll, currentHospital }) {
    const receive = useForm({ item_id: '', quantity: '', supplier: '', document_no: '', batch_code: '', expires_on: '', idempotency_key: '' });
    const issue = useForm({ item_id: '', quantity: '', recipient_type: 'unit', recipient_id: '', purpose: '', idempotency_key: '' });
    const reconcile = useForm({ item_id: '', expected_quantity: '', counted_quantity: '', notes: '', idempotency_key: '' });
    const newItem = useForm({ name: '', sku: '', category: '', unit: 'pcs', description: '' });

    return (
        <Layout>
            <PageHeader
                title="Supply"
                subtitle={currentHospital ? `${currentHospital.code} · ${currentHospital.name}` : 'Network-wide stock position'}
                actions={
                    <>
                        {canSeeAll && <Link href={isAll ? '/supply?scope=scoped' : '/supply?scope=all'} className="border rounded px-3 py-2 text-sm">{isAll ? 'My Hospital' : 'All Hospitals'}</Link>}
                        <Link href="/supply/approvals" className="border rounded px-3 py-2 text-sm">Approvals</Link>
                    </>
                }
            />

            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5">
                {(insights || []).map((metric) => <StatCard key={metric.label} label={metric.label} value={metric.value} href={metric.url} />)}
            </div>

            <div className="grid lg:grid-cols-3 gap-4 mt-6">
                <form className="bg-white border rounded-xl p-4" onSubmit={(e) => { e.preventDefault(); receive.post('/supply/receive'); }}>
                    <h2 className="font-semibold">Receive goods</h2>
                    <div className="mt-3 space-y-2">
                        <select className="w-full border rounded px-2 py-2 text-sm" value={receive.data.item_id} onChange={(e) => receive.setData('item_id', e.target.value)}><option value="">Select item</option>{items.map((item) => <option key={item.id} value={item.id}>{item.name} ({item.sku})</option>)}</select>
                        <input className="w-full border rounded px-2 py-2 text-sm" type="number" step="0.001" placeholder="Quantity" value={receive.data.quantity} onChange={(e) => receive.setData('quantity', e.target.value)} />
                        <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Supplier" value={receive.data.supplier} onChange={(e) => receive.setData('supplier', e.target.value)} />
                        <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Document no." value={receive.data.document_no} onChange={(e) => receive.setData('document_no', e.target.value)} />
                        <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Batch code" value={receive.data.batch_code} onChange={(e) => receive.setData('batch_code', e.target.value)} />
                        <input className="w-full border rounded px-2 py-2 text-sm" type="date" value={receive.data.expires_on} onChange={(e) => receive.setData('expires_on', e.target.value)} />
                        <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Idempotency key" value={receive.data.idempotency_key} onChange={(e) => receive.setData('idempotency_key', e.target.value)} />
                        {Object.values(receive.errors).map((error, index) => <p key={index} className="text-red-600 text-xs">{error}</p>)}
                        <button disabled={receive.processing} className="w-full bg-[#0b3d0b] text-white rounded py-2 text-sm">Receive</button>
                    </div>
                </form>

                <form className="bg-white border rounded-xl p-4" onSubmit={(e) => { e.preventDefault(); issue.post('/supply/issue'); }}>
                    <h2 className="font-semibold">Issue stock</h2>
                    <div className="mt-3 space-y-2">
                        <select className="w-full border rounded px-2 py-2 text-sm" value={issue.data.item_id} onChange={(e) => issue.setData('item_id', e.target.value)}><option value="">Select item</option>{items.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select>
                        <input className="w-full border rounded px-2 py-2 text-sm" type="number" step="0.001" placeholder="Quantity" value={issue.data.quantity} onChange={(e) => issue.setData('quantity', e.target.value)} />
                        <select className="w-full border rounded px-2 py-2 text-sm" value={issue.data.recipient_type} onChange={(e) => issue.setData('recipient_type', e.target.value)}><option value="unit">Unit</option><option value="work_order">Work order</option><option value="care_visit">Care visit</option></select>
                        <input className="w-full border rounded px-2 py-2 text-sm" type="number" placeholder="Recipient ID" value={issue.data.recipient_id} onChange={(e) => issue.setData('recipient_id', e.target.value)} />
                        <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Purpose" value={issue.data.purpose} onChange={(e) => issue.setData('purpose', e.target.value)} />
                        <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Idempotency key" value={issue.data.idempotency_key} onChange={(e) => issue.setData('idempotency_key', e.target.value)} />
                        {Object.values(issue.errors).map((error, index) => <p key={index} className="text-red-600 text-xs">{error}</p>)}
                        <button disabled={issue.processing} className="w-full bg-[#0b3d0b] text-white rounded py-2 text-sm">Issue</button>
                    </div>
                </form>

                <div className="space-y-4">
                    <form className="bg-white border rounded-xl p-4" onSubmit={(e) => { e.preventDefault(); reconcile.post('/supply/reconcile'); }}>
                        <h2 className="font-semibold">Reconcile count</h2>
                        <div className="mt-3 space-y-2">
                            <select className="w-full border rounded px-2 py-2 text-sm" value={reconcile.data.item_id} onChange={(e) => reconcile.setData('item_id', e.target.value)}><option value="">Select item</option>{items.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select>
                            <input className="w-full border rounded px-2 py-2 text-sm" type="number" step="0.001" placeholder="Expected" value={reconcile.data.expected_quantity} onChange={(e) => reconcile.setData('expected_quantity', e.target.value)} />
                            <input className="w-full border rounded px-2 py-2 text-sm" type="number" step="0.001" placeholder="Counted" value={reconcile.data.counted_quantity} onChange={(e) => reconcile.setData('counted_quantity', e.target.value)} />
                            <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Notes" value={reconcile.data.notes} onChange={(e) => reconcile.setData('notes', e.target.value)} />
                            {Object.values(reconcile.errors).map((error, index) => <p key={index} className="text-red-600 text-xs">{error}</p>)}
                            <button disabled={reconcile.processing} className="w-full border rounded py-2 text-sm">Record count</button>
                        </div>
                    </form>

                    <form className="bg-white border rounded-xl p-4" onSubmit={(e) => { e.preventDefault(); newItem.post('/supply/items'); }}>
                        <h2 className="font-semibold">New supply item</h2>
                        <div className="mt-3 space-y-2">
                            <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Name" value={newItem.data.name} onChange={(e) => newItem.setData('name', e.target.value)} />
                            <input className="w-full border rounded px-2 py-2 text-sm" placeholder="SKU" value={newItem.data.sku} onChange={(e) => newItem.setData('sku', e.target.value)} />
                            <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Category" value={newItem.data.category} onChange={(e) => newItem.setData('category', e.target.value)} />
                            <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Unit" value={newItem.data.unit} onChange={(e) => newItem.setData('unit', e.target.value)} />
                            {Object.values(newItem.errors).map((error, index) => <p key={index} className="text-red-600 text-xs">{error}</p>)}
                            <button disabled={newItem.processing} className="w-full border rounded py-2 text-sm">Add item</button>
                        </div>
                    </form>
                </div>
            </div>

            <div className="bg-white border rounded-xl mt-6 overflow-hidden">
                <div className="p-4 border-b flex justify-between items-center">
                    <h2 className="font-semibold">Stock balances</h2>
                    <div className="flex gap-2 text-sm">
                        <Link href={filters.low_stock ? '/supply' : '/supply?low_stock=1'} className="border rounded px-2 py-1">{filters.low_stock ? 'Show all' : 'Low stock only'}</Link>
                    </div>
                </div>
                {balances.data.length ? (
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left"><tr><th className="p-3">Item</th><th className="p-3">Store</th><th className="p-3">On hand</th><th className="p-3">Reserved</th><th className="p-3">Updated</th></tr></thead>
                        <tbody>
                            {balances.data.map((balance) => (
                                <tr key={balance.id} className="border-t">
                                    <td className="p-3"><Link href={`/supply/items/${balance.item_id}`} className="text-[#0b3d0b] underline">{balance.item?.name}</Link><div className="text-xs text-slate-400">{balance.item?.sku}</div></td>
                                    <td className="p-3">{balance.store}</td>
                                    <td className="p-3 font-semibold">{balance.quantity_on_hand} {balance.item?.unit}</td>
                                    <td className="p-3">{balance.quantity_reserved}</td>
                                    <td className="p-3 text-xs text-slate-500">{balance.updated_at}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : <div className="p-4"><EmptyState message="No stock balances found. Add an item and receive stock." /></div>}
                <div className="p-3 border-t flex gap-2 text-sm">
                    {balances.links.map((link, index) => <Link key={index} href={link.url || '#'} className={`px-2 py-1 rounded border ${link.active ? 'bg-[#0b3d0b] text-white' : ''}`} dangerouslySetInnerHTML={{ __html: link.label }} />)}
                </div>
            </div>

            {approvals.length > 0 && (
                <div className="bg-white border rounded-xl mt-6 p-4">
                    <h2 className="font-semibold">Pending approvals</h2>
                    <div className="divide-y mt-2">
                        {approvals.map((approval) => <div key={approval.id} className="py-2 flex justify-between text-sm"><span>{approval.type} · {approval.subject?.quantity ?? ''}</span><StatusBadge value={approval.status} /></div>)}
                    </div>
                </div>
            )}
        </Layout>
    );
}
