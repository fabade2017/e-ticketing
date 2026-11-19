import { Link } from '@inertiajs/react';
import Layout from '../../Components/Layout';
import PageHeader from '../../Components/ui/PageHeader';
import StatCard from '../../Components/ui/StatCard';
import StatusBadge from '../../Components/ui/StatusBadge';
import EmptyState from '../../Components/ui/EmptyState';

export default function Index({ workOrders, insights, filters, canSeeAll, isAll }) {
    return (
        <Layout>
            <PageHeader
                title="Maintenance"
                subtitle="Work orders, equipment faults, and preventive maintenance"
                actions={
                    <>
                        {canSeeAll && <Link href={isAll ? '/maintenance?scope=scoped' : '/maintenance?scope=all'} className="border rounded px-3 py-2 text-sm">{isAll ? 'My Scope' : 'All Hospitals'}</Link>}
                        <Link href="/maintenance/create" className="bg-[#0b3d0b] text-white rounded px-3 py-2 text-sm">New work order</Link>
                    </>
                }
            />
            <div className="grid grid-cols-2 gap-3 mt-5">
                {(insights || []).map((metric) => <StatCard key={metric.label} label={metric.label} value={metric.value} href={metric.url} />)}
            </div>
            <div className="bg-white border rounded-xl mt-6 overflow-hidden">
                <div className="p-4 border-b flex gap-2 text-sm">
                    {['', 'pending', 'assigned', 'in_progress', 'completed', 'overdue'].map((status) => (
                        <Link key={status || 'all'} href={status ? `/maintenance?status=${status}` : '/maintenance'} className={`px-2 py-1 rounded border ${filters.status === status ? 'bg-[#0b3d0b] text-white' : ''}`}>{status || 'All'}</Link>
                    ))}
                </div>
                {workOrders.data.length ? (
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left"><tr><th className="p-3">Number</th><th className="p-3">Title</th><th className="p-3">Equipment</th><th className="p-3">Priority</th><th className="p-3">Status</th><th className="p-3">Due</th></tr></thead>
                        <tbody>
                            {workOrders.data.map((order) => (
                                <tr key={order.id} className="border-t">
                                    <td className="p-3"><Link href={`/maintenance/${order.id}`} className="text-[#0b3d0b] underline font-mono text-xs">{order.work_order_number}</Link></td>
                                    <td className="p-3">{order.title}</td>
                                    <td className="p-3">{order.equipment?.name || '—'}<div className="text-xs text-slate-400">{order.equipment?.asset_id}</div></td>
                                    <td className="p-3">{order.priority}</td>
                                    <td className="p-3"><StatusBadge value={order.status} /></td>
                                    <td className="p-3 text-xs">{order.due_at || '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : <div className="p-4"><EmptyState message="No work orders found." /></div>}
                <div className="p-3 border-t flex gap-2 text-sm">
                    {workOrders.links.map((link, index) => <Link key={index} href={link.url || '#'} className={`px-2 py-1 rounded border ${link.active ? 'bg-[#0b3d0b] text-white' : ''}`} dangerouslySetInnerHTML={{ __html: link.label }} />)}
                </div>
            </div>
        </Layout>
    );
}
