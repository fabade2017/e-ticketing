# NA Support Services Suite — Architecture & Migration Plan

Status: living implementation reference
Date: 19 November 2026
Updated: 23 November 2026
Scope: combine NA E-Ticket (existing) with NA Care Network, ArmyFix and SupplyTrack into one Laravel application with four modules.

## 1. Goal and principles

One Laravel app, one database, one login, one navigation shell, four modules:

| Module | Source writeup | Primary actors |
| --- | --- | --- |
| Ticketing | existing app | Soldier, Admin, Support, SuperAdmin |
| Care | NA Care Network user guide | Patient/sponsor, Reception, Accounts, Nurse, Doctor, Lab, Pharmacy, Records Officer, Hospital Admin, Network Admin |
| Maintenance | ArmyFix | Maintenance technician, Workshop supervisor, Logistics admin |
| Supply | SupplyTrack | Stores officer, Warehouse manager, Supply admin |

Principles:

- Preserve the existing ticketing behaviour and mobile API while adding modules around it. No big-bang rewrite.
- Share identity, navigation, branding, notifications and reporting at the suite layer. Keep module business rules inside the module.
- Modules do not reach into each other's Eloquent models. Cross-module operations go through explicit link columns and a small integration service.
- One database. Module tables are prefixed (`care_`, `maint_`, `supply_`) to keep ownership obvious. No per-module databases or schemas.
- Follow the conventions already in this repo: inline validation in controllers, `$fillable` models, local query scopes, services under `app/Services`, Inertia React pages, PHPUnit feature tests, Pint.
- No new composer/npm dependencies without approval (MFA in particular — see §10).

## 2. Target structure

Module folders are a new base folder, so they need explicit approval before code is written. Proposed layout, keeping every existing file in place initially:

```
app/
  Modules/
    Care/
      Http/Controllers/{Portal,Workspace}/
      Models/
      Services/
      Policies/
    Maintenance/
      Http/Controllers/
      Models/
      Services/
      Policies/
    Supply/
      Http/Controllers/
      Models/
      Services/
      Policies/
    Ticketing/
      Services/            # adapters over existing app/Models/Ticket.php
  Shared/
    Identity/              # hospital selection, workspace access
    Navigation/            # module registry + menu definitions
    Notifications/
    Reporting/             # InsightsProvider contract + registry
  Models/                  # existing shared models stay here
  Http/Controllers/        # existing controllers stay here
  Services/                # existing services stay here
resources/js/
  Components/ui/           # shared Card, DataTable, PageHeader, StatCard, Badge, Skeleton, EmptyState
  Components/SuiteShell.jsx
  Components/PatientPortalLayout.jsx
  Pages/{Care,Maintenance,Supply,Dashboard}/
routes/
  web.php                  # suite + ticketing routes
  care.php                 # portal and staff workspace routes
  maintenance.php
  supply.php
  api.php                  # existing mobile API
database/migrations/       # timestamped, module-prefixed tables
```

Register the new route files in `bootstrap/app.php:8-14`. Share the module registry through `HandleInertiaRequests::share()` (`app/Http/Middleware/HandleInertiaRequests.php:38-68`) so the React shell can filter links without extra requests.

## 3. Shared foundation

### 3.1 Identity and workspaces

- Keep the single `users` table and the existing web session + Sanctum token setup. One login page (`/login`) serves all modules.
- Add `hospitals` (network of facilities), `hospital_user` pivot (`hospital_id`, `user_id`, `local_role`, `is_active`), and `users.current_hospital_id` (nullable, no FK to avoid a two-column cycle; validated on selection).
- `POST /care/hospital/select` validates the hospital against the pivot and stores it in the session. Staff workspace routes use an `EnsureHospitalSelected` middleware. All Care queries take an explicit `forHospital($id)` scope — no global scope, because reporting legitimately needs network-wide aggregates.
- Add `workspace` to the shared Inertia props (`ticketing`, `care-portal`, `care-staff`, `maintenance`, `supply`) so the shell can pick the right layout and breadcrumbs.
- Patients are **not** `users` by default. Add `care_patients` with `network_number`, `army_number`, `category` (military / civilian / dependant), `hospital_id`, `linked_user_id` (nullable). A portal account may link to one patient; sponsors link via `care_patient_sponsors`. Sponsorship grants invoice/registration visibility only — never clinical history (enforced by `PatientPolicy`).
- MFA: staff roles (Reception, Accounts, Nurse, Doctor, Lab, Pharmacy, Records Officer, Hospital Admin, Network Admin, Maintenance/Stores admins) require six-digit TOTP; patients and soldiers do not. Enrolment re-enters the password, then verifies a code, then revokes other sessions. Laravel 13 core has no TOTP support, so this needs an approved package (§10).

