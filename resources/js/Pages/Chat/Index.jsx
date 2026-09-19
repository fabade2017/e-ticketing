import { useForm, usePage, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import Layout from '../../Components/Layout';

export default function ChatIndex({ groups: groupsProp, selected, messages: initialMessages, allUsers: allUsersProp, canCreateGroup, filters }) {
    const { auth, flash } = usePage().props;
    const [groups, setGroups] = useState(groupsProp || []);
    const allUsers = allUsersProp || [];
    const [msgs, setMsgs] = useState(initialMessages || []);
    const [text, setText] = useState('');
    const [showCreate, setShowCreate] = useState(false);
    const [showMembers, setShowMembers] = useState(false);
    const bottomRef = useRef(null);
    const { data, setData, post, processing, errors } = useForm({ name: '', description: '', is_announcement: false, member_ids: [] });
    const [addIds, setAddIds] = useState([]);
    const [search, setSearch] = useState(filters?.search || '');
    const [userSearch, setUserSearch] = useState(filters?.user_search || '');
    const [msgSearch, setMsgSearch] = useState('');

    useEffect(()=> setGroups(groupsProp || []), [groupsProp]);

    useEffect(()=> {
        setMsgs(Array.isArray(initialMessages) ? initialMessages : []);
        // clear unread badge locally for this group when opened
        if (selected?.id) setGroups(prev=> prev.map(g=> g.id===selected.id ? {...g, unread_count:0} : g));
    }, [selected?.id]);
    useEffect(()=>{
        try { bottomRef.current?.scrollIntoView?.({behavior:'smooth'}); } catch(e) {}
        // also clear unread in this view after reading
        if (selected?.id) setGroups(prev=> prev.map(g=> g.id===selected.id ? {...g, unread_count:0} : g));
    }, [msgs]);

    useEffect(()=>{
        if (!selected?.id) return;
        let cancelled = false;
        const poll = async ()=>{
            try {
                const res = await fetch(`/chat/groups/${selected.id}/messages`, { headers:{'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest'}});
                if (!cancelled && res.ok) {
                    const data = await res.json();
                    if (Array.isArray(data)) {
                        setMsgs(data);
                        // mark read locally after poll
                        setGroups(prev=> prev.map(g=> g.id===selected.id ? {...g, unread_count:0} : g));
                    }
                }
            } catch(e) { /* ignore */ }
        };
        const id = setInterval(poll, 5000);
        return ()=> { cancelled = true; clearInterval(id); };
    }, [selected?.id]);

    const selectGroup = id => router.get('/chat', { group_id: id }, { preserveState:false, preserveScroll:true });
    const doSearch = () => router.get('/chat', { search, user_search: userSearch, group_id: selected?.id }, { preserveState:true, replace:true });
    const createGroup = e=>{ e.preventDefault(); post('/chat/groups', { onSuccess: ()=> setShowCreate(false) }); };
    const send = e=>{
        e.preventDefault();
        if (!text.trim() || !selected?.can_post) return;
        router.post(`/chat/groups/${selected.id}/messages`, { message: text }, { preserveScroll:true, onSuccess: ()=> setText('') });
    };
    const addMembers = ()=> router.post(`/chat/groups/${selected.id}/members`, { member_ids: addIds }, { onSuccess: ()=> setAddIds([]) });

    const filteredMsgs = msgSearch ? msgs.filter(m=> m.message.toLowerCase().includes(msgSearch.toLowerCase()) || m.user.name.toLowerCase().includes(msgSearch.toLowerCase()) || m.user.service_number.toLowerCase().includes(msgSearch.toLowerCase())) : msgs;

    return (
        <Layout>
            <h1 className="text-2xl font-bold">Chat</h1>
            <p className="text-sm text-slate-600">Search by group, individual (name/rank) or number (service_number). Admin announcements are readonly.</p>
            {flash?.success && <div className="bg-green-50 border border-green-200 text-green-800 p-2 rounded mt-3 text-sm">{flash.success}</div>}

            <div className="bg-white border rounded-xl p-3 mt-4 flex flex-wrap gap-3 items-end">
                <div className="flex-1 min-w-[180px]">
                    <label className="text-xs font-medium">Search groups / description / creator</label>
                    <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="e.g. Sector 2, HQ Announcement" className="w-full border rounded px-3 py-2 mt-1 text-sm" onKeyDown={e=>e.key==='Enter'&&doSearch()} />
                </div>
                <div className="flex-1 min-w-[180px]">
                    <label className="text-xs font-medium">Search individual / number</label>
                    <input value={userSearch} onChange={e=>setUserSearch(e.target.value)} placeholder="e.g. NA/SL/003 or Pte Musa" className="w-full border rounded px-3 py-2 mt-1 text-sm" onKeyDown={e=>e.key==='Enter'&&doSearch()} />
                </div>
                <button onClick={doSearch} className="bg-slate-800 text-white px-6 py-2 rounded text-sm">Search</button>
                <button onClick={()=>{ setSearch(''); setUserSearch(''); router.get('/chat'); }} className="border px-4 py-2 rounded text-sm">Reset</button>
            </div>

            <div className="grid md:grid-cols-3 gap-4 mt-4 h-[65vh]">
                <div className="bg-white border rounded-xl flex flex-col">
                    <div className="p-3 border-b flex justify-between items-center">
                        <span className="font-semibold text-sm">Groups ({groups.length})</span>
                        <div className="flex gap-2">
                            <span className="text-xs bg-red-600 text-white px-2 py-1 rounded hidden" id="chat-unread-badge"></span>
                            {canCreateGroup && <button onClick={()=>setShowCreate(!showCreate)} className="bg-[#0b3d0b] text-white px-3 py-1 rounded text-xs">+ New</button>}
                        </div>
                    </div>
                    {showCreate && (
                        <form onSubmit={createGroup} className="p-3 border-b space-y-2 bg-amber-50">
                            <input value={data.name} onChange={e=>setData('name', e.target.value)} placeholder="Group name" className="w-full border rounded px-2 py-1 text-sm" />
                            <input value={data.description} onChange={e=>setData('description', e.target.value)} placeholder="Description" className="w-full border rounded px-2 py-1 text-sm" />
                            <label className="flex items-center gap-2 text-xs"><input type="checkbox" checked={data.is_announcement} onChange={e=>setData('is_announcement', e.target.checked)} /> Admin announcement (readonly)</label>
                            <div className="max-h-28 overflow-auto border rounded p-2 space-y-1 bg-white">
                                {allUsers.map(u=>(
                                    <label key={u.id} className="flex items-center gap-2 text-xs"><input type="checkbox" checked={data.member_ids.includes(u.id)} onChange={e=> setData('member_ids', e.target.checked ? [...data.member_ids, u.id] : data.member_ids.filter(x=>x!==u.id))} />{u.rank} {u.name} ({u.service_number})</label>
                                ))}
                            </div>
                            <button disabled={processing} className="w-full bg-slate-800 text-white py-1 rounded text-sm">Create</button>
                        </form>
                    )}
                    <div className="flex-1 overflow-auto divide-y">
                        {groups.length ? groups.map(g=>(
                            <button key={g.id} onClick={()=>selectGroup(g.id)} className={`w-full text-left p-3 hover:bg-slate-50 relative ${selected?.id===g.id ? 'bg-green-50 border-l-4 border-[#0b3d0b]' : ''}`}>
                                <div className="font-semibold text-sm flex items-center gap-2">{g.name} {g.is_announcement && <span className="text-xs bg-red-600 text-white px-1.5 py-0.5 rounded">READONLY</span>} {g.unread_count > 0 && <span className="ml-auto bg-red-600 text-white text-xs px-1.5 py-0.5 rounded-full">{g.unread_count}</span>}</div>
                                <div className="text-xs text-slate-500">{g.description || '—'} • {g.members_count} members {g.unread_count > 0 && <span className="text-red-600 font-bold">• {g.unread_count} new</span>}</div>
                                <div className="text-xs text-slate-400">by {g.creator?.name || '—'}</div>
                            </button>
                        )) : (
                            <div className="p-6 text-sm text-slate-500 text-center space-y-2">
                                <p>No groups yet.</p>
                                <p className="text-xs">{canCreateGroup ? 'Create one with + New or' : 'As Soldier, request via'} <a href="/friends" className="underline text-[#0b3d0b]">Friends → Add Colleague / Request Join</a></p>
                                <p className="text-xs">Search by group, individual or number (service_number) above.</p>
                            </div>
                        )}
                    </div>
                </div>

                <div className="md:col-span-2 bg-white border rounded-xl flex flex-col">
                    {!selected ? <div className="flex-1 flex items-center justify-center text-slate-500 text-sm">Select a group • Use search above by group/individual/number</div> : (
                        <>
                            <div className="p-3 border-b flex justify-between items-center">
                                <div>
                                    <div className="font-bold">{selected.name} {selected.is_announcement && <span className="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded ml-2">Admin readonly</span>}</div>
                                    <div className="text-xs text-slate-500">{selected.description} • {selected.members?.length} members</div>
                                </div>
                                <button onClick={()=>setShowMembers(!showMembers)} className="border px-3 py-1 rounded text-xs">Members</button>
                            </div>
                            {showMembers && (
                                <div className="p-3 border-b bg-slate-50">
                                    <div className="text-xs font-semibold">Members — search by number works here</div>
                                    <div className="flex flex-wrap gap-2 mt-2">
                                        {selected.members?.map(m=>(
                                            <span key={m.id} className="bg-white border px-2 py-1 rounded text-xs flex items-center gap-2">{m.rank} {m.name} ({m.service_number}) <button onClick={()=>router.delete(`/chat/groups/${selected.id}/members/${m.id}`)} className="text-red-600">×</button></span>
                                        ))}
                                    </div>
                                    <div className="flex gap-2 mt-3">
                                        <select multiple value={addIds} onChange={e=>setAddIds(Array.from(e.target.selectedOptions, o=>parseInt(o.value)))} className="flex-1 border rounded px-2 py-1 text-xs h-20">
                                            {allUsers.filter(u=>!selected.members.some(m=>m.id===u.id)).map(u=><option key={u.id} value={u.id}>{u.rank} {u.name} ({u.service_number})</option>)}
                                        </select>
                                        <button onClick={addMembers} className="bg-slate-800 text-white px-3 rounded text-xs">Add</button>
                                    </div>
                                </div>
                            )}
                            <div className="px-3 py-2 border-b flex gap-2">
                                <input value={msgSearch} onChange={e=>setMsgSearch(e.target.value)} placeholder="Search messages / sender / number" className="flex-1 border rounded px-3 py-1 text-xs" />
                                {msgSearch && <button onClick={()=>setMsgSearch('')} className="text-xs border px-3 rounded">Clear</button>}
                            </div>
                            <div className="flex-1 overflow-auto p-4 space-y-2 bg-slate-50">
                                {filteredMsgs.map(m=>(
                                    <div key={m.id} className={`max-w-[80%] rounded-lg px-3 py-2 text-sm ${m.user?.id===auth.user?.id ? 'ml-auto bg-[#0b3d0b] text-white' : 'bg-white border'}`}>
                                        <div className="text-xs opacity-70">{m.user?.rank || ''} {m.user?.name || 'Unknown'} {m.user?.service_number ? `(${m.user.service_number})` : ''} • {m.created_at || ''}</div>
                                        <div className="mt-1 break-words">{m.message || ''}</div>
                                    </div>
                                ))}
                                <div ref={bottomRef} />
                                {filteredMsgs.length===0 && <p className="text-center text-xs text-slate-500">No messages yet — send one.</p>}
                            </div>
                            <form onSubmit={send} className="p-3 border-t flex gap-2">
                                <input value={text} onChange={e=>setText(e.target.value)} placeholder={selected.can_post ? "Type message..." : "Readonly — only admins can post"} disabled={!selected.can_post} className="flex-1 border rounded px-3 py-2 text-sm disabled:bg-slate-100" />
                                <button disabled={!selected.can_post || !text.trim()} className="bg-[#0b3d0b] text-white px-6 py-2 rounded font-semibold disabled:opacity-50">Send</button>
                            </form>
                        </>
                    )}
                </div>
            </div>
        </Layout>
    );
}
