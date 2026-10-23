import { useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import Layout from '../../Components/Layout';

export default function Show({ ticket, departments, roles, histories }) {
    const { auth } = usePage().props;
    const canManage = ['SuperAdmin','Admin','Support'].some(r=>auth.user?.roles?.includes(r));
    const [deptId, setDeptId] = useState(ticket.department_id || '');
    const [role, setRole] = useState('');
    const [search, setSearch] = useState('');
    const [users, setUsers] = useState([]);
    const { data, setData, put, processing } = useForm({ status: ticket.status, priority: ticket.priority, assigned_to: ticket.assigned_to || '', department_id: ticket.department_id || '' });

    useEffect(()=>{
        if (!role && !deptId && !search) { setUsers([]); return; }
        const params = new URLSearchParams();
        if (role) params.append('role', role);
        if (deptId) params.append('department_id', deptId);
        if (search) params.append('search', search);
        fetch(`/admin/departments/users/search?${params.toString()}`, { headers:{'Accept':'application/json'}})
            .then(r=> r.ok ? r.json() : []).then(j=> setUsers(Array.isArray(j) ? j : [])).catch(()=> setUsers([]));
    }, [role, deptId, search]);

    const update = e => { e.preventDefault(); put(`/tickets/${ticket.id}`); };

    return (
        <Layout>
            <h1 className="text-xl font-bold">{ticket.ticket_number} — {ticket.title}</h1>
            <div className="grid md:grid-cols-3 gap-6 mt-6">
                <div className="md:col-span-2 bg-white border rounded-xl p-6">
                    <p className="text-sm text-slate-600 whitespace-pre-wrap">{ticket.description}</p>
                    <div className="mt-6 grid grid-cols-2 gap-4 text-sm">
                        <div><span className="text-slate-500">Category:</span> {ticket.category}</div>
                        <div><span className="text-slate-500">Department:</span> {ticket.department?.name || '—'}</div>
                        <div><span className="text-slate-500">Priority:</span> {ticket.priority} {ticket.sla_due_at && <span className={`ml-2 text-xs px-1.5 py-0.5 rounded ${new Date(ticket.sla_due_at) < new Date() && !['resolved','closed'].includes(ticket.status) ? 'bg-red-600 text-white' : 'bg-slate-100'}`}>SLA {new Date(ticket.sla_due_at).toLocaleString()} {new Date(ticket.sla_due_at) < new Date() && !['resolved','closed'].includes(ticket.status) ? 'OVERDUE' : ''}</span>}</div>
                        <div><span className="text-slate-500">Status:</span> {ticket.status} {ticket.escalated_at && <span className="text-xs bg-amber-100 px-1 rounded">escalated</span>}</div>
                        <div><span className="text-slate-500">Country:</span> {ticket.country || 'Nigeria'}</div>
                        <div><span className="text-slate-500">State:</span> {ticket.state || '—'}</div>
                        <div><span className="text-slate-500">LGA:</span> {ticket.lga || '—'}</div>
                        <div><span className="text-slate-500">Location:</span> {ticket.location || '—'}</div>
                        <div><span className="text-slate-500">Assigned:</span> {ticket.assignee ? `${ticket.assignee.rank} ${ticket.assignee.name} (${ticket.assignee.service_number})` : 'Unassigned'}</div>
                        <div><span className="text-slate-500">Raised by:</span> {ticket.is_anonymous ? <span className="font-semibold text-amber-700">Anonymous</span> : `${ticket.user?.name || '—'} (${ticket.user?.service_number || '—'})`}</div>
                        <div><span className="text-slate-500">Unit:</span> {ticket.is_anonymous ? 'Hidden' : (ticket.unit || '—')}</div>
                    </div>
                    {histories?.length > 0 && (
                        <div className="mt-6 border-t pt-4">
                            <h3 className="font-semibold text-sm">History & Audit</h3>
                            <div className="mt-3 space-y-2 max-h-64 overflow-auto">
                                {histories.map(h=>(
                                    <div key={h.id} className="text-xs border rounded p-2 bg-slate-50">
                                        <div className="font-mono">{new Date(h.created_at).toLocaleString()} — {h.user?.name || (h.action === 'created' && ticket.is_anonymous ? 'Anonymous' : 'System')} — {h.action}</div>
                                        <div className="text-slate-600">From: {JSON.stringify(h.from)} → To: {JSON.stringify(h.to)} {h.ip && `• ${h.ip}`}</div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
                {canManage && (
                    <form onSubmit={update} className="bg-white border rounded-xl p-6 space-y-3 h-fit">
                        <h3 className="font-semibold">Manage — Department & Assignment</h3>
                        <p className="text-xs text-slate-500">Pick department → role → search user to assign. Users are filtered by both.</p>
                        <select value={data.status} onChange={e=>setData('status', e.target.value)} className="w-full border rounded px-3 py-2"><option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option><option value="closed">Closed</option></select>
                        <select value={data.priority} onChange={e=>setData('priority', e.target.value)} className="w-full border rounded px-3 py-2"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
                        <select value={data.department_id} onChange={e=>{ setData('department_id', e.target.value); setDeptId(e.target.value); }} className="w-full border rounded px-3 py-2">
                            <option value="">Select Department</option>
                            {Object.entries(departments || {}).map(([id,name])=> <option key={id} value={id}>{name}</option>)}
                        </select>
                        <select value={role} onChange={e=>setRole(e.target.value)} className="w-full border rounded px-3 py-2">
                            <option value="">Filter by Role (dropdown) — fills users below</option>
                            {(roles || []).map(r=> <option key={r} value={r}>{r}</option>)}
                        </select>
                        <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Optional: filter the dropdown by name / service number" className="w-full border rounded px-3 py-2" />
                        <select value={data.assigned_to} onChange={e=>setData('assigned_to', e.target.value)} className="w-full border rounded px-3 py-2 bg-white">
                            <option value="">{(users||[]).length ? `— ${users.length} users with role ${role || 'any'} — pick one —` : (role ? 'No users for this role — try another role' : 'Pick a role above to fill this dropdown')}</option>
                            {(users||[]).map(u=> <option key={u.id} value={u.id}>{u.rank} {u.name} ({u.service_number}) — Dept {u.department_id || '—'}</option>)}
                        </select>
                        {data.assigned_to && <div className="text-xs text-green-700">Selected: {(users||[]).find(x=>String(x.id)===String(data.assigned_to))?.service_number || data.assigned_to} <button type="button" onClick={()=>setData('assigned_to','')} className="underline ml-2">clear</button></div>}
                        <button disabled={processing} className="w-full bg-slate-800 text-white py-2 rounded">Update Ticket</button>
                    </form>
                )}
            </div>
        </Layout>
    );
}
