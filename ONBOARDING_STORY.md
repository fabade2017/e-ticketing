# New User Story and Workarounds

This guide is written for someone using the system for the first time. It explains what you see,
why it is arranged that way, and what to do when something is not available on your device.

## What is a "room"?

A room is a permission-filtered group of menus from one suite module. On the web dashboard, rooms
appear as expandable cards. On the mobile dashboard, the same rooms appear as expandable cards.
Opening one room collapses the others so you only see one module's menus at a time.

Rooms currently available, depending on your access:

- **Ticketing** — create and manage tickets, reports, knowledge base
- **Care** — registrations, appointments, visits, lab, invoices, duty certificates
- **Maintenance** — work orders and preventive maintenance
- **Supply** — stock levels and approvals

Patient Portal is inside Care rather than a separate top-level room.

## Story 1 — A soldier at a border outpost

Ada is deployed at an outpost with weak mobile signal. She opens the soldier app and sees four
statistics cards followed by a **Suite Rooms** section. Her account only has Ticketing, so she
sees one room.

1. She taps **Ticketing**. The room expands and shows her permitted menus.
2. She taps **New Ticket** and fills in the category, priority, state/LGA details and description.
3. The signal drops before she submits. The app saves the ticket on the device.
4. When signal returns, she taps **Sync Offline Queue**. The queued ticket is sent and appears in her ticket list.
5. If her unit later grants Care access, the Care room appears automatically after the app refreshes.

**Workaround:** mobile offline capture is for ticket creation only. Care, Maintenance and Supply
menus open the web dashboard in the browser until native mobile screens are built.

## Story 2 — A new administrator

Musa has just been given an Admin account. He logs in to the web app and lands on `/dashboard`.

1. The left sidebar shows module headings: **Ticketing**, **Care**, **Maintenance**, **Supply**,
   and **Suite Administration**.
2. Only one heading is open. The heading for the page he is on opens automatically.
3. He clicks **Care**. Other module menus collapse, and the Care links appear.
4. He opens a Care page from the menu. The sidebar keeps Care open because that is the current module.
5. If he needs user management, he clicks **Suite Administration**. Care collapses and admin links open.

**Workaround:** if a menu link is missing, the account does not have the required role/permission.
Ask a SuperAdmin to check Roles & Permissions. Do not add a raw URL as a permanent workaround;
permissions are enforced server-side too.

## Story 3 — A care worker

Halima is a Reception officer. Her account has Care workspace access but not maintenance or supply.

1. Her dashboard Care card shows only the Care menus she can use.
2. Her sidebar has Care open by default and no Maintenance or Supply groups.
3. She registers a patient, then opens Appointments from the Care group.
4. If she also has patient-portal support, she can open Patient Dashboard from the Care Portal group.

**Workaround:** portal records are separated from staff workspaces. A patient/sponsor account cannot
see another patient's clinical history, even if both are linked to the same hospital.

## Common workarounds

| Situation | What to do |
| --- | --- |
| A mobile room is empty | Refresh the app. If still empty, confirm the account has that module permission. |
| A mobile Care/Maintenance/Supply menu opens a browser | Expected. Those modules are web-first; use the web dashboard until native screens ship. |
| The left sidebar looks collapsed | Click the module heading. Only one module stays open at a time by design. |
| A dropdown choice is missing | Ask a SuperAdmin to edit Settings → Dropdown Options. Choices are now managed in the database. |
| No signal and a ticket must be filed | Use the mobile app's New Ticket screen; it queues locally and syncs later. |
| Web offline ticket queue does not replay | Not implemented yet; re-enter the ticket when online or use the mobile app queue. |
| API URL errors in the mobile app | Check `soldier-app/app.json` → `expo.extra.apiUrl` for the build environment. |
| In-app PDF link says "soon" | Use the `/docs` page and this guide; no PDF is bundled yet. |
| `soldier-app/dist/` appears in git status | Generated build output; leave it untracked. |

## Where to continue

- [Root README](README.md) — setup, architecture summary, API list and verification commands
- [Suite plan](SUITE_PLAN.md) — module design, delivery phases and open decisions
- [Mobile README](soldier-app/README.md) — Expo setup and offline behavior
- In-app `/help` and `/docs` pages — short operational guidance
