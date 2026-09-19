import { router } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';

export default function Users({ users, roles }) {
    const [sel, setSel] = useState({});
    const update = (id) => router.post(`/admin/users/${id}/role`, { role: sel[id] });

    return (
        <Layout>
            <h1 className="text-2xl font-bold">User Management — SuperAdmin</h1>
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
                                <td className="p-3">{u.face_embedding ? 'Enrolled' : 'Not enrolled'}</td>
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
        </Layout>
    );
}
