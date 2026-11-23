import Layout from '../Components/Layout';

export default function Docs() {
    const sections = [
        ['1. Installation', `composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed && npm run build
php artisan serve --port=8004

Set database and mail values in .env. Local seed credentials are for testing only; replace all default passwords before deployment.`],
        ['2. Roles & Permissions (Spatie)', `Roles: SuperAdmin, Admin, Support, Soldier plus care, maintenance and supply module roles.
SuperAdmin -> /admin/roles to create/edit roles and assign permissions.
Menu links and dashboard rooms are filtered by the permissions on your account.`],
        ['3. Face Auth', `Config: config/face_auth.php / .env FACE_AUTH_MODE=local|cloud, FACE_AUTH_THRESHOLD=0.6
Models: public/models/* (face-api.js model files).
Local: face-api.js extracts a 128-d vector in the browser; the server compares vectors.
Cloud: the same vector is delegated to the configured provider.
Toggle: SuperAdmin -> /admin/settings. Use HTTPS for camera access.`],
        ['4. Bulk Upload', `CSV headers: name,email,service_number,rank,unit,phone,role
Endpoint: POST /admin/users/bulk (multipart/form-data, file=csv). SuperAdmin only.
Validation: unique email/service_number and role must exist.
After import, the report lists success/skipped rows and links to the export file.`],
        ['5. Tickets & Dropdown Options', `Priorities: low,medium,high,urgent | Statuses: open,in_progress,resolved,closed
SLA: urgent 4h / high 12h / medium 24h / low 72h.
SuperAdmin -> Settings -> Dropdown Options manages categories, relationships, KB categories, supply units and ranks.
SuperAdmin -> Settings -> Care Registration Fees manages military, civilian and dependant base fees; existing registrations keep their recorded amounts.
Web offline ticket form keeps a local draft but does not replay it yet.
Mobile ticket creation queues offline and replays on Sync.`],
        ['6. Suite Navigation', `The dashboard shows permission-filtered rooms as expandable cards.
Opening one room collapses the others. Patient Portal stays inside Care.
The left sidebar is grouped by module (Ticketing, Care, Care Portal, Maintenance, Supply, Suite Administration).
Only one sidebar group is open at a time. The current page's group opens automatically.`],
        ['7. Mobile App', `The soldier app uses GET /api/suite/rooms to render the same rooms on the phone.
Ticketing menus open native Tickets/Create Ticket screens.
Care, Maintenance and Supply menus open this web dashboard in the device browser until native screens are built.
The API URL comes from soldier-app/app.json -> expo.extra.apiUrl.`],
        ['8. API & Integration', `POST /face/verify {service_number, embedding[128]}
POST /login, GET /user, POST /logout, POST /logout-all
GET /suite/modules, GET /suite/rooms, GET /suite/notifications
GET /tickets/stats, GET /tickets, POST /tickets, GET /tickets/:id, PATCH /tickets/:id
GET /chat/groups, GET /chat/unread, GET /chat/groups/:group/messages, POST /chat/groups/:group/messages
GET /meta (includes care_registration_fees)`],
        ['9. New User Story', `A soldier with no signal opens the app, expands the Ticketing room and taps New Ticket.
The ticket is saved on the device and replays when Sync runs.
A new admin sees one sidebar module open at a time; clicking another module collapses the previous one.
A care worker only sees Care menus because the backend filters rooms and links by permission.
See ONBOARDING_STORY.md in the repository for the full story and workaround table.`],
        ['10. Deployment', `Army intranet: php artisan config:cache, storage:link, queue for ticket sync, HTTPS required for camera.
PWA: public/manifest.json + sw.js.
Run php artisan test --compact, vendor/bin/pint --dirty --format agent and npm run build before release.`],
    ];
    return (
        <Layout>
            <h1 className="text-3xl font-bold text-[#0b3d0b]">Full Documentation</h1>
            <p className="text-slate-600 mt-2">Updated for suite rooms, collapsible module menus, managed dropdowns and the soldier mobile app.</p>
            <div className="mt-4 flex gap-3">
                <a href="/help" className="border px-4 py-2 rounded text-sm">Quick Help</a>
                <span className="text-xs self-center text-slate-500">Detailed repository docs: README.md, SUITE_PLAN.md, ONBOARDING_STORY.md and soldier-app/README.md</span>
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
                <b>Bulk Upload → Documentation Link:</b> After CSV import, the report page at <code>/admin/users/bulk</code> shows success/skipped rows with a download link to <code>storage/app/exports/bulk_import_*.csv</code> and links here to <code>/docs</code> for the full spec.
            </div>
        </Layout>
    );
}
