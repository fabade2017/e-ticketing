# NA E-Ticketing Suite

A single Laravel application for ticketing, care, maintenance and supply operations, with an
Inertia/React web client and an offline-capable Expo mobile app for soldiers.

## What is included

- **Ticketing** — create, assign, track and report tickets with SLA timers, history and offline mobile capture.
- **Care** — patient portal and staff workspace for registrations, appointments, visits, lab, billing and reports.
- **Maintenance** — equipment, faults, work orders, preventive schedules and overdue alerts.
- **Supply** — stock balances, batches, receipts, issues, reorder rules and approval workflows.
- **Suite shell** — permission-filtered modules, dashboard "rooms", unified notifications and reports.

## Technology

- PHP 8.3 / Laravel 13, Spatie Permission, Sanctum, PHPUnit, Pint
- React 19 / Inertia 3 / Tailwind 4 / Vite
- Expo / React Native mobile app in `soldier-app/`

## Quick start

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve --port=8004
```

Set the database and mail values in `.env` before migrating. Local seed data contains demo
credentials; replace all default passwords before any non-local deployment.

## Navigation for new users

### Web

- The left menu is grouped by **suite module** (Ticketing, Care, Care Portal, Maintenance, Supply, Suite Administration).
- Only one module group is open at a time. Clicking a module header opens it and collapses the others.
- The group for the current page opens automatically.
- The dashboard shows **suite rooms** as expandable cards. Opening one room collapses the others.
- Menu links are filtered by the roles and permissions attached to your account.

### Mobile

- The soldier app reads permission-filtered rooms from `GET /api/suite/rooms`.
- Dashboard rooms expand one at a time and list the menus you are allowed to use.
- Ticketing menus open the native Tickets/Create Ticket screens.
- Other module menus open the web dashboard in the device browser because native Care,
  Maintenance and Supply screens are not included in this app build yet.

## Common workflows

1. **Create a ticket with no signal** — open the mobile app, use the Ticketing room → New Ticket. The ticket
   is queued on the device and replays when Sync runs with connectivity.
2. **Switch modules on the web** — click a module label in the sidebar. Previous module menus collapse.
3. **Open a module menu on mobile** — expand a room card, then tap a menu. Non-ticketing menus open the web app.
4. **Change dropdown choices** — SuperAdmin can edit ticket categories, care registration categories,
   relationships, KB categories, supply units and ranks under Settings → Dropdown Options.
5. **Change care registration fees** — SuperAdmin can set military, civilian and dependant fees under
   Settings → Care Registration Fees. Existing registrations keep their recorded amounts.

## API endpoints used by the mobile app

All endpoints are under `/api` and use Sanctum Bearer tokens except login/face verification.

- `POST /login`, `POST /face/verify`
- `GET /user`, `POST /logout`, `POST /logout-all`
- `GET /suite/modules`, `GET /suite/rooms`, `GET /suite/notifications`
- `GET /tickets/stats`, `GET /tickets`, `POST /tickets`, `GET /tickets/{ticket}`, `PATCH /tickets/{ticket}`
- `GET /chat/groups`, `GET /chat/unread`, `GET /chat/groups/{group}/messages`, `POST /chat/groups/{group}/messages`
- `GET /meta` (includes managed `care_registration_fees`)

## Verification

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
```

For the mobile bundle:

```bash
cd soldier-app
npx expo export --platform android --output-dir /tmp/soldier-app-export
```

## Documentation

- [New-user story and workarounds](ONBOARDING_STORY.md)
- [Suite architecture and migration plan](SUITE_PLAN.md)
- [Soldier mobile app setup](soldier-app/README.md)
- In-app `/docs` and `/help` pages

## Known limitations

- Mobile offline capture covers ticket creation; Care, Maintenance and Supply are web-first for now.
- The web offline ticket form does not yet replay queued tickets.
- MFA/TOTP and payment-gateway integrations remain configuration/decision items in `SUITE_PLAN.md`.
- `soldier-app/dist/` is generated build output and should not be committed.
