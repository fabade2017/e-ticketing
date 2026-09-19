import Layout from '../../Components/Layout';
import { Link } from '@inertiajs/react';

export default function ReportsIndex({ report, narrative, canGenerate }) {
    return (
        <Layout>
            <h1 className="text-2xl font-bold">World-Class Army Reports — SITREP</h1>
            <p className="text-sm text-slate-600">{report.classification} • {new Date(report.generated_at).toLocaleString()} • Readiness <span className={`px-2 py-0.5 rounded text-xs ${report.readiness==='GREEN'?'bg-green-600 text-white': report.readiness==='AMBER'?'bg-amber-500 text-black':'bg-red-600 text-white'}`}>{report.readiness}</span></p>

            <div className="grid md:grid-cols-3 gap-4 mt-6">
                {[
                    ['Total', report.stats.total, 'Tickets'],
                    ['Overdue', report.stats.overdue, 'SLA breach'],
                    ['Urgent Open', report.stats.urgentOpen, 'Priority'],
                    ['Avg Close', report.stats.avgCloseHours ? `${report.stats.avgCloseHours.toFixed(1)}h` : '—', 'World-class <12h'],
                    ['Face Coverage', `${report.stats.faceCoverage}%`, 'Target 95%'],
                    ['Officers', report.stats.officerCount, 'Depts:'+report.stats.deptCount],
                ].map(([k,v,sub])=>(
                    <div key={k} className="bg-white border rounded-xl p-4">
                        <div className="text-xs text-slate-500 uppercase">{k}</div><div className="text-xl font-bold">{v}</div><div className="text-xs text-slate-400">{sub}</div>
                    </div>
                ))}
            </div>

            <div className="bg-white border rounded-xl p-6 mt-6">
                <h3 className="font-bold">AI Insights — World-Class Standard (NATO/J4/NIST)</h3>
                <ul className="list-disc ml-5 mt-3 space-y-1 text-sm">
                    {report.insights.map((ins,i)=><li key={i}>{ins}</li>)}
                </ul>
            </div>

            <div className="bg-gradient-to-br from-[#0b3d0b] to-slate-900 text-white rounded-xl p-6 mt-6">
                <h3 className="font-bold">🤖 AI SITREP Narrative {canGenerate ? '(Groq)' : '(Local — add GROQ_API_KEY for Groq)'}</h3>
                {narrative ? <pre className="bg-white text-slate-800 rounded p-4 mt-3 text-sm whitespace-pre-wrap break-words">{narrative}</pre> : <p className="text-sm opacity-80 mt-3">No LLM narrative — rule-based insights above. Add Groq key to generate world-class SITREP.</p>}
                <div className="mt-4 flex gap-3">
                    <a href="/reports/download" className="bg-yellow-500 text-black px-4 py-2 rounded text-sm font-semibold">Download SITREP .txt</a>
                    <Link href="/dashboard" className="border border-white/30 px-4 py-2 rounded text-sm">Back to Dashboard</Link>
                </div>
            </div>

            <div className="bg-white border rounded-xl p-6 mt-6">
                <h3 className="font-bold">World-Class Benchmarks</h3>
                <ul className="list-disc ml-5 text-sm text-slate-600 mt-2 space-y-1">
                    <li><b>NATO APP-6:</b> Hotspot mapping, J4 logistics audit for top unit.</li>
                    <li><b>NIST 800-63:</b> 95% face enrollment for offline auth, liveness required.</li>
                    <li><b>SLA:</b> Urgent 4h, High 12h — auto-escalate overdue, avg close &lt;12h is world-class.</li>
                    <li><b>Forecast:</b> 25% growth — pre-stage, QRF.</li>
                </ul>
            </div>
        </Layout>
    );
}
