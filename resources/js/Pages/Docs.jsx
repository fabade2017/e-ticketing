import Layout from '../Components/Layout';

export default function Docs() {
    const sections = [
        ['1. Installation', `composer install && npm install\ncp .env.example .env && php artisan key:generate\nphp artisan migrate --seed && npm run build\nphp artisan serve --port=8004`],
        ['2. Roles & Permissions (Spatie)', `Roles: SuperAdmin, Admin, Support, Soldier\nPermissions: manage tickets, manage users, toggle face mode, view reports\nSuperAdmin → /admin/roles to create/edit/delete roles & assign permissions.\nMiddleware: role:SuperAdmin in bootstrap/app.php:17`],
        ['3. Face Auth', `Config: config/face_auth.php / .env FACE_AUTH_MODE=local|cloud, FACE_AUTH_THRESHOLD=0.6\nModels: public/models/* (6 files, ~13MB) already downloaded.\nLocal: face-api.js extracts 128-d vector in browser → cosine similarity on server.\nCloud: same vector delegated to AWS Rekognition/Azure Face (set credentials).\nToggle: SuperAdmin → /admin/settings → instant switch via SystemSetting.`],
        ['4. Bulk Upload', `CSV headers: name,email,service_number,rank,unit,phone,role\nExample: Soldier One,soldier2@army.mil.ng,NA/SL/004,Pte,Outpost B,080...,Soldier\nEndpoint: POST /admin/users/bulk (multipart/form-data, file=csv). SuperAdmin only.\nValidation: unique email/service_number, role must exist.`],
        ['5. Tickets', `Categories: welfare,logistics,medical,equipment,operations,other\nPriorities: low,medium,high,urgent | Statuses: open,in_progress,resolved,closed\nOffline: Create form queues to localStorage when !navigator.onLine.`],
        ['6. API & Integration', `POST /face/verify {service_number, embedding[128]} → login\nPOST /face/enroll {user_id, embedding[128]} → enroll\nGET /admin/users, POST /admin/users/{user}/role, GET /admin/roles`],
        ['7. Deployment', `Army intranet: php artisan config:cache, storage:link, queue for ticket sync, HTTPS required for camera.\nPWA: public/manifest.json + sw.js`],
    ];
    return (
        <Layout>
            <h1 className="text-3xl font-bold text-[#0b3d0b]">Full Documentation</h1>
            <p className="text-slate-600 mt-2">Generated from codebase — RBAC, bulk upload, face auth, offline.</p>
            <div className="mt-4 flex gap-3">
                <a href="/docs.pdf" target="_blank" className="bg-[#0b3d0b] text-white px-4 py-2 rounded text-sm">Download PDF (soon)</a>
                <a href="https://github.com/anomalyco/opencode" target="_blank" className="border px-4 py-2 rounded text-sm">Help & Feedback</a>
                <span className="text-xs self-center text-slate-500">Bulk upload generates import report at /admin/users/bulk</span>
            </div>
            <div className="mt-6 space-y-4">
                {sections.map(([title, body]) => (
                    <div key={title} className="bg-white p-6 rounded-xl border">
                        <h3 className="font-bold">{title}</h3>
                        <pre className="bg-slate-900 text-slate-100 p-4 rounded mt-3 text-xs overflow-auto whitespace-pre-wrap">{body}</pre>
                    </div>
                ))}
            </div>
            <div className="bg-yellow-50 border border-yellow-200 p-4 rounded-xl mt-6 text-sm">
                <b>Bulk Upload → Documentation Link:</b> After CSV import, the report page at <code>/admin/users/bulk</code> shows success/skipped rows with download link to <code>storage/app/exports/bulk_import_*.csv</code> and auto-links here to <code>/docs</code> for full spec.
            </div>
        </Layout>
    );
}