### 3.2 Roles and permissions

Keep the four existing roles (`SuperAdmin`, `Admin`, `Support`, `Soldier`) and `Gate::before` SuperAdmin bypass (`app/Providers/AppServiceProvider.php`). Add module roles:

`Patient`, `Reception`, `Accounts`, `Nurse`, `Doctor`, `Lab`, `Pharmacy`, `RecordsOfficer`, `HospitalAdmin`, `NetworkAdmin`, `MaintenanceTech`, `WorkshopSupervisor`, `StoresOfficer`, `WarehouseManager`, `SupplyAdmin`.

Add permissions: `use ticketing`, `use care portal`, `use care workspace`, `use maintenance`, `use supply`, `view reports` (exists), plus module actions such as `approve registrations`, `verify payments`, `certify duty status`, `issue stock`, `approve work orders`, `view network reports`.

Important: the current seeder grants permissions to non-SuperAdmin roles only when the role was newly created (`database/seeders/DatabaseSeeder.php:42-46`). Add a separate additive `SuitePermissionSeeder` using `givePermissionTo()` so new permissions reach existing roles without overwriting SuperAdmin's edits in Roles & Permissions.

Hospital-level authority comes from the pivot + policies, not from roles alone. A `HospitalAdmin` must not be able to act on another hospital even if they hold the role.

### 3.3 Navigation and design system

- The side menu in `resources/js/Components/SideMenu.jsx` is grouped by the permission-filtered `suite.modules` prop. Groups are collapsible accordions: only one module is open at a time, and the current route auto-opens its module. Keep the existing green header (`#0b3d0b`) and branding props. Moving the full menu definition to the server remains a future cleanup.
- The dashboard uses permission-filtered room cards from `SuiteNavigation::roomsFor()`. Opening a room collapses the other rooms; Patient Portal remains inside Care.
- Extract repeated JSX into `resources/js/Components/ui/`: `PageHeader`, `StatCard`, `DataTable` (search/sort/paginate), `StatusBadge`, `EmptyState`, `Skeleton`, `ConfirmDialog`. Use Tailwind v4 `@theme` tokens in `resources/css/app.css`; do not add `tailwind.config.js`.
- Two shells sharing the primitives: `SuiteShell` (staff) and `PatientPortalLayout` (patient portal). Patients see only their own requests, appointments, family and invoices.
- Sidebar badge becomes a suite-wide unread/overdue count from `suite_notifications`, replacing the chat-only 5-second fetch at `SideMenu.jsx:25-33`. Use Inertia `usePoll` on the dashboard and filtered partial reloads.
- Deferred props must render a pulsing skeleton before data arrives.

### 3.4 Shared infrastructure

- `suite_notifications`: polymorphic `notifiable`, `type`, `title`, `url`, `read_at`, module, hospital id. Feeds the badge and inbox. Covers care results/appointments, duty-status notices, cross-hospital alerts, overdue work orders and low stock.
- `audit_trails`: actor, action, subject, hospital, IP, metadata. Clinical, payment, stock-issue and duty-status actions are always audited. Keep `ticket_histories` as-is; mirror a compact row into `audit_trails` for the suite view.
- `system_settings` already exists; add suite-level keys (suite name, timezone, report retention) without removing face-auth keys.
- Timezone: Care reports and fee waivers use Lagos time (age 18 cutoff, monthly reports). Set reporting conversions explicitly to `Africa/Lagos` and keep timestamps stored consistently. Add a feature test around the 18th-birthday waiver boundary. Do not silently change existing SLA math.
- Add `Model::preventLazyLoading(! app()->isProduction())` and deterministic `orderBy` on paginated lists as modules land.

## 4. Module blueprints

### 4.1 Ticketing (existing, mostly unchanged)

- Keep current tables, `Ticket::scopeVisibleTo()` (`app/Models/Ticket.php:91-125`), anonymity tokens, SLA and offline `client_uuid` sync.
- Add nullable `ticket_link` metadata via `suite_links` rather than changing tickets: an equipment ticket can point to a work order; a medical ticket can point to a care visit.
- Fix the existing gaps that affect the suite: missing `edit`/`destroy` methods for the resource routes, web offline queue has no replay, and no scheduler registration for `tickets:escalate`.

### 4.2 Care

Two workspaces inside the same app: `/portal/*` (patients) and `/care/*` (staff).

Tables: `care_patients`, `care_patient_sponsors`, `care_registrations`, `care_documents`, `care_visits`, `care_vitals`, `care_diagnoses`, `care_lab_orders`, `care_lab_results`, `care_lab_images`, `care_prescriptions`, `care_pharmacy_dispenses`, `wards`, `beds`, `care_admissions`, `care_duty_certificates`, `care_frontline_records`, `care_referrals`, `care_invoices`, `care_payments`, `care_monthly_reports`.

