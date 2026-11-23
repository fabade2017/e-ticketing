# NA E-Ticket — Soldier Mobile App (Expo React Native + Sanctum)

Offline-capable Android/iOS app for soldiers. It talks to the Laravel Sanctum API in `routes/api.php`.
See [../ONBOARDING_STORY.md](../ONBOARDING_STORY.md) for a guided first-day walkthrough.

## API endpoints used here

- `POST /api/login` — service number + password → Bearer token
- `POST /api/face/verify` — face embedding verification
- `GET /api/user`, `POST /api/logout`, `POST /api/logout-all`
- `GET /api/suite/modules` — permitted module cards
- `GET /api/suite/rooms` — permission-filtered rooms with their menu lists
- `GET /api/suite/notifications` — unread suite notifications
- `GET /api/tickets/stats`, `GET /api/tickets`, `POST /api/tickets`, `GET /api/tickets/:id`, `PATCH /api/tickets/:id`
- `GET /api/chat/groups`, `GET /api/chat/unread`, `GET /api/chat/groups/:group/messages`, `POST /api/chat/groups/:group/messages`
- `GET /api/meta`

All endpoints except login/face verification require `Authorization: Bearer <token>`.

## Quick start

```bash
cd soldier-app
npm install
# Edit app.json -> expo.extra.apiUrl for your environment
npx expo start
# Press a (Android emulator), i (iOS simulator), or scan the QR with Expo Go
```

`src/api/client.js` reads the API URL from `app.json`, so `expo.extra.apiUrl` is the single
source of truth. Use your LAN address for a local backend, or the deployed HTTPS API URL
for a device build.

## Local demo credentials

These come from `database/seeders/DatabaseSeeder.php` and are for local testing only.
Replace them before any non-local deployment.

- Soldier: `NA/SL/003` / `Soldier@123`
- SuperAdmin: `NA/SA/001` / `Admin@123`
- Admin: `NA/ADM/002` / `Admin@123`

## Dashboard and suite rooms

The dashboard loads `GET /api/suite/rooms` and renders each permitted room as an expandable card.
Opening one room collapses the others. Room menus are permission-filtered by the backend.

Menu behavior:

- `/tickets/create` opens the native Create Ticket screen.
- Other `/tickets*` routes open the native Tickets screen.
- Care, Maintenance, Supply and other module menus open the web dashboard in the device browser.

This is the current workaround until native screens exist for those modules.

## Offline behavior

- Create Ticket queues to AsyncStorage when the API is unreachable (`src/api/client.js` → `queueTicket()`).
- Dashboard and Create screens expose Sync Offline Queue, which replays queued tickets when online.
- SLA timers are shown locally: urgent 4h / high 12h / medium 24h / low 72h.
- Offline login with PIN is available after a PIN is set on the device.

## Security notes

- Tokens are stored in `expo-secure-store` where available, with AsyncStorage fallback.
- `is_active` is checked by `Api/AuthController`; inactive users receive 401.
- Bearer tokens are used instead of cookies/CSRF.
- RBAC is enforced server-side; soldiers can only see and patch their own tickets.

## Build for deployment

```bash
npm install -g eas-cli
eas login
eas build --platform android --profile preview     # APK for internal testing
eas build --platform android --profile production  # AAB for store/internal track
eas build --platform ios --profile preview
```

Bundle IDs are configured in `app.json`; signing is configured in `eas.json`.
For a local bundle check without EAS:

```bash
npx expo export --platform android --output-dir /tmp/soldier-app-export
```

## Structure

```
src/
  api/client.js          # axios, token storage, offline queue
  context/AuthContext.jsx # login/logout/me
  screens/
    LoginScreen.jsx
    DashboardScreen.jsx   # stats, suite rooms, notifications, sync
    TicketsScreen.jsx     # own tickets, filters, SLA badges
    CreateTicketScreen.jsx # offline ticket queue
    TicketDetailScreen.jsx
  navigation/AppNavigator.jsx
```

## Future work

- Native Care, Maintenance and Supply screens.
- Push notifications for SLA/assignment.
- Biometric unlock before showing tickets.
- MDM configuration for API URL injection.
