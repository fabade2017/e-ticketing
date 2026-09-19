import Layout from '../Components/Layout';
import { Link } from '@inertiajs/react';

export default function About() {
    return (
        <Layout>
            <h1 className="text-3xl font-bold text-[#0b3d0b]">About NA E-Ticket</h1>
            <p className="text-slate-600 mt-2">Proposal implementation for the Nigerian Army — 2026.</p>
            <div className="bg-white p-6 rounded-xl border mt-6">
                <h3 className="font-bold">Project Genesis</h3>
                <p className="text-sm text-slate-600 mt-2">Based on the paper “Development of an E-Ticket (Support Ticketing Platform) Web Application for the Nigerian Army” — addressing the critical gap where soldiers at remote/border areas lack reliable GSM to reach the existing call centre. The web platform provides resilient, offline-capable ticketing for welfare, logistics, medical, equipment and operations requests, with modular integration to personnel databases.</p>
            </div>
            <div className="bg-white p-6 rounded-xl border mt-4">
                <h3 className="font-bold">Objectives (Paper §3)</h3>
                <ul className="list-disc ml-5 text-sm text-slate-600 mt-2 space-y-1">
                    <li>Streamline ticketing for soldiers and administrators.</li>
                    <li>Enhance efficiency & accuracy: manual → electronic, reducing errors/delays.</li>
                    <li>Secure, user-friendly, data-confidential platform.</li>
                </ul>
            </div>
            <div className="bg-white p-6 rounded-xl border mt-4">
                <h3 className="font-bold">Key Features Delivered</h3>
                <ul className="list-disc ml-5 text-sm text-slate-600 mt-2 space-y-1">
                    <li>Ticket generation / tracking / reporting + offline PWA queue.</li>
                    <li>Dual Face Auth: <b>Local</b> (face-api.js on-premise) ↔ <b>Cloud</b> (AWS/Azure) toggleable by SuperAdmin.</li>
                    <li>RBAC: SuperAdmin / Admin / Support / Soldier with Spatie permissions.</li>
                    <li>Bulk user import (CSV) + Roles & Permissions CRUD.</li>
                    <li>Encrypted embeddings, audit logs, personnel DB sync ready.</li>
                </ul>
                <div className="mt-4 flex gap-3">
                    <Link href="/help" className="bg-[#0b3d0b] text-white px-4 py-2 rounded">How It Works</Link>
                    <Link href="/docs" className="border px-4 py-2 rounded">Documentation</Link>
                    <a href="/README.md" target="_blank" className="text-sm underline self-center">README.md</a>
                </div>
            </div>
            <div className="bg-white p-6 rounded-xl border mt-4">
                <h3 className="font-bold">Tech Stack</h3>
                <p className="text-sm text-slate-600 mt-2"><code>Laravel 13 + React 19 + Inertia 3 + Tailwind 4 + face-api.js (tfjs) + Spatie Permission + SQLite/MySQL + PWA</code>. Hosted on Army private cloud / intranet. Follows NDPA, AES-256.</p>
            </div>
            <div className="text-xs text-slate-500 mt-6">© Nigerian Army • Built for operational effectiveness • Contact: HQ ICT • Version: 1.0.0</div>
        </Layout>
    );
}
