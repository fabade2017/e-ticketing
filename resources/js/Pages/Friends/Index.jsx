import { useForm, router, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';

export default function FriendsIndex({ users, friends, pendingReceived, pendingSent, groupRequests, filters }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters?.search || '');
    const [msg, setMsg] = useState({});

    const doSearch = () => router.get('/friends', { search }, { preserveState:true, replace:true });

    const sendColleague = (receiver_id) => router.post('/friends/request', { receiver_id, message: msg[receiver_id] || '' });
    const sendGroupJoin = (group_id) => router.post('/friends/group-join', { group_id, message: msg[`g-${group_id}`] || '' });

    return (
        <Layout>
            <h1 className="text-2xl font-bold">Friends & Colleagues</h1>
            <p className="text-sm text-slate-600">Soldiers can request to add colleagues (1-1 private chat) or request to join a chat group. If granted, they appear under Friends and can chat.</p>
            {flash?.success && <div className="bg-green-50 border border-green-200 text-green-800 p-2 rounded mt-3 text-sm">{flash.success}</div>}
            {flash?.error && <div className="bg-red-50 border text-red-800 p-2 rounded mt-3 text-sm">{flash.error}</div>}

            <div className="bg-white border rounded-xl p-4 mt-4 flex gap-3 items-end">
                <div className="flex-1">
                    <label className="text-xs font-medium">Search individual / number</label>
                    <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="e.g. NA/SL/003, Musa, Pte" className="w-full border rounded px-3 py-2 mt-1" onKeyDown={e=>e.key==='Enter'&&doSearch()} />
                </div>
                <button onClick={doSearch} className="bg-slate-800 text-white px-6 py-2 rounded">Search</button>
                <Link href="/friends" className="border px-4 py-2 rounded text-sm">Reset</Link>
            </div>

            <div className="grid md:grid-cols-3 gap-6 mt-6">
                <div className="md:col-span-2">
                    <h3 className="font-semibold">People ({users.total} found)</h3>
                    <div className="grid md:grid-cols-2 gap-3 mt-3">
                        {users.data.map(u=>(
                            <div key={u.id} className="bg-white border rounded-xl p-4 flex justify-between items-center">
                                <div>
                                    <div className="font-bold text-sm">{u.rank} {u.name}</div>
                                    <div className="text-xs font-mono text-slate-600">{u.service_number} • {u.unit || '—'}</div>
                                    <div className="text-xs text-slate-500">{u.roles?.map(r=>r.name).join(', ')}</div>
                                </div>
                                <div className="flex flex-col gap-1">
                                    <input value={msg[u.id]||''} onChange={e=>setMsg({...msg, [u.id]: e.target.value})} placeholder="Hello..." className="border rounded px-2 py-1 text-xs w-28" />
                                    <button onClick={()=>sendColleague(u.id)} className="bg-[#0b3d0b] text-white px-3 py-1 rounded text-xs">+ Add Colleague</button>
                                    <button onClick={()=>router.post(`/chat/direct/${u.id}`)} className="bg-slate-800 text-white px-3 py-1 rounded text-xs">💬 Direct Chat</button>
                                </div>
                            </div>
                        ))}
                    </div>
                    {users.data.length===0 && <p className="text-sm text-slate-500 mt-4">No users found.</p>}
                </div>

                <div className="space-y-4">
                    <div className="bg-white border rounded-xl p-4">
                        <h3 className="font-bold text-sm">My Colleagues / Friends ({friends.length})</h3>
                        {friends.length===0 ? <p className="text-xs text-slate-500 mt-2">No colleagues yet — send requests.</p> : (
                            <div className="divide-y mt-2">
                                {friends.map(f=>(
                                    <div key={f.id} className="py-2 flex justify-between items-center">
                                        <div><div className="text-sm font-medium">{f.rank} {f.name}</div><div className="text-xs text-slate-500">{f.service_number}</div></div>
                                        <button onClick={()=>router.post(`/chat/direct/${f.id}`)} className="bg-[#0b3d0b] text-white px-3 py-1 rounded text-xs">💬 Chat</button>
                                    </div>
                                ))}
                            </div>
                        )}
                        <Link href="/chat" className="text-xs underline mt-3 inline-block">Go to Chat →</Link>
                    </div>

                    <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <h3 className="font-bold text-sm">Received Requests ({pendingReceived.length})</h3>
                        {pendingReceived.map(r=>(
                            <div key={r.id} className="bg-white border rounded p-2 mt-2 text-xs">
                                <div><b>{r.sender?.name} ({r.sender?.service_number})</b> wants to be colleague{r.message ? `: "${r.message}"` : ''}</div>
                                <div className="flex gap-2 mt-2">
                                    <button onClick={()=>router.post(`/friends/requests/${r.id}/accept`)} className="bg-green-600 text-white px-3 py-1 rounded">Accept</button>
                                    <button onClick={()=>router.post(`/friends/requests/${r.id}/reject`)} className="bg-red-600 text-white px-3 py-1 rounded">Reject</button>
                                </div>
                            </div>
                        ))}
                        {pendingReceived.length===0 && <p className="text-xs text-slate-500 mt-2">No pending.</p>}
                    </div>

                    <div className="bg-white border rounded-xl p-4">
                        <h3 className="font-bold text-sm">Sent Requests ({pendingSent.length})</h3>
                        {pendingSent.map(r=>(
                            <div key={r.id} className="text-xs py-1 border-b">{r.receiver ? `${r.receiver.name} (${r.receiver.service_number})` : `Group ${r.group?.name}`} — <span className="text-amber-600">pending</span></div>
                        ))}
                        {pendingSent.length===0 && <p className="text-xs text-slate-500 mt-2">None.</p>}
                    </div>

                    {groupRequests.length>0 && (
                        <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <h3 className="font-bold text-sm">Group Join Requests ({groupRequests.length})</h3>
                            {groupRequests.map(r=>(
                                <div key={r.id} className="bg-white border rounded p-2 mt-2 text-xs">
                                    <div><b>{r.sender?.name}</b> wants to join <b>{r.group?.name}</b></div>
                                    <div className="flex gap-2 mt-2">
                                        <button onClick={()=>router.post(`/friends/requests/${r.id}/accept`)} className="bg-green-600 text-white px-3 py-1 rounded">Accept</button>
                                        <button onClick={()=>router.post(`/friends/requests/${r.id}/reject`)} className="bg-red-600 text-white px-3 py-1 rounded">Reject</button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <div className="bg-white border rounded-xl p-4 mt-6">
                <h3 className="font-semibold text-sm">Request to Join a Group</h3>
                <p className="text-xs text-slate-500">Find a group in Chat, then request to join if not a member.</p>
                <div className="flex gap-2 mt-3">
                    <input placeholder="Group ID (see Chat)" value={msg['join_group']||''} onChange={e=>setMsg({...msg, ['join_group']: e.target.value})} className="border rounded px-3 py-2 text-sm flex-1" />
                    <button onClick={()=>sendGroupJoin(parseInt(msg['join_group']))} className="bg-slate-800 text-white px-4 py-2 rounded text-sm">Request Join</button>
                </div>
            </div>
        </Layout>
    );
}