Rules from the guide to encode:

- Registration review requires identity/evidence checks; a military sponsor must be approved before dependants.
- Fee waiver: approved military personnel; spouse/child under 18 after checks; child 18+ pays full fee in Lagos time; civilian charges apply. Accounts verifies actual credit, not just an uploaded receipt.
- Appointment requests are not bookings until Reception confirms.
- Only published consultation summaries are visible to the patient; internal notes, lab images and staff reports stay private.
- Lab results: written findings plus up to three JPG/PNG attachments at 5 MB each. Uploads validated by MIME and size, stored via `Storage` outside public execution.
- Bed stays occupied until nurse checkout. Duty certificates are `FIT|RESTRICTED|UNFIT` with statement, review date and retained history; no certificate means `NOT ASSESSED`, passed review date means `REVIEW DUE`. Only doctors certify; Records Officers maintain administrative fields only.
- Referrals require receiving-hospital acceptance. Cross-hospital alerts go to the previous completed visit's doctor.
- Monthly reports: distinct patients, completed visits, patients tested, tests performed, disease encounters; selected hospital vs network statistics; CSV/print exports. Lagos timezone.
- OPay temporary transfer accounts and MFA need credentials/package approval before implementation (§10).

### 4.3 Maintenance (ArmyFix)

Tables: `equipment`, `faults`, `maint_work_orders`, `maint_work_order_parts`, `maint_preventive_schedules`, `maint_service_logs`, `maint_overdue_flags`.

- `equipment` uses authorised asset IDs (unique, validated) and belongs to a hospital/unit.
- Faults create work orders; work orders track technician, status, parts requested/issued/used, timestamps for repair-time metrics and recurring-fault grouping by asset + fault code.
- Preventive maintenance schedules generate work orders via a scheduled command with `withoutOverlapping()` and `onOneServer()`.
- Overdue work orders surface in the suite dashboard and `suite_notifications`.
- First release does not connect to classified operational systems.

### 4.4 Supply (SupplyTrack)

Tables: `supply_items` (SKU), `supply_stock_balances` (item + hospital/store), `supply_batches` (batch, expiry, damaged flag), `supply_goods_received`, `supply_goods_issued`, `supply_reorder_rules`, `supply_reconciliations`, `supply_approval_requests`.

- Stock movement rows are immutable after approval; corrections are new adjustment rows, not edits.
- Reorder alerts derive from `supply_reorder_rules` and current balance; expiry/damage flags feed both alerts and reports.
- Approval workflows cover issues, write-offs and reconciliations. Every issue records actor, recipient, purpose and hospital.
- Use DB transactions + row locks for balance updates; idempotency keys on received/issued documents to prevent double posting.

## 5. Cross-app data links

Keep integrations explicit and narrow:

| Link | Storage | Direction |
| --- | --- | --- |
| Ticket → work order | `maint_work_orders.ticket_id` (nullable FK) | Admin opens a work order from an equipment/logistics ticket |
| Work order → parts used | `maint_work_order_parts.supply_issue_id` (nullable FK) | Supply issue recorded against the work order |
| Care visit → supplies used | `supply_goods_issued.care_visit_id` (nullable FK) | Consumables issued for a visit |
| Care patient ↔ ticket | `suite_links` (polymorphic) | Generic navigation link, no clinical data copied |
| Referral | `care_referrals` | Care-to-care between hospitals |

`Shared/Reporting` and a `SuiteIntegrationService` expose operations such as `issueStockForWorkOrder()` and `openWorkOrderFromTicket()`. Each operation is one DB transaction, writes an audit row, and is idempotent by source key (reuse the `client_uuid` pattern from `Api/TicketController`). No module reads another module's tables directly except through these FKs and the shared notification/audit tables.

## 6. Combined dashboard and reports

- `/dashboard` remains the landing page. `AdminController::dashboard()` (`app/Http/Controllers/AdminController.php`) composes cards from registered `InsightsProvider` implementations: open/overdue tickets, pending registrations, today's visits, pending lab results, overdue work orders, low/expired stock, approval queue.
- Introduce `InsightsProvider` contract (`key`, `label`, `metrics(User $user, array $filters): array`) and register providers in `config/suite.php`. Each provider reuses the current visibility pattern: SuperAdmin sees all; others are scoped by hospital/unit/department/assignment. Never broaden scope inside the suite view.
- Extend `AiInsightsService` / `AiController::ask()` with suite-level questions and keep the Groq → rule-based fallback.
- Reports gain a module selector and a unified workbook export using the already-installed PhpSpreadsheet. Care monthly reports keep their own definitions and Lagos-time basis; the suite roll-up must not redefine them.
- Add `view network reports` gating so only Network Admin/SuperAdmin can see cross-hospital named rosters.

