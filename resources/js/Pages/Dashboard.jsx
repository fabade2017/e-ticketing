import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../Components/Layout';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, LineChart, Line, CartesianGrid, Legend } from 'recharts';

const COLORS = ['#0b3d0b','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];

export default function Dashboard({ stats, ai, recent }) {
    const { auth, branding } = usePage().props;
    const appName = branding?.app_name || 'NA E-Ticket';
    const isSoldier = stats?.isSoldier;
    const roles = auth.user?.roles || [];
    const [q, setQ] = useState('');
    const [answer, setAnswer] = useState(null);
    const [loadingAi, setLoadingAi] = useState(false);
    const formatAnswer = (txt)=>{
        if (!txt) return null;
        // simple markdown: **bold** -> <b>, line breaks preserved via pre-wrap, bullets keep
        const parts = txt.split(/(\*\*.*?\*\*)/g);
        return parts.map((p,i)=> p.startsWith('**') && p.endsWith('**') ? <b key={i}>{p.slice(2,-2)}</b> : <span key={i}>{p}</span>);
    };

    const askAi = async () => {
        if (!q.trim()) return;
        setLoadingAi(true);
        const res = await fetch('/ai/ask', { method: 'POST', headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ q }) });
        const j = await res.json();
        setAnswer(j.answer);
        setLoadingAi(false);
    };

    if (isSoldier) {
        return (
            <Layout>
                <h1 className="text-2xl font-bold">Welcome, {auth.user?.rank} {auth.user?.name}</h1>
                <p className="text-slate-500 text-sm">Soldier • {auth.user?.unit || '—'} • {auth.user?.service_number} • Face: {auth.user?.face_enrolled_at ? 'Enrolled' : 'Pending — contact Admin'}</p>
                <div className="bg-amber-50 border border-amber-200 p-3 rounded mt-4 text-sm">You see only your tickets. For help, see <Link href="/help" className="underline">Help</Link>. Officers directory and analytics are restricted to Admin/Support/SuperAdmin.</div>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                    {[
                        ['My Tickets', stats.my_total, 'border-l-4 border-slate-800'],
                        ['Open', stats.my_open, 'border-l-4 border-amber-500'],
                        ['In Progress', stats.my_progress, 'border-l-4 border-blue-500'],
                        ['Resolved', stats.my_resolved, 'border-l-4 border-green-500'],
                    ].map(([k,v,cls])=>(
                        <div key={k} className={`bg-white border rounded-xl p-4 shadow-sm ${cls}`}>
                            <div className="text-xs text-slate-500 uppercase tracking-wide">{k}</div>
                            <div className="text-2xl font-bold mt-1">{v}</div>
                        </div>
                    ))}
                </div>
                <div className="mt-6 grid md:grid-cols-2 gap-4">
                    <Link href="/tickets/create" className="bg-[#0b3d0b] text-white p-8 rounded-xl font-semibold block text-center text-lg">+ Create New Ticket<br/><span className="text-xs font-normal opacity-80">Works offline — queues when no signal</span></Link>
                    <Link href="/tickets" className="bg-white border p-8 rounded-xl font-semibold block text-center">My Tickets →<br/><span className="text-xs font-normal text-slate-500">Track your requests</span></Link>
                </div>
                <div className="bg-white border rounded-xl p-5 mt-6">
                    <h3 className="font-semibold">My Recent Tickets</h3>
                    <div className="divide-y mt-3">
                        {recent?.length ? recent.map(t=>(
                            <Link key={t.id} href={`/tickets/${t.id}`} className="flex justify-between py-2 hover:bg-slate-50 px-2 rounded text-sm">
                                <span className="font-mono text-xs">{t.ticket_number} — {t.title.slice(0,40)}</span>
                                <span className="text-xs px-2 py-0.5 rounded bg-slate-100">{t.status}</span>
                            </Link>
                        )) : <p className="text-sm text-slate-500">No tickets yet — create your first.</p>}
                    </div>
                </div>
            </Layout>
        );
    }

    const statusData = Object.entries(stats.byStatus || {open: stats.open, in_progress: stats.progress, resolved: stats.resolved, closed: stats.closed}).map(([k,v])=>({name:k, value:v})).filter(d=>d.value>0);
    const catData = Object.entries(stats.byCategory || {}).map(([k,v])=>({name:k, value:v}));
    const priorityData = Object.entries(stats.byPriority || {}).map(([k,v])=>({name:k, value:v}));
    const trendData = (stats.trend || []).map(d=>({date: d.date?.slice(5) || d.date, count: d.count}));

    return (
        <Layout>
            <div className="flex justify-between items-start">
                <div>
                    <h1 className="text-2xl font-bold">Command Dashboard — {auth.user?.rank} {auth.user?.name}</h1>
                    <p className="text-slate-500 text-sm">{appName} • {roles.join(', ')} • {auth.user?.unit || '—'} • {auth.user?.service_number}</p>
                </div>
                <Link href="/officers" className="bg-[#0b3d0b] text-white px-4 py-2 rounded text-sm hidden md:block">View Officers & Faces →</Link>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mt-6">
                {[
                    ['Total Tickets', stats.total, 'border-l-4 border-slate-800'],
                    ['Open', stats.open, 'border-l-4 border-amber-500'],
                    ['In Progress', stats.progress ?? stats.byStatus?.in_progress ?? 0, 'border-l-4 border-blue-500'],
                    ['Resolved', stats.resolved ?? 0, 'border-l-4 border-green-500'],
                    ['Urgent Open', stats.urgentOpen ?? 0, 'border-l-4 border-red-500'],
                    ['Face Enrolled', `${stats.face_enrolled} (${stats.faceCoverage ?? 0}%)`, 'border-l-4 border-emerald-600'],
                ].map(([k,v,cls])=>(
                    <div key={k} className={`bg-white border rounded-xl p-4 shadow-sm ${cls}`}>
                        <div className="text-xs text-slate-500 uppercase tracking-wide">{k}</div>
                        <div className="text-2xl font-bold mt-1">{v}</div>
                    </div>
                ))}
            </div>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3">
                {[
                    ['Officers', stats.users, `→ /officers`],
                    ['Categories', Object.keys(stats.byCategory||{}).length, 'welfare, logistics...'],
                ].map(([k,v,sub])=>(
                    <div key={k} className="bg-white border rounded-xl p-4">
                        <div className="text-xs text-slate-500">{k}</div><div className="text-xl font-bold">{v}</div><div className="text-xs text-slate-400">{sub}</div>
                    </div>
                ))}
            </div>

            <div className="grid md:grid-cols-2 gap-6 mt-6">
                <div className="bg-white border rounded-xl p-5">
                    <h3 className="font-semibold text-sm">Tickets by Status</h3>
                    <ResponsiveContainer width="100%" height={220}>
                        <BarChart data={statusData}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="name" fontSize={11} /><YAxis fontSize={11} /><Tooltip /><Bar dataKey="value" fill="#0b3d0b" radius={[6,6,0,0]} /></BarChart>
                    </ResponsiveContainer>
                </div>
                <div className="bg-white border rounded-xl p-5">
                    <h3 className="font-semibold text-sm">Tickets by Category</h3>
                    {catData.length ? <ResponsiveContainer width="100%" height={220}><PieChart><Pie data={catData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={80} label>{catData.map((e,i)=><Cell key={i} fill={COLORS[i%COLORS.length]} />)}</Pie><Tooltip /><Legend /></PieChart></ResponsiveContainer> : <p className="text-sm text-slate-500 mt-10">No category data yet</p>}
                </div>
                <div className="bg-white border rounded-xl p-5">
                    <h3 className="font-semibold text-sm">Priority Breakdown</h3>
                    <ResponsiveContainer width="100%" height={220}><BarChart data={priorityData} layout="vertical"><CartesianGrid strokeDasharray="3 3" /><XAxis type="number" fontSize={11} /><YAxis type="category" dataKey="name" fontSize={11} width={70} /><Tooltip /><Bar dataKey="value" fill="#f59e0b" radius={[0,6,6,0]} /></BarChart></ResponsiveContainer>
                </div>
                <div className="bg-white border rounded-xl p-5">
                    <h3 className="font-semibold text-sm">14-Day Trend</h3>
                    {trendData.length ? <ResponsiveContainer width="100%" height={220}><LineChart data={trendData}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="date" fontSize={10} /><YAxis fontSize={11} /><Tooltip /><Line type="monotone" dataKey="count" stroke="#0b3d0b" strokeWidth={2} dot /></LineChart></ResponsiveContainer> : <p className="text-sm text-slate-500 mt-10">No trend yet</p>}
                </div>
            </div>

            {ai && (
                <div className="bg-gradient-to-br from-[#0b3d0b] to-emerald-900 text-white rounded-xl p-6 mt-6">
                    <div className="flex justify-between items-center">
                        <h3 className="font-bold flex items-center gap-2">🤖 AI Insights — Local Rule-Based (extensible to LLM)</h3>
                        <span className="text-xs bg-white/20 px-2 py-1 rounded">{new Date(ai.generated_at).toLocaleString()}</span>
                    </div>
                    <ul className="list-disc ml-5 mt-3 space-y-1 text-sm">
                        {ai.insights.map((ins,i)=><li key={i} className="opacity-90">{ins}</li>)}
                    </ul>
                    <div className="mt-4 bg-white/10 rounded p-3 flex gap-2">
                        <input value={q} onChange={e=>setQ(e.target.value)} placeholder="Ask AI: urgent tickets? face coverage?" className="flex-1 bg-white text-black rounded px-3 py-2 text-sm" onKeyDown={e=>e.key==='Enter'&&askAi()} />
                        <button onClick={askAi} disabled={loadingAi} className="bg-yellow-500 text-black px-4 py-2 rounded font-semibold disabled:opacity-50">{loadingAi ? 'Thinking...' : 'Ask AI'}</button>
                    </div>
                    {answer && (
                        <div className="mt-3 bg-white text-slate-800 rounded p-4 text-sm leading-relaxed">
                            <div className="font-bold text-[#0b3d0b] mb-2">🤖 Organised Response</div>
                            <div className="whitespace-pre-wrap break-words" style={{whiteSpace:'pre-wrap'}}>{formatAnswer(answer)}</div>
                            <div className="text-xs text-slate-400 mt-2">Structured: Summary • Key Stats • Action Checklist • Risks</div>
                        </div>
                    )}
                </div>
            )}

            <div className="grid md:grid-cols-3 gap-6 mt-6">
                <div className="md:col-span-2 bg-white border rounded-xl p-5">
                    <h3 className="font-semibold">Recent Tickets</h3>
                    <div className="divide-y mt-3">
                        {recent?.length ? recent.map(t=>(
                            <Link key={t.id} href={`/tickets/${t.id}`} className="flex justify-between py-2 hover:bg-slate-50 px-2 rounded text-sm">
                                <span className="font-mono text-xs">{t.ticket_number} — {t.title.slice(0,40)}</span>
                                <span className="text-xs px-2 py-0.5 rounded bg-slate-100">{t.status}</span>
                            </Link>
                        )) : <p className="text-sm text-slate-500">No tickets</p>}
                    </div>
                    <Link href="/tickets" className="text-sm text-[#0b3d0b] underline mt-3 inline-block">View all →</Link>
                </div>
                <div className="space-y-3">
                    <Link href="/tickets/create" className="bg-[#0b3d0b] text-white p-6 rounded-xl font-semibold block text-center">+ Create Ticket</Link>
                    <Link href="/officers" className="bg-white border p-6 rounded-xl font-semibold block text-center">👥 Officers & Faces</Link>
                </div>
            </div>
        </Layout>
    );
}
