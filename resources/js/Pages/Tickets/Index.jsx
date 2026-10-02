import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';

export default function Index({ tickets, filters, scope, canSeeAll, isAll }) {
    const safeTickets = tickets || { data: [] };
    const safeFilters = Array.isArray(filters) ? {} : (filters || {});
    const [search, setSearch] = useState(safeFilters.search || '');
    const [status, setStatus] = useState(safeFilters.status || '');
    const [filter, setFilter] = useState(safeFilters.filter || '');
    const apply = () => {
        const params = { search, status, filter };
        if (safeFilters.scope) params.scope = safeFilters.scope;
        if (safeFilters.view) params.view = safeFilters.view;
        // preserve scope toggle
        if (isAll) params.scope = 'all';
        router.get('/tickets', params, { preserveState: true });
    };

    const slaBadge = (t)=>{
        try {
            if (!t?.sla_due_at || ['resolved','closed'].includes(t?.status)) return null;
            const due = new Date(t.sla_due_at);
            if (isNaN(due)) return null;
            const overdue = due < new Date();
            const hours = Math.round((due - new Date())/3600000);
            return <span className={`ml-2 text-xs px-1.5 py-0.5 rounded ${overdue ? 'bg-red-600 text-white' : hours<4 ? 'bg-amber-500 text-black' : 'bg-green-100 text-green-800'}`}>{overdue ? 'OVERDUE' : `${hours}h SLA`}</span>;
        } catch(e) { return null; }
    };

    const list = Array.isArray(safeTickets.data) ? safeTickets.data : [];

    return (
        <Layout>
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold">Tickets {scope ? <span className="text-sm font-normal text-slate-500">— {isAll ? 'All' : scope}</span> : ''}</h1>
                    <p className="text-xs mt-1">
                        <span className={`px-2 py-1 rounded-full border ${isAll ? 'bg-slate-800 text-white border-slate-800' : 'bg-amber-50 text-amber-800 border-amber-200'}`}>{isAll ? 'Viewing ALL tickets (privileged)' : `Scoped to ${scope || 'you'} — only your dept/unit/assigned`}</span>
                        {canSeeAll && (
                            isAll
                                ? <Link href="/tickets" className="ml-2 underline text-[#0b3d0b]">Switch to My Scope</Link>
                                : <Link href="/tickets?scope=all" className="ml-2 underline text-[#0b3d0b]">View All</Link>
                        )}
                    </p>
                </div>
                <Link href="/tickets/create" className="bg-[#0b3d0b] text-white px-4 py-2 rounded">New Ticket</Link>
            </div>
            <div className="flex gap-2 mt-4 flex-wrap">
                <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search ticket number / title" className="border rounded px-3 py-2 flex-1 min-w-[180px]" />
                <select value={status} onChange={e=>setStatus(e.target.value)} className="border rounded px-3 py-2">
                    <option value="">All Status</option><option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option><option value="closed">Closed</option>
                </select>
                <select value={filter} onChange={e=>setFilter(e.target.value)} className="border rounded px-3 py-2">
                    <option value="">All</option><option value="overdue">Overdue SLA</option>
                </select>
                <button onClick={apply} className="bg-slate-800 text-white px-4 py-2 rounded">Filter</button>
            </div>
            <p className="text-xs text-slate-500 mt-2">SLA: urgent 4h, high 12h, medium 24h, low 72h.</p>
            <div className="mt-6 bg-white border rounded-xl overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50"><tr><th className="text-left p-3">Ticket</th><th className="text-left p-3">Title</th><th className="text-left p-3">Dept</th><th className="text-left p-3">Priority</th><th className="text-left p-3">Status</th><th className="text-left p-3">Owner</th></tr></thead>
                    <tbody>
                        {list.map(t=>(
                            <tr key={t.id} className={`border-t hover:bg-slate-50 ${t.sla_due_at && new Date(t.sla_due_at) < new Date() && !['resolved','closed'].includes(t.status) ? 'bg-red-50' : ''}`}>
                                <td className="p-3 font-mono text-xs"><Link href={`/tickets/${t.id}`} className="text-green-700 underline">{t.ticket_number}</Link>{slaBadge(t)}</td>
                                <td className="p-3">{t.title || '—'}</td>
                                <td className="p-3 text-xs">{t.department?.name || '—'}</td>
                                <td className="p-3"><span className="px-2 py-1 rounded text-xs bg-slate-100">{t.priority || '—'}</span>{t.escalated_at && <span className="ml-1 text-xs bg-amber-100 px-1 rounded">escalated</span>}</td>
                                <td className="p-3">{t.status || '—'}</td>
                                <td className="p-3">{t.user?.name || '—'}</td>
                            </tr>
                        ))}
                        {list.length===0 && <tr><td colSpan={6} className="p-6 text-center text-slate-500">No tickets. <Link href="/tickets/create" className="underline">Create one</Link></td></tr>}
                    </tbody>
                </table>
            </div>
        </Layout>
    );
}
