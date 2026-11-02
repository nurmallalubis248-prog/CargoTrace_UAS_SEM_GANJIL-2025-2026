# CargoTrace — Global Shipment Visibility & Tracking Platform

Web Programming 2 · Final Project (UAS). A Laravel application that lets logistics companies and their customers follow shipments across the world: lifecycle, tracking timeline, map, ETA/delay detection, exception management, dashboard, REST API.

> **Live demo:** _add your deployed URL here_ &nbsp;|&nbsp; **Repository:** _add your GitHub URL here_

## Problem and solution
Shipment information is scattered, customers cannot see the latest status, delays and exceptions are invisible, and operators have no place to prioritise follow-ups. CargoTrace centralises shipments, carriers, locations and tracking events, shows the journey on a map and timeline, flags delays with deterministic rules and lets operators manage exceptions.

## Features
- **Roles:** Administrator, Logistics Operator, Customer. Authorization is enforced on the server (policies + route middleware) for every page and API endpoint.
- **Shipment lifecycle** with a validated state machine: `CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> DELIVERED` (`CANCELLED` from CREATED/BOOKED). Terminal states cannot be reopened.
- **Tracking events** (append-only history), chronological timeline, normal vs. exception events, current location from the latest valid event.
- **ETA / delay rule:** `delay = ETA (or actual arrival) - expected arrival`; `<= 6 h` LOW, `<= 24 h` MEDIUM, `> 24 h` HIGH. Thresholds are configurable in `.env`. The UI shows the reason and the values used.
- **Exception management:** CUSTOMS_HOLD, WEATHER_DELAY, PORT_CONGESTION, DAMAGED, MISROUTED, DOCUMENTATION_ISSUE; severity; OPEN -> INVESTIGATING -> RESOLVED; detection and resolution time stored.
- **Map:** Leaflet + OpenStreetMap (origin, destination, tracking points, popups, attribution).
- **External APIs (Laravel HTTP Client):** Open-Meteo weather (shipment page) and Nominatim geocoding (admin location form). Timeouts, HTTP errors and empty data are handled; weather is informational only and is not used to predict delays.
- **Dashboard (Chart.js + Fetch):** totals, in transit, delivered, delayed, open exceptions, shipments by status/carrier, delays by severity, delay/exception rate, on-time delivery rate, average exception resolution time, live map of active shipments — all computed from the database.
- **Search, filter, sort, server-side pagination** by number/reference, status, carrier, origin, destination, date range and delay.
- **In-app notifications** and **audit log** (actor, action, time, entity).
- **REST API** with Laravel Sanctum, consistent JSON errors (`401/403/404/422`), no stack traces.

## Tech stack
PHP 8.2+ (8.3 recommended) · Laravel 12 · MySQL 8 · Eloquent + migrations/seeders · Blade · Tailwind CSS (CDN) · Vanilla JS + Fetch · Leaflet + OpenStreetMap · Chart.js · Laravel Sanctum · PHPUnit · Postman.

## Setup
```bash
git clone <your-repo-url> cargotrace && cd cargotrace
composer install
cp .env.example .env
php artisan key:generate
# create an empty MySQL database named "cargotrace", then set DB_* in .env
php artisan migrate --seed
php artisan serve        # http://localhost:8000
```
XAMPP users: start Apache + MySQL, create the database in phpMyAdmin, keep `DB_HOST=127.0.0.1`, `DB_USERNAME=root`, empty password.

### Demo accounts (password: `password`)
| Role | Email |
|---|---|
| Administrator | admin@cargotrace.test |
| Logistics Operator | operator@cargotrace.test |
| Customer | customer1@cargotrace.test, customer2@cargotrace.test |

> Change or delete these accounts on any public deployment.

### Demo data
`database/seeders/DatabaseSeeder.php` creates ports, carriers, users and nine shipments (on time, delayed LOW/MEDIUM/HIGH, delivered, booked, created, with exceptions). Seeding goes through the real service classes so all business rules apply. **Port names/coordinates and carrier names are real references; all shipments, vessel positions, delays and exceptions are synthetic demonstration data.**