## 7. API and mobile

- `routes/api.php` exposes `/api/suite/modules`, `/api/suite/rooms` and `/api/suite/notifications` for the soldier app's home screen. Rooms are permission-filtered by `SuiteNavigation::roomsFor()`.
- Later API additions follow the existing Sanctum Bearer-token convention and `auth:sanctum` middleware group (`routes/api.php`).
- The mobile dashboard renders expandable room cards with the same one-open behavior as the web dashboard. Ticketing menus open native screens; other module menus open the web dashboard in the device browser until native screens are built.
- `soldier-app/src/api/client.js` reads `expo.extra.apiUrl` from `soldier-app/app.json`, which is the single source of truth. Verify that value for each build environment.
- Patient portal and staff workspace are Inertia pages; no separate Angular apps are introduced.

## 8. Delivery phases and acceptance criteria

**Phase 0 — Foundation (no user-visible change)**
- Hospitals, pivot, current-hospital selection, suite permissions seeder, notification/audit tables, module registry, timezone handling.
- Acceptance: existing 46 tests pass; a SuperAdmin and a Hospital Admin can be scoped correctly in feature tests.

**Phase 1 — Suite shell**
- Module-aware side menu, shared UI primitives, suite dashboard with ticketing cards, unified badge, patient portal shell stub.
- Acceptance: login leads to the suite dashboard; Soldier sees only their tickets; Admin sees scoped tickets; no broken Inertia links.

**Phase 2 — Supply + Maintenance**
- Full Supply and Maintenance modules, work-order and stock integrations, overdue/low-stock alerts.
- Acceptance: ticket → work order → issued parts flow is covered end-to-end by a feature test; concurrent stock issue cannot double-decrement.

**Phase 3 — Care**
- Portal registration/appointments/results, staff workspaces, clinical flows, records/duty certificates, referrals, monthly reports.
- Acceptance: permission matrix tests cover patient vs sponsor vs each staff role; waiver boundary, certificate lifecycle and report definitions are tested.

**Phase 4 — Unified insights and mobile**
- Suite reports/workbooks, AI questions, mobile module navigation and API fixes.
- Acceptance: combined dashboard respects each module's scope; exports are permission-gated; mobile room cards show only permitted menus; ticketing menus work natively and other menus fall back to the web dashboard.

Care is the largest module. If it is the delivery priority, swap phases 2 and 3, but keep Phase 0/1 first either way.

## 9. Verification

- Feature tests with `RefreshDatabase` and model factories, matching the existing PHPUnit style. Per module: route gating, hospital scoping, cross-module links, idempotency, report math, timezone boundary, upload validation.
- Run `php artisan test --compact` for affected files after each change, then `vendor/bin/pint --dirty --format agent`.
- `composer audit` before release; current install reports advisories in existing packages.
- Frontend changes require `npm run build` (or a dev server) to confirm the Vite manifest.
- Mobile changes require an Expo bundle export, for example `npx expo export --platform android --output-dir /tmp/soldier-app-export`.

## 10. Open decisions and risks

1. **MFA package** — Laravel 13 core has no TOTP; approve a package (e.g. Fortify or a TOTP/QR library) before phase 0/3.
2. **Module folder approval** — `app/Modules` and `app/Shared` are now in use; ask before adding another new base folder.
3. **Care funding/payment** — OPay temporary accounts need credentials, a provider contract and approval; manual transfers can be receipt-based with Accounts verification first.
4. **Clinical data sensitivity** — encryption at rest, audit retention, upload scanning and access reviews need explicit sign-off; do not claim AES-256 embeddings or blink liveness until implemented. Help, Docs and FaceAuth text now avoid those claims.
5. **One database** — simplest for this team, but a medical module raises backup/retention questions. Confirm this is acceptable.
6. **Seeded credentials and static default passwords** (`Password@123`, `Admin@123`, `Soldier@123`) must be replaced before any non-local deployment.
7. **Deployment** — single Docker/Render service fits the module plan; the Vercel `api/index.php` path needs review if that target is still used.
8. **Working tree** — implementation work is committed through `ed5ccbe`; only generated `soldier-app/dist/` build output remains untracked and should stay out of commits.
9. **Documentation** — root `README.md`, `SUITE_PLAN.md`, `soldier-app/README.md`, in-app Docs/Help/About/ProposalCoverage pages and `ONBOARDING_STORY.md` are the current documentation set. Keep them in sync as modules ship.
