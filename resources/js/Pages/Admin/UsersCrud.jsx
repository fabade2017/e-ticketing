import { useForm, router, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';

export default function UsersCrud({ users, roles, departments, filters }) {
    const { flash } = usePage().props;
    const [editing, setEditing] = useState(null);
    const [showCreate, setShowCreate] = useState(false);
    const [search, setSearch] = useState(filters?.search || '');
    const createForm = useForm({ name:'', email:'', service_number:'', rank:'', unit:'', department_id:'', phone:'', role: roles[0]||'Soldier', password:'' });
    const editForm = useForm({ name:'', email:'', service_number:'', rank:'', unit:'', department_id:'', phone:'', role:'', is_active:true, password:'' });

    const startEdit = (u)=>{
        setEditing(u.id);
        editForm.setData({ name:u.name, email:u.email, service_number:u.service_number, rank:u.rank||'', unit:u.unit||'', department_id:u.department_id||'', phone:u.phone||'', role:u.roles?.[0]?.name||roles[0], is_active:u.is_active, password:'' });
    };
    const doSearch = ()=> router.get('/admin/users/crud', { search }, { preserveState:true });

    return (
        <Layout>
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold">Users — CRUD</h1>
                <div className="flex gap-2">
                    <button onClick={()=>setShowCreate(!showCreate)} className="bg-[#0b3d0b] text-white px-4 py-2 rounded text-sm">+ New User</button>
                    <Link href="/admin/users/bulk" className="border px-4 py-2 rounded text-sm">Bulk Upload →</Link>
                </div>
            </div>
            {flash?.success && <div className="bg-green-50 border border-green-200 p-2 rounded mt-3 text-sm">{flash.success}</div>}

            <div className="bg-white border rounded-xl p-4 mt-4 flex gap-3">
                <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search name / service_number / email" className="flex-1 border rounded px-3 py-2" onKeyDown={e=>e.key==='Enter'&&doSearch()} />
                <button onClick={doSearch} className="bg-slate-800 text-white px-4 py-2 rounded">Search</button>
            </div>

            {showCreate && (
                <form onSubmit={e=>{e.preventDefault(); createForm.post('/admin/users/crud');}} className="bg-white border rounded-xl p-6 mt-4 grid md:grid-cols-3 gap-4">
                    <input value={createForm.data.name} onChange={e=>createForm.setData('name',e.target.value)} placeholder="Name" className="border rounded px-3 py-2" />
                    <input value={createForm.data.email} onChange={e=>createForm.setData('email',e.target.value)} placeholder="Email" className="border rounded px-3 py-2" />
                    <input value={createForm.data.service_number} onChange={e=>createForm.setData('service_number',e.target.value)} placeholder="Service No NA/..." className="border rounded px-3 py-2" />
                    <input value={createForm.data.rank} onChange={e=>createForm.setData('rank',e.target.value)} placeholder="Rank" className="border rounded px-3 py-2" />
                    <input value={createForm.data.unit} onChange={e=>createForm.setData('unit',e.target.value)} placeholder="Unit" className="border rounded px-3 py-2" />
                    <select value={createForm.data.department_id} onChange={e=>createForm.setData('department_id',e.target.value)} className="border rounded px-3 py-2">
                        <option value="">Department</option>{Object.entries(departments).map(([id,name])=> <option key={id} value={id}>{name}</option>)}
                    </select>
                    <input value={createForm.data.phone} onChange={e=>createForm.setData('phone',e.target.value)} placeholder="Phone" className="border rounded px-3 py-2" />
                    <select value={createForm.data.role} onChange={e=>createForm.setData('role',e.target.value)} className="border rounded px-3 py-2">
                        {roles.map(r=> <option key={r} value={r}>{r}</option>)}
                    </select>
                    <input value={createForm.data.password} onChange={e=>createForm.setData('password',e.target.value)} placeholder="Password (default Password@123)" className="border rounded px-3 py-2" />
                    <button className="bg-[#0b3d0b] text-white py-2 rounded md:col-span-3">Create User</button>
                </form>
            )}

            <div className="bg-white border rounded-xl mt-6 overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-slate-50"><tr><th className="p-2 text-left">User</th><th className="p-2 text-left">Service No</th><th className="p-2">Dept</th><th className="p-2">Role</th><th className="p-2">Active</th><th className="p-2">Actions</th></tr></thead>
                    <tbody>
                        {users.data.map(u=>(
                            <tr key={u.id} className="border-t">
                                <td className="p-2">{editing===u.id ? <input value={editForm.data.name} onChange={e=>editForm.setData('name',e.target.value)} className="border rounded px-2 py-1 w-32" /> : <span>{u.rank} {u.name}<br/><span className="text-xs text-slate-500">{u.email}</span></span>}</td>
                                <td className="p-2 font-mono text-xs">{editing===u.id ? <input value={editForm.data.service_number} onChange={e=>editForm.setData('service_number',e.target.value)} className="border rounded px-2 py-1 w-24" /> : u.service_number}</td>
                                <td className="p-2">{editing===u.id ? <select value={editForm.data.department_id} onChange={e=>editForm.setData('department_id',e.target.value)} className="border rounded px-2 py-1"><option value="">—</option>{Object.entries(departments).map(([id,n])=> <option key={id} value={id}>{n}</option>)}</select> : (u.department?.name || '—')}</td>
                                <td className="p-2">{editing===u.id ? <select value={editForm.data.role} onChange={e=>editForm.setData('role',e.target.value)} className="border rounded px-2 py-1">{roles.map(r=> <option key={r} value={r}>{r}</option>)}</select> : u.roles?.map(r=>r.name).join(', ')}</td>
                                <td className="p-2">{editing===u.id ? <input type="checkbox" checked={editForm.data.is_active} onChange={e=>editForm.setData('is_active', e.target.checked)} /> : (u.is_active ? '✓' : '✗')}</td>
                                <td className="p-2 flex gap-1">
                                    {editing===u.id ? (
                                        <>
                                            <button onClick={()=>editForm.put(`/admin/users/crud/${u.id}`)} className="bg-green-600 text-white px-2 py-1 rounded text-xs">Save</button>
                                            <button onClick={()=>setEditing(null)} className="border px-2 py-1 rounded text-xs">Cancel</button>
                                        </>
                                    ) : (
                                        <>
                                            <button onClick={()=>startEdit(u)} className="bg-slate-800 text-white px-2 py-1 rounded text-xs">Edit</button>
                                            <button onClick={()=>{ if(confirm('Delete user?')) router.delete(`/admin/users/crud/${u.id}`)}} className="bg-red-600 text-white px-2 py-1 rounded text-xs">Delete</button>
                                        </>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="flex gap-2 mt-4 text-sm">
                {users.prev_page_url && <Link href={users.prev_page_url} className="border px-3 py-1 rounded">Prev</Link>}
                {users.next_page_url && <Link href={users.next_page_url} className="border px-3 py-1 rounded">Next</Link>}
            </div>
        </Layout>
    );
}
