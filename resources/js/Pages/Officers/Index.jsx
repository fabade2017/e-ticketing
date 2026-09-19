import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';

export default function Index({ officers, filters, roles, stats }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [role, setRole] = useState(filters?.role || '');
    const [face, setFace] = useState(filters?.face || '');

    const apply = () => router.get('/officers', { search, role, face }, { preserveState: true, replace: true });

    return (
        <Layout>
            <div className="flex justify-between items-start">
                <div>
                    <h1 className="text-2xl font-bold">Officers — {stats.total} total, {stats.enrolled} face-enrolled</h1>
                    <p className="text-sm text-slate-600">All army personnel • Face enrollment shows ◉ green (enrolled) / ○ pending • SuperAdmin can enroll via Admin → Branding/Face Settings</p>
                </div>
                <Link href="/admin/users/bulk" className="bg-[#0b3d0b] text-white px-4 py-2 rounded text-sm hidden md:block">Bulk Upload →</Link>
            </div>

            <div className="bg-white border rounded-xl p-4 mt-4 flex flex-wrap gap-3 items-end">
                <div className="flex-1 min-w-[200px]">
                    <label className="text-xs font-medium">Search name / service no / rank</label>
                    <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="e.g. NA/SL/003 or Pte" className="w-full border rounded px-3 py-2 mt-1" onKeyDown={e=>e.key==='Enter'&&apply()} />
                </div>
                <div>
                    <label className="text-xs font-medium">Role</label>
                    <select value={role} onChange={e=>setRole(e.target.value)} className="w-full border rounded px-3 py-2 mt-1">
                        <option value="">All roles</option>
                        {roles.map(r=><option key={r} value={r}>{r}</option>)}
                    </select>
                </div>
                <div>
                    <label className="text-xs font-medium">Face</label>
                    <select value={face} onChange={e=>setFace(e.target.value)} className="w-full border rounded px-3 py-2 mt-1">
                        <option value="">All</option><option value="enrolled">Enrolled</option><option value="pending">Pending</option>
                    </select>
                </div>
                <button onClick={apply} className="bg-slate-800 text-white px-6 py-2 rounded">Filter</button>
                <Link href="/officers" className="border px-4 py-2 rounded text-sm">Reset</Link>
            </div>

            <div className="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-6">
                {officers.data.map(o=>(
                    <div key={o.id} className="bg-white border rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
                        <div className="h-32 bg-gradient-to-br from-slate-800 to-[#0b3d0b] flex items-center justify-center relative">
                            {/* Face placeholder — if enrolled, show green rim, else grey */}
                            <div className={`w-20 h-20 rounded-full bg-white flex items-center justify-center text-2xl border-4 ${o.face_enrolled_at ? 'border-green-400' : 'border-slate-300'}`}>
                                {o.face_enrolled_at ? '◉' : '○'}
                            </div>
                            <span className={`absolute top-2 right-2 text-xs px-2 py-1 rounded-full ${o.face_enrolled_at ? 'bg-green-500 text-white' : 'bg-amber-500 text-black'}`}>{o.face_enrolled_at ? 'Face ✓' : 'Face ○'}</span>
                            <span className="absolute bottom-2 left-2 text-xs bg-black/50 text-white px-2 py-0.5 rounded">{o.roles?.map(r=>r.name).join(', ')}</span>
                        </div>
                        <div className="p-4">
                            <div className="font-bold text-sm">{o.rank} {o.name}</div>
                            <div className="text-xs font-mono text-slate-600">{o.service_number} • {o.unit || '—'}</div>
                            <div className="text-xs text-slate-500 mt-1">{o.email}</div>
                            <div className="text-xs mt-2 flex gap-2">
                                <span className={`px-2 py-0.5 rounded ${o.is_active ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700'}`}>{o.is_active ? 'Active' : 'Inactive'}</span>
                                {o.face_enrolled_at && <span className="text-[11px] text-slate-500">Enrolled {new Date(o.face_enrolled_at).toLocaleDateString()}</span>}
                            </div>
                            <div className="mt-3 flex gap-2">
                                <Link href={`/admin/users?search=${o.service_number}`} className="flex-1 border text-center py-1 rounded text-xs">Manage</Link>
                                <button onClick={()=>router.post(`/chat/direct/${o.id}`)} className="flex-1 bg-[#0b3d0b] text-white text-center py-1 rounded text-xs">💬 Chat</button>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            {officers.data.length===0 && <p className="text-center text-slate-500 mt-10">No officers match filter</p>}

            <div className="flex justify-between mt-6 text-sm">
                <span>Page {officers.current_page} of {officers.last_page} • Total {officers.total}</span>
                <div className="flex gap-2">
                    {officers.prev_page_url && <Link href={officers.prev_page_url} className="border px-3 py-1 rounded">Prev</Link>}
                    {officers.next_page_url && <Link href={officers.next_page_url} className="border px-3 py-1 rounded">Next</Link>}
                </div>
            </div>

            <div className="bg-white border rounded-xl p-4 mt-6 text-xs text-slate-600">
                <b>By Rank:</b> {Object.entries(stats.byRank || {}).map(([k,v])=>`${k}:${v}`).join(' • ') || '—'} • View full RBAC at <Link href="/admin/roles" className="underline">Roles & Permissions</Link> • Enroll faces at <Link href="/admin/settings" className="underline">Face Settings</Link>
            </div>
        </Layout>
    );
}
