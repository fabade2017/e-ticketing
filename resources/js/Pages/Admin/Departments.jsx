import { useForm, router, Link } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';

export default function Departments({ departments, users, roles }) {
    const { data, setData, post, processing } = useForm({ name: '', code: '', description: '' });
    const [assign, setAssign] = useState({ user_id: '', department_id: '', role: '' });

    const create = e=>{ e.preventDefault(); post('/admin/departments'); };
    const assignUser = e=>{ e.preventDefault(); router.post('/admin/departments/assign', assign); };

    return (
        <Layout>
            <h1 className="text-2xl font-bold">Departments</h1>
            <p className="text-sm text-slate-600">SuperAdmin: create departments, assign users to departments + roles. Tickets are assigned via department → role → user search.</p>

            <div className="grid md:grid-cols-2 gap-6 mt-6">
                <div className="bg-white border rounded-xl p-6">
                    <h3 className="font-semibold">Create Department</h3>
                    <form onSubmit={create} className="space-y-3 mt-3">
                        <input value={data.name} onChange={e=>setData('name', e.target.value)} placeholder="Name e.g. Logistics" className="w-full border rounded px-3 py-2" />
                        <input value={data.code} onChange={e=>setData('code', e.target.value)} placeholder="Code e.g. LOG" className="w-full border rounded px-3 py-2" />
                        <input value={data.description} onChange={e=>setData('description', e.target.value)} placeholder="Description" className="w-full border rounded px-3 py-2" />
                        <button disabled={processing} className="w-full bg-[#0b3d0b] text-white py-2 rounded">Create</button>
                    </form>
                    <div className="mt-6 divide-y border-t">
                        {departments.map(d=>(
                            <div key={d.id} className="py-3 flex justify-between">
                                <div><div className="font-semibold text-sm">{d.name} <span className="text-xs bg-slate-100 px-1.5 py-0.5 rounded">{d.code}</span></div><div className="text-xs text-slate-500">{d.description} • {d.users_count} users</div></div>
                                <button onClick={()=>{ if(confirm('Delete?')) router.delete(`/admin/departments/${d.id}`)}} className="text-red-600 text-xs">Delete</button>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="bg-white border rounded-xl p-6">
                    <h3 className="font-semibold">Assign User to Department + Role</h3>
                    <form onSubmit={assignUser} className="space-y-3 mt-3">
                        <select value={assign.user_id} onChange={e=>setAssign({...assign, user_id: e.target.value})} className="w-full border rounded px-3 py-2">
                            <option value="">Select User</option>
                            {users.map(u=> <option key={u.id} value={u.id}>{u.rank} {u.name} ({u.service_number}) — Dept: {u.department?.name || '—'} — {u.roles?.map(r=>r.name).join(',')}</option>)}
                        </select>
                        <select value={assign.department_id} onChange={e=>setAssign({...assign, department_id: e.target.value})} className="w-full border rounded px-3 py-2">
                            <option value="">Select Department</option>
                            {departments.map(d=> <option key={d.id} value={d.id}>{d.name}</option>)}
                        </select>
                        <select value={assign.role} onChange={e=>setAssign({...assign, role: e.target.value})} className="w-full border rounded px-3 py-2">
                            <option value="">Pick Role (dropdown)</option>
                            {roles.map(r=> <option key={r} value={r}>{r}</option>)}
                        </select>
                        <button className="w-full bg-slate-800 text-white py-2 rounded">Assign</button>
                    </form>
                    <p className="text-xs text-slate-500 mt-4">Then in Tickets → Show → pick department → role dropdown → search user to assign. Users are searchable under that role+department.</p>
                    <Link href="/tickets" className="text-sm underline mt-4 inline-block">Go to Tickets →</Link>
                </div>
            </div>
        </Layout>
    );
}
