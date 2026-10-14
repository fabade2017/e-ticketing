import { useForm, usePage, router } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';

export default function Roles({ roles, permissions, users }) {
    const [selPerms, setSelPerms] = useState({});
    const [newRole, setNewRole] = useState({ name: '', permissions: [] });
    const [newPerm, setNewPerm] = useState('');
    const [assign, setAssign] = useState({ user_id: '', roles: [] });

    const createRole = e => { e.preventDefault(); router.post('/admin/roles', newRole); };
    const createPerm = e => { e.preventDefault(); router.post('/admin/permissions', { name: newPerm }); };
    const updateRolePerms = (role) => {
        const perms = selPerms[role.id] ?? role.permissions.map(p => p.name);
        router.put(`/admin/roles/${role.id}`, { permissions: perms });
    };
    const deleteRole = (roleId) => { if(confirm('Delete role?')) router.delete(`/admin/roles/${roleId}`); };
    const assignRoles = e => { e.preventDefault(); router.post('/admin/users/assign-role', assign); };

    return (
        <Layout>
            <h1 className="text-2xl font-bold">Roles & Permissions — SuperAdmin</h1>
            <p className="text-sm text-slate-600">Control RBAC: create roles, attach permissions, assign to users. Core roles cannot be deleted. See <a href="/help" className="underline">Help</a> for story.</p>

            <div className="grid md:grid-cols-2 gap-6 mt-6">
                <div className="bg-white border rounded-xl p-6">
                    <h3 className="font-semibold">Create Role</h3>
                    <form onSubmit={createRole} className="mt-3 space-y-3">
                        <input value={newRole.name} onChange={e=>setNewRole({...newRole, name: e.target.value})} placeholder="Role name e.g. Intel" className="w-full border rounded px-3 py-2" />
                        <div className="flex flex-wrap gap-2">
                            {permissions.map(p=>(
                                <label key={p} className="text-xs border rounded px-2 py-1 flex items-center gap-1">
                                    <input type="checkbox" checked={newRole.permissions.includes(p)} onChange={e=> setNewRole({...newRole, permissions: e.target.checked ? [...newRole.permissions, p] : newRole.permissions.filter(x=>x!==p)})} />
                                    {p}
                                </label>
                            ))}
                        </div>
                        <button className="w-full bg-[#0b3d0b] text-white py-2 rounded">Create Role</button>
                    </form>
                    <form onSubmit={createPerm} className="mt-6 border-t pt-4">
                        <h4 className="font-semibold text-sm">Create Permission</h4>
                        <div className="flex gap-2 mt-2">
                            <input value={newPerm} onChange={e=>setNewPerm(e.target.value)} placeholder="e.g. view intel reports" className="flex-1 border rounded px-3 py-2" />
                            <button className="bg-slate-800 text-white px-4 rounded">Add</button>
                        </div>
                    </form>
                </div>

                <div className="bg-white border rounded-xl p-6">
                    <h3 className="font-semibold">Assign Roles to User (Bulk)</h3>
                    <form onSubmit={assignRoles} className="mt-3 space-y-3">
                        <select value={assign.user_id} onChange={e=>setAssign({...assign, user_id: e.target.value})} className="w-full border rounded px-3 py-2">
                            <option value="">Select user</option>
                            {users.map(u=><option key={u.id} value={u.id}>{u.name} ({u.service_number}) — {u.roles?.map(r=>r.name).join(',')}</option>)}
                        </select>
                        <div className="flex flex-wrap gap-2">
                            {roles.map(r=>(
                                <label key={r.id} className="text-xs border rounded px-2 py-1 flex items-center gap-1">
                                    <input type="checkbox" checked={assign.roles.includes(r.name)} onChange={e=> setAssign({...assign, roles: e.target.checked ? [...assign.roles, r.name] : assign.roles.filter(x=>x!==r.name)})} />
                                    {r.name}
                                </label>
                            ))}
                        </div>
                        <button className="w-full bg-slate-800 text-white py-2 rounded">Sync Roles</button>
                    </form>
                </div>
            </div>

            <div className="bg-white border rounded-xl mt-6 overflow-hidden">
                <div className="px-6 py-3 bg-slate-50 font-semibold">Existing Roles</div>
                <div className="divide-y">
                    {roles.map(r=>(
                        <div key={r.id} className="p-4 flex flex-col md:flex-row gap-4 justify-between">
                            <div className="flex-1">
                                <div className="font-bold">{r.name} <span className="text-xs text-slate-500">({r.permissions.length} perms)</span></div>
                                <div className="flex flex-wrap gap-2 mt-2">
                                    {permissions.map(p=>(
                                        <label key={p} className="text-xs border rounded px-2 py-1 flex items-center gap-1">
                                            <input type="checkbox" defaultChecked={r.permissions.some(x=>x.name===p)} onChange={e=>{
                                                const cur = selPerms[r.id] ?? r.permissions.map(x=>x.name);
                                                const next = e.target.checked ? [...cur, p] : cur.filter(x=>x!==p);
                                                setSelPerms({...selPerms, [r.id]: next});
                                            }} />
                                            {p}
                                        </label>
                                    ))}
                                </div>
                            </div>
                            <div className="flex gap-2 self-start">
                                <button onClick={()=>updateRolePerms(r)} className="bg-[#0b3d0b] text-white px-3 py-1 rounded text-sm">Save Perms</button>
                                <button onClick={()=>deleteRole(r.id)} className="bg-red-600 text-white px-3 py-1 rounded text-sm">Delete</button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </Layout>
    );
}
