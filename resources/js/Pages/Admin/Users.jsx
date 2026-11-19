import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';
import useLiveFilters from '../../hooks/useLiveFilters';

export default function Users({ users, roles, filters }) {
    const [sel, setSel] = useState({});
    const [search, setSearch] = useState(filters?.search || '');
    const update = (id) => router.post(`/admin/users/${id}/role`, { role: sel[id] });

    const applyFilters = (next) => router.get('/admin/users', { search: next.search }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['users', 'filters'],
    });
    const applyNow = useLiveFilters({ search }, applyFilters, 300);

    return (
        <Layout>
            <h1 className="text-2xl font-bold">User Management — SuperAdmin</h1>
            <div className="bg-white border rounded-xl p-4 mt-4 flex gap-3">
                <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search name / service_number / email" className="flex-1 border rounded px-3 py-2" onKeyDown={e=>e.key==='Enter'&&applyNow()} />
                <button onClick={()=>{ setSearch(''); applyNow({ search: '' }); }} className="border px-4 py-2 rounded text-sm">Clear</button>
                <button onClick={()=>applyNow()} className="bg-slate-800 text-white px-4 py-2 rounded">Search</button>
            </div>
            <div className="bg-white border rounded-xl mt-6 overflow-hidden">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50"><tr><th className="p-3 text-left">Name</th><th className="p-3 text-left">Service No</th><th className="p-3 text-left">Unit</th><th className="p-3 text-left">Roles</th><th className="p-3 text-left">Face</th><th className="p-3 text-left">Change Role</th></tr></thead>
                    <tbody>
                        {users.data.map(u=>(
                            <tr key={u.id} className="border-t">
                                <td className="p-3">{u.name} <div className="text-xs text-slate-500">{u.email}</div></td>
                                <td className="p-3 font-mono text-xs">{u.service_number || '—'}</td>
                                <td className="p-3">{u.unit || '—'}</td>
                                <td className="p-3">{u.roles?.map(r=>r.name).join(', ')}</td>
                                <td className="p-3">{u.face_enrolled_at ? 'Enrolled' : 'Not enrolled'}</td>
                                <td className="p-3 flex gap-2">
                                    <select value={sel[u.id] || ''} onChange={e=>setSel({...sel, [u.id]: e.target.value})} className="border rounded px-2 py-1">
                                        <option value="">Select</option>{roles.map(r=><option key={r} value={r}>{r}</option>)}
                                    </select>
                                    <button onClick={()=>update(u.id)} className="bg-[#0b3d0b] text-white px-3 py-1 rounded">Apply</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="flex justify-between mt-4 text-sm">
                <span>Page {users.current_page} of {users.last_page} • Total {users.total}</span>
                <div className="flex gap-2">
                    {users.prev_page_url && <Link href={users.prev_page_url} className="border px-3 py-1 rounded">Prev</Link>}
                    {users.next_page_url && <Link href={users.next_page_url} className="border px-3 py-1 rounded">Next</Link>}
                </div>
            </div>
        </Layout>
    );
}
