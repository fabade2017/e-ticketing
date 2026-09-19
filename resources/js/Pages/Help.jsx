import Layout from '../Components/Layout';
import { Link } from '@inertiajs/react';

export default function Help() {
    return (
        <Layout>
            <h1 className="text-3xl font-bold text-[#0b3d0b]">Help — How NA E-Ticket Works</h1>
            <p className="text-slate-600 mt-2">Role-based story of the Nigerian Army Support Ticketing Platform. Offline-first, Face-ID secured, RBAC governed.</p>

            <div className="mt-6 grid md:grid-cols-3 gap-4">
                <div className="bg-white p-5 rounded-xl border shadow-sm">
                    <h3 className="font-bold">1. The Field Reality</h3>
                    <p className="text-sm text-slate-600 mt-2">Soldiers at border outposts often have no GSM signal. Instead of calling the HQ call centre (which fails), they open <b>NA E-Ticket</b> as a PWA — it works offline, queues the ticket locally, and syncs when any network (2G/Starlink) appears.</p>
                </div>
                <div className="bg-white p-5 rounded-xl border shadow-sm">
                    <h3 className="font-bold">2. Face Identity</h3>
                    <p className="text-sm text-slate-600 mt-2"><b>Local mode</b> (default): face-api.js extracts a 128-float embedding in the browser — no photo leaves the device. Verified on Army intranet via cosine similarity. <b>Cloud mode</b> (SuperAdmin toggle): delegates to AWS Rekognition / Azure Face for HQ central verification.</p>
                </div>
                <div className="bg-white p-5 rounded-xl border shadow-sm">
                    <h3 className="font-bold">3. Accountability</h3>
                    <p className="text-sm text-slate-600 mt-2">Every ticket gets <code>NA-YYYY-XXXXXXXX</code>, SLA timers, audit logs, and encrypted storage. Command sees transparent analytics, not phone logs.</p>
                </div>
            </div>

            <div className="mt-8 bg-white border rounded-xl overflow-hidden">
                <div className="bg-[#0b3d0b] text-white px-6 py-3 font-semibold">Role-Based Workflow</div>
                <div className="divide-y">
                    {[
                        ['Soldier (Pte – WO)', 'Creator', 'Can: create ticket (welfare/logistics/medical/equipment/operations), track own tickets, login via Service No + Password or Face ID. Cannot see others’ tickets. Offline queue auto-sync. Typical story: <i>“Borehole pump faulty at Outpost A” → create High ticket → synced at next patrol → Support assigns → resolved.”</i>', 'bg-green-50'],
                        ['Support / Helpdesk', 'Resolver', 'Can: view all tickets, assign, change status open→in_progress→resolved→closed, set priority. Cannot manage users or toggle face mode. Story: triages medical evacuation requests first (urgent).', 'bg-blue-50'],
                        ['Admin (Col / Unit Admin)', 'Manager', 'Can: everything Support can + manage users within unit, view reports, enroll faces for soldiers. Cannot toggle Local/Cloud. Story: enrolls new recruits’ faces at HQ, assigns Support to sector.', 'bg-amber-50'],
                        ['SuperAdmin (Brig Gen / HQ)', 'Governor', 'Can: everything + full RBAC (create/edit/delete roles & permissions), bulk upload users (CSV), toggle Face Auth mode Local↔Cloud and enable/disable, set cloud provider (AWS/Azure), view audit. Only role that can switch face mode instantly — change reflects in real-time on login page. Story: during border operation sets mode=local for offline; at HQ switches to cloud for higher accuracy.', 'bg-yellow-100'],
                    ].map(([role, subtitle, desc, bg]) => (
                        <div key={role} className={`p-6 flex gap-4 ${bg}`}>
                            <div className="shrink-0 w-10 h-10 rounded-full bg-[#0b3d0b] text-white flex items-center justify-center font-bold">{role[0]}</div>
                            <div>
                                <div className="font-bold">{role} — {subtitle}</div>
                                <div className="text-sm text-slate-700 mt-1" dangerouslySetInnerHTML={{__html: desc}} />
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            <div className="mt-8 grid md:grid-cols-2 gap-6">
                <div className="bg-white p-6 rounded-xl border">
                    <h3 className="font-bold">Step-by-Step (Soldier)</h3>
                    <ol className="list-decimal ml-5 text-sm text-slate-600 mt-3 space-y-1">
                        <li>Login: Service No <code>NA/SL/003</code> + Password or Face Verify (needs prior enrollment by Admin).</li>
                        <li>Dashboard → Create Ticket → choose category/priority/location → Submit. If offline, it queues in browser.</li>
                        <li>Track at Tickets → status updates via assignment.</li>
                        <li>When resolved, record stays for audit.</li>
                    </ol>
                </div>
                <div className="bg-white p-6 rounded-xl border">
                    <h3 className="font-bold">Step-by-Step (SuperAdmin)</h3>
                    <ol className="list-decimal ml-5 text-sm text-slate-600 mt-3 space-y-1">
                        <li>Users → Bulk Upload CSV (name, email, service_number, rank, unit, role) → preview → import.</li>
                        <li>Roles & Permissions → create role (e.g. Intel) → assign permissions (manage tickets, etc.) → assign to users.</li>
                        <li>Settings → toggle Face Auth Local/Cloud, enable/disable, choose provider.</li>
                        <li>Admin → enroll face: enter User ID → Capture for Enrollment (needs camera + models at /models).</li>
                    </ol>
                </div>
            </div>

            <div className="mt-8 bg-white p-6 rounded-xl border">
                <h3 className="font-bold">Security & Offline Notes</h3>
                <ul className="list-disc ml-5 text-sm text-slate-600 mt-2 space-y-1">
                    <li>Embeddings encrypted (AES-256 via Laravel cipher), not photos.</li>
                    <li>RBAC via <code>spatie/laravel-permission</code> — every route guarded by <code>role</code> / <code>permission</code> middleware.</li>
                    <li>PWA: tickets + auth state cached; sync uses background queue when online.</li>
                    <li>Audit: who changed status, when, from which IP.</li>
                </ul>
                <div className="mt-4 flex gap-3">
                    <Link href="/about" className="bg-[#0b3d0b] text-white px-4 py-2 rounded">About Project →</Link>
                    <Link href="/docs" className="border px-4 py-2 rounded">Full Documentation →</Link>
                </div>
            </div>
        </Layout>
    );
}