## Tests
```bash
php artisan test
```
Tests use an in-memory SQLite database (see `phpunit.xml`), so they never touch your MySQL data. They cover: authentication (valid/invalid login, access without login), shipment CRUD + validation + authorization, tracking events and invalid status transitions, ETA/delay rule (unit + API), exceptions, external API success/error/timeout/empty responses, dashboard scoping and role-based web access.

## REST API
Import `postman/CargoTrace.postman_collection.json` into Postman, run **Login**, and the token is stored automatically.

| Area | Endpoints |
|---|---|
| Auth | `POST /api/register`, `POST /api/login`, `POST /api/logout`, `GET /api/user` |
| Shipments | `GET/POST /api/shipments`, `GET/PUT/PATCH/DELETE /api/shipments/{id}` |
| Tracking | `GET/POST /api/shipments/{id}/tracking` |
| Exceptions | `GET/POST /api/shipments/{id}/exceptions`, `PATCH /api/exceptions/{id}` |
| Master data | `GET/POST /api/carriers`, `GET/POST /api/locations`, `GET /api/customers` |
| Other | `GET /api/dashboard`, `GET /api/shipments/{id}/weather`, `GET /api/geocode?q=`, `GET /api/notifications` |

Errors: `401` unauthenticated, `403` forbidden, `404` not found, `422` validation or business-rule violation (`{"message": "...", "errors": {...}}`).

## Security
Password hashing (bcrypt) · auth middleware and server-side authorization (policies, role middleware) · Form Request validation · CSRF tokens on web forms · Blade escaping (XSS) and DOM `textContent` for map popups · Eloquent/query-builder bindings, whitelisted sort columns (SQL injection) · `$fillable` mass-assignment protection (`role` is never mass-assignable) · rate limiting on login/geocoding/API · `.env` and API keys are git-ignored.

## Deployment
1. Provision PHP 8.2+, MySQL, Composer (any shared hosting with SSH, VPS, or PaaS).
2. Point the web root to `public/`.
3. `composer install --no-dev --optimize-autoloader`, create `.env` with `APP_ENV=production`, `APP_DEBUG=false`, the real `APP_URL`, DB credentials, and `SHOW_DEMO_ACCOUNTS=true` only for the demo.
4. `php artisan key:generate && php artisan migrate --seed --force`
5. Make `storage/` and `bootstrap/cache/` writable. Optionally `php artisan config:cache route:cache`.
6. Set `GEOCODING_USER_AGENT` to your own contact (Nominatim usage policy).

CI: `.github/workflows/tests.yml` runs the test suite on every push.

## Documentation
- `docs/ERD.md` — ERD, cardinality and design decisions
- `docs/DATA_DICTIONARY.md` — tables and columns
- `docs/ARCHITECTURE.md` — layers and request flow
- `postman/` — API collection

## Known limitations and future work
- Tailwind is loaded from the CDN for simplicity; a production build with Vite would remove the runtime dependency.
- No direct integration with real carriers, customs or port systems; no real-time GPS (out of scope).
- Reopening a DELIVERED/CANCELLED shipment is not implemented.
- E-mail notifications and Docker are not included (advanced items).
- Map routes are straight lines between reported points, not shipping lanes.

## Attribution
- Laravel (MIT), Laravel Sanctum (MIT), PHPUnit (BSD-3)
- Tailwind CSS (MIT), Leaflet (BSD-2), Chart.js (MIT)
- Map tiles and geodata: © OpenStreetMap contributors (ODbL); geocoding via Nominatim
- Weather: Open-Meteo (CC BY 4.0, non-commercial use)
- **AI usage:** _edit this to describe honestly how you used AI (e.g. "initial scaffold generated with Claude; I reviewed, ran and modified the code and can explain it")._ 

## UI theme

The interface uses a matcha green (primary) and sakura pink (accent) palette.
All design tokens (colors, font, buttons, cards, form inputs) live in one file:
`resources/views/partials/theme.blade.php`. Change the palette there and every page follows.
