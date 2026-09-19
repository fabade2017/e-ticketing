import { Link, usePage } from '@inertiajs/react';
import Layout from '../Components/Layout';

export default function Welcome() {
    const { auth, branding } = usePage().props;
    const appName = branding?.app_name || 'Nigerian Army E-Ticketing Platform';
    const appMotto = branding?.app_motto || 'Secure • RBAC • Face ID Dual-Mode • Offline PWA';
    const appLogo = branding?.app_logo ? `/storage/${branding.app_logo}` : null;
    return (
        <Layout>
            <div className="py-12 text-center">
                {appLogo && <img src={appLogo} alt="logo" className="w-20 h-20 mx-auto object-contain bg-white rounded-xl p-2 border shadow-sm" />}
                <div className="inline-flex items-center gap-2 bg-green-50 text-green-800 px-3 py-1 rounded-full text-xs border border-green-200 mt-4">● {appMotto}</div>
                <h1 className="text-4xl font-bold mt-6 text-slate-900">{appName}</h1>
                <p className="text-slate-600 mt-4 max-w-2xl mx-auto">Streamlined ticketing for personnel in barracks, field and border deployments. Works offline, syncs when connected. Face identification with SuperAdmin-toggleable Local / Cloud mode.</p>
                <div className="mt-8 flex gap-4 justify-center">
                    {auth?.user ? <Link href="/dashboard" className="bg-[#0b3d0b] text-white px-8 py-3 rounded-lg font-semibold">Go to Dashboard</Link> : <><Link href="/login" className="bg-[#0b3d0b] text-white px-8 py-3 rounded-lg font-semibold">Soldier Login</Link><a href="#features" className="border px-8 py-3 rounded-lg">Learn More</a></>}
                </div>
                <div id="features" className="grid md:grid-cols-3 gap-6 mt-16 text-left">
                    {[
                        ['Ticket Lifecycle', 'Generate, track, assign, resolve — with SLA timers and audit logs. Works offline for remote outposts.'],
                        ['Dual Face Auth', 'On-Premise (face-api.js) for offline/border + Cloud (AWS Rekognition/Azure) for HQ. SuperAdmin toggles instantly.'],
                        ['RBAC + Security', 'SuperAdmin > Admin > Support > Soldier. Encrypted embeddings, audit trail, integration with personnel DB.'],
                    ].map(([t,d]) => (
                        <div key={t} className="bg-white p-6 rounded-xl border shadow-sm">
                            <h3 className="font-semibold">{t}</h3><p className="text-sm text-slate-600 mt-2">{d}</p>
                        </div>
                    ))}
                </div>
            </div>
        </Layout>
    );
}
