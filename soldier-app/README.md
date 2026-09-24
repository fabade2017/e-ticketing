# NA E-Ticket — Soldier Mobile App (Expo React Native + Sanctum)

Military-grade offline-capable app for soldiers. Connects to Laravel Sanctum API at `routes/api.php`.

## Backend already done
- `POST /api/login` (service_number + password → Bearer token) `app/Http/Controllers/Api/AuthController.php:14`
- `GET /api/user`, `POST /api/logout`
- `GET/POST /api/tickets`, `GET /api/tickets/:id`, `PATCH /api/tickets/:id` (RBAC: soldier sees own only) `app/Http/Controllers/Api/TicketController.php:15`
- `GET /api/tickets/stats`, `GET /api/chat/*`
- Token storage via `HasApiTokens` at `app/Models/User.php:10`

## Quick Start
```bash
cd soldier-app
npm install          # or yarn
# 1. Set API URL for your network
# Edit app.json -> expo.extra.apiUrl
# For emulator: http://10.0.2.2:8004/api  (Android)
# For device on LAN: http://192.168.x.x:8004/api
# For prod: https://your-domain/api

npx expo start
# Press a (Android emulator), i (iOS simulator), or scan QR with Expo Go
```

## Test Credentials (from DatabaseSeeder.php)
- Soldier: `NA/SL/003` / `Soldier@123` (role Soldier, unit Border Outpost A) — sees own tickets only
- SuperAdmin: `NA/SA/001` / `Admin@123`
- Admin: `NA/ADM/002` / `Admin@123`

API quick test:
```bash
curl -X POST http://127.0.0.1:8004/api/login \
  -H "Content-Type: application/json" \
  -d '{"service_number":"NA/SL/003","password":"Soldier@123","device_name":"soldier-phone"}'

# Use token
curl http://127.0.0.1:8004/api/tickets -H "Authorization: Bearer <token>"
```

## Offline (Military Field Requirement)
- CreateTicket auto-queues to AsyncStorage when `!response` (no signal) via `src/api/client.js:queueTicket()`
- Dashboard + Create screen have "Sync Offline Queue" → replays `POST /api/tickets` with `offline_created:true`
- Pull-to-refresh on Tickets also triggers `syncQueue()` if desired (add to TicketsScreen)
- SLA timers shown locally: urgent 4h / high 12h / medium 24h / low 72h (`Ticket.php:38`)

## Security (Military)
- Token in `expo-secure-store` (Keychain/Keystore), not AsyncStorage plaintext
- `is_active` checked at `Api/AuthController.php:16`; inactive = 401
- Bearer token only, no cookies/CSRF. Logout revokes current token (`currentAccessToken()->delete()`)
- RBAC enforced server-side — soldier cannot view/ patch others' tickets

## Build for Deployment (MDM / Internal Distribution)
```bash
npm install -g eas-cli
eas login
eas build --platform android --profile preview  # APK for testing (internal)
eas build --platform android --profile production # AAB for Play Store internal track
eas build --platform ios --profile preview
# For classified networks: eas build --local (no Expo cloud)
```

Configure `app.json` bundle IDs: `ng.mil.na.eticket.soldier` already set. Add signing in `eas.json`.

## Structure
```
src/
  api/client.js          # axios + token + offline queue
  context/AuthContext.jsx # login/logout/me
  screens/
    LoginScreen.jsx
    DashboardScreen.jsx   # stats + queue status
    TicketsScreen.jsx     # own tickets, filters, SLA badges
    CreateTicketScreen.jsx # offline queue
    TicketDetailScreen.jsx
  navigation/AppNavigator.jsx
```

## Next TODO for Production
- [ ] Change `app.json.extra.apiUrl` to HTTPS prod domain + `SANCTUM_STATEFUL_DOMAINS`
- [ ] Add `expo-camera` for `POST /api/face/verify` (face embedding on-device)
- [ ] Push via `expo-notifications` + FCM for SLA/assignment
- [ ] Biometric `expo-local-authentication` before showing tickets
- [ ] MDM config (Intune) for API_URL injection
