import { useForm, router, Link } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';

export default function KbIndex({ articles, filters, canManage }) {
    const [search, setSearch] = useState(filters?.search || '');
    const { data, setData, post, processing } = useForm({ title:'', category:'general', body:'' });
    const doSearch = ()=> router.get('/kb', { search }, { preserveState:true });
    return (
        <Layout>
            <h1 className="text-2xl font-bold">Knowledge Base</h1>
            <p className="text-sm text-slate-600">Self-service FAQ — reduces repeat tickets. Search before creating ticket. {canManage && 'Admin can add.'}</p>
            <div className="flex gap-2 mt-4">
                <input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search articles" className="flex-1 border rounded px-3 py-2" onKeyDown={e=>e.key==='Enter'&&doSearch()} />
                <button onClick={doSearch} className="bg-slate-800 text-white px-4 py-2 rounded">Search</button>
            </div>
            {canManage && (
                <form onSubmit={e=>{e.preventDefault(); post('/kb');}} className="bg-white border rounded-xl p-4 mt-4 space-y-2">
                    <input value={data.title} onChange={e=>setData('title',e.target.value)} placeholder="Title" className="w-full border rounded px-3 py-2" />
                    <input value={data.category} onChange={e=>setData('category',e.target.value)} placeholder="Category" className="w-full border rounded px-3 py-2" />
                    <textarea value={data.body} onChange={e=>setData('body',e.target.value)} placeholder="Body" rows={3} className="w-full border rounded px-3 py-2" />
                    <button disabled={processing} className="bg-[#0b3d0b] text-white px-4 py-2 rounded">Add Article</button>
                </form>
            )}
            <div className="grid md:grid-cols-2 gap-4 mt-6">
                {articles.data.map(a=>(
                    <div key={a.id} className="bg-white border rounded-xl p-4">
                        <div className="text-xs bg-slate-100 px-2 py-0.5 rounded inline-block">{a.category}</div>
                        <div className="font-bold mt-2">{a.title}</div>
                        <div className="text-sm text-slate-600 mt-1 whitespace-pre-wrap">{a.body}</div>
                        <div className="text-xs text-slate-400 mt-2">by {a.author?.name} • {new Date(a.created_at).toLocaleDateString()}</div>
                    </div>
                ))}
            </div>
            {articles.data.length===0 && <p className="text-center text-slate-500 mt-6">No articles.</p>}
        </Layout>
    );
}
