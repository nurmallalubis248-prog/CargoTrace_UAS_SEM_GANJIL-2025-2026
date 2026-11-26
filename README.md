# CargoTrace — Global Shipment Visibility & Tracking Platform

Web Programming 2 · Final Project (UAS). A Laravel application that lets logistics companies and their customers follow shipments across the world: lifecycle, tracking timeline, live map, ETA/delay detection, delay forecast, exception management, warehouses, dashboard, multi-language UI and a REST API.

> **Repository:** https://github.com/nurmallalubis248-prog/CargoTrace_UAS_SEM_GANJIL-2025-2026
> **Live demo:** _TODO before submission: deploy following [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) and replace this line with the public URL_ (demo accounts are listed below)

## Problem and solution
Shipment information is scattered, customers cannot see the latest status, delays and exceptions are invisible, and operators have no place to prioritise follow-ups. CargoTrace centralises shipments, carriers, locations, warehouses and tracking events, shows the journey on a map and timeline, flags and forecasts delays with deterministic rules, lets operators manage exceptions, and lets customers follow their cargo and confirm receipt once it has arrived.

## Features

### Shipments and tracking
- **Roles:** Administrator, Logistics Operator, Customer. Authorization is enforced on the server (policies + route middleware) for every page and API endpoint.
- **Shipment CRUD** (create, list, detail, edit, delete) with search, filter, sort and server-side pagination by number/reference, status, carrier, origin, destination, date range and delay.
- **Shipment lifecycle** with a validated state machine: `CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> ARRIVED -> DELIVERED` (`CANCELLED` from CREATED/BOOKED). Terminal states cannot be reopened.
  - `ARRIVED`: the cargo reached the destination. The arrival time is stored then (the delay is computed from it), the marker **stays on the live map** until delivery is recorded.
  - `DELIVERED`: manual confirmation when the customer presses **Confirm received** (an admin may do it on the customer's behalf; operators cannot). In auto-shipping demo mode (`CARGOTRACE_SIMULATION_AUTO_DELIVER=true`, the default) the server records `DELIVERED` itself on arrival;  Details: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).
- **Tracking events** (append-only history), chronological timeline, normal vs. exception events, current location from the latest valid event. An event cannot be in the future or earlier than the latest event.
- **Cargo types:** general, container (FCL/LCL), bulk, refrigerated, liquid, hazardous.

### Delay detection and forecast
- **ETA / delay rule:** `delay = ETA (or actual arrival) - expected arrival`; `<= 6 h` LOW, `<= 24 h` MEDIUM, `> 24 h` HIGH. Thresholds are configurable in `.env`. The UI shows the reason and the values used.
- **Delay forecast** (dashboard table, `ForecastService`): a statistical model, not an external AI service. `predicted delay = max(current delay, 0.6 x average delay of the same carrier + lane) + penalty of open exceptions`. Confidence grows with the number of historical shipments on the lane. It is an estimate, not a guarantee.

### Exceptions
- CUSTOMS_HOLD, WEATHER_DELAY, PORT_CONGESTION, DAMAGED, MISROUTED, DOCUMENTATION_ISSUE; severity; `OPEN -> INVESTIGATING -> RESOLVED`; detection and resolution time stored.

### Dashboard (Chart.js + Fetch)
- Totals, in transit, delivered, delayed, open exceptions, delay rate, exception rate, on-time delivery rate, average delay and average exception resolution time, shipments by status / carrier, delays by severity, forecast table — all computed from the database.
- **Live map** (Leaflet + OpenStreetMap) with plane / truck / ship markers, destination pin, latitude/longitude grid and cursor coordinates; drag with the mouse, zoom with the wheel.
- **Live route** (shipment page, and on the dashboard for the selected shipment): road shipments follow **real roads** (OSRM), sea/air use great-circle estimates; the part already travelled is drawn solid, the rest faded; the vehicle keeps moving toward the ETA with a "remaining km" label, a speed selector (Live / x60 / x600 / x6000 simulation) and a "show vehicle" button. The position between two scans is an **estimate**, not GPS.
- Cards and charts are **clickable** (they open the filtered shipment list) and a table lists delayed shipments with their **reason**.
- Live clock that follows the selected country's time zone.

### Master data and administration (admin)
- **Carriers** (with transport mode AIR / ROAD / SEA), **Locations** (geocoding via Nominatim), **Warehouses** (code, location, capacity) and **Users** (create, change role). Carriers, locations and warehouses can be activated / deactivated.
- **Audit log** (actor, action, time, entity) and in-app **notifications**. The sidebar badge refreshes every 15 s; admins are notified when the customer confirms receipt, and customers when their cargo arrives.

### Email verification
- Self-registered customers must verify their email (link sent by SMTP, e.g. Gmail App Password) before using the app. Demo accounts and users created by an admin are pre-verified. Configure `MAIL_*` in `.env`.

### Auto-shipping demo mode (moving cargo)
Set `CARGOTRACE_SIMULATION=true`. By default (`CARGOTRACE_SIMULATION_MODE=time`) a shipment's position follows its schedule (departure -> arrival) and the real clock: `BOOKED` shortly after creation, then `PICKED_UP`, `IN_TRANSIT`, `ARRIVED` and `DELIVERED`. Every page request catches the shipment up to where it should be, and `php artisan schedule:work` (optional) keeps it moving when nobody is browsing. Each step is a **real tracking event** (same rules, notifications and audit log as an operator; descriptions start with \"[Simulation]\"). `CARGOTRACE_SIMULATION_TIME_SCALE` speeds the clock up for demos. The old mode (`CARGOTRACE_SIMULATION_MODE=step`) advances N % per run: `php artisan cargotrace:simulate --mode=step --step=25`. Real deployments post positions with `POST /api/shipments/{id}/tracking`.

### Language and PWA
- **Languages (whole app, all roles):** Indonesian, English, Chinese, Japanese, Spanish, French, Arabic (RTL). Dictionary: `public/js/ct-dict.js`; engine: `public/js/ct-i18n.js`.
- **Country selector:** picks a country, switches the language and filters the dashboard map.
- **PWA:** installable ("Pasang aplikasi" button), `public/sw.js` service worker, `public/manifest.webmanifest`.

### External APIs (Laravel HTTP Client)
- Open-Meteo weather (shipment page) and Nominatim geocoding (admin location form), with timeouts, HTTP-error and empty-data handling. Weather is informational only and is not used to predict delays.

## Tech stack
PHP 8.2+ (8.3 recommended) · Laravel 12 · MySQL / MariaDB (XAMPP) · Eloquent + migrations/seeders · Blade · Tailwind CSS (CDN) · Vanilla JS + Fetch · Leaflet + OpenStreetMap · Chart.js · Laravel Sanctum · barryvdh/laravel-dompdf · OSRM routing · PHPUnit · Postman.

## Setup

```bash
git clone https://github.com/nurmallalubis248-prog/CargoTrace_UAS_SEM_GANJIL-2025-2026.git cargotrace
cd cargotrace
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# create an empty MySQL database named "cargotrace", then check DB_* in .env
php artisan migrate --seed
php artisan serve             # http://127.0.0.1:8000
```

To reset the database and reload the demo data at any time:

```bash
php artisan migrate:fresh --seed
```

**XAMPP users:** start Apache + MySQL, create the database `cargotrace` in phpMyAdmin, keep `DB_HOST=127.0.0.1`, `DB_USERNAME=root` and an empty password.

**phpMyAdmin alternative (without Artisan):** import `database/cargotrace_schema.sql` (creates all tables and demo data in one go). Use **either** this route **or** `php artisan migrate`, never both, otherwise you get "table already exists" errors. The richer demo data (road/air carriers, delayed shipments with reasons) is only created by the seeder (`php artisan migrate:fresh --seed`).

### Demo accounts (password: `password`)
| Role | Name | Email |
|---|---|---|
| Administrator | Admin CargoTrace | admin@cargotrace.test |
| Logistics Operator | Logistics Operator | operator@cargotrace.test |
| Customer | PT Nusantara Export | customer1@cargotrace.test |
| Customer | Samudra Trading Co. | customer2@cargotrace.test |

> Change or delete these accounts on any public deployment. The login page only lists them when `SHOW_DEMO_ACCOUNTS=true`.

### Demo data
`database/seeders/DatabaseSeeder.php` creates ports, carriers, warehouses, the four users above and sample shipments (on time, delayed LOW/MEDIUM/HIGH, delivered, booked, created, with exceptions). Seeding goes through the real service classes so all business rules apply. **Port names/coordinates and carrier names are real references; all shipments, vessel positions, delays and exceptions are synthetic demonstration data.**

## Configuration (`.env`)
| Variable | Purpose |
|---|---|
| `DB_*` | MySQL connection (default `cargotrace`, user `root`, empty password) |
| `SESSION_DRIVER`, `CACHE_STORE` | `file` by default, no extra tables needed |
| `CARGOTRACE_DELAY_LOW_MAX_HOURS` / `..._MEDIUM_MAX_HOURS` | Delay thresholds (6 / 24 hours) |
| `SHOW_DEMO_ACCOUNTS` | Show demo accounts on the login page |
| `EXTERNAL_API_TIMEOUT`, `WEATHER_BASE_URL`, `GEOCODING_BASE_URL`, `GEOCODING_USER_AGENT` | External API settings (set your own contact in the Nominatim user agent) |
| `CARGOTRACE_SIMULATION`, `CARGOTRACE_SIMULATION_MODE`, `CARGOTRACE_SIMULATION_TIME_SCALE`, `CARGOTRACE_SIMULATION_AUTO_DELIVER` | Auto-shipping demo mode (default off; `time` mode follows the clock, `AUTO_DELIVER` records DELIVERED on arrival) |

### Changing the delay thresholds (per-group scenario)
The delay rule lives in `App\Services\EtaService` and reads its limits from `config/cargotrace.php`, which reads `.env`. No code change is needed:

```env
CARGOTRACE_DELAY_LOW_MAX_HOURS=12      # 0 < delay <= 12 h  => LOW
CARGOTRACE_DELAY_MEDIUM_MAX_HOURS=24   # 12 < delay <= 24 h => MEDIUM, above => HIGH
```

Then run `php artisan config:clear`. Already-saved shipments keep their stored `delay_severity` until their next ETA/tracking update (`EtaService::refresh`); re-run `php artisan migrate:fresh --seed` to re-evaluate the demo data under the new thresholds. `tests/Unit/EtaServiceTest::test_thresholds_are_configurable` covers this behaviour.

## Reports (PDF) and delay reasons
- **Reports** (sidebar, all roles): filter by status, delay, exception, carrier and expected-arrival dates, preview the numbers, then **Download PDF**. Customers only get their own shipments. Each download is written to the audit log.
- PDF engine: `barryvdh/laravel-dompdf` (already in `composer.json`, installed by `composer install`). The PDF language follows the language selected in the browser (Indonesian or English). Without the package the pages still work and the download buttons show a clear message.
- **Delay reason:** a late shipment shows *why* (for example "Incomplete documents", "Held at customs", "Bad weather", "Port congestion") taken from the exception an operator reported on it (`CUSTOMS_HOLD`, `DOCUMENTATION_ISSUE`, `WEATHER_DELAY`, ...). If nobody has reported one yet it says "Reason not recorded yet". It appears in the shipment list, the dashboard "Delayed shipments and reasons" table and the PDF.
- **Dashboard cards and charts are clickable** and open the shipment list already filtered (status, delayed, open exception, carrier, severity).

## REST API
Import `postman/CargoTrace.postman_collection.json` into Postman, run **Login**, and the token is stored automatically. All routes are prefixed with `/api`.

| Area | Endpoints |
|---|---|
| Auth | `POST /register`, `POST /login`, `POST /logout`, `GET /user` |
| Shipments | `GET/POST /shipments`, `GET/PUT/PATCH/DELETE /shipments/{id}` |
| Tracking | `GET/POST /shipments/{id}/tracking` |
| Exceptions | `GET/POST /shipments/{id}/exceptions`, `PATCH /exceptions/{id}` |
| Master data | `GET/POST /carriers`, `GET/POST /locations` (POST admin only), `GET /customers` |
| Other | `GET /dashboard`, `GET /shipments/{id}/weather`, `GET /geocode?q=`, `GET /notifications`, `POST /notifications/read-all` |

List endpoints are paginated on the server (`data`, `links`, `meta`). `GET /shipments` accepts `q`, `status`, `carrier_id`, `origin_id`, `destination_id`, `date_from`/`date_to`, `delayed`, `sort` and `per_page` (max 50). `GET /carriers` accepts `q`, `transport_mode` and `per_page`; `GET /locations` accepts `q`, `type`, `country` and `per_page` (default 25, max 100 for both).

Errors: `401` unauthenticated, `403` forbidden, `404` not found, `422` validation or business-rule violation (`{"message": "...", "errors": {...}}`), `429` rate limited. No stack traces are exposed.

## Web modules and access
| Module | Menu | Access |
|---|---|---|
| Dashboard | Dasbor | all roles (customers see only their own shipments) |
| Shipments (CRUD, tracking, exceptions, delivery confirmation) | Pengiriman | per `ShipmentPolicy` |
| Reports (+ PDF) | Laporan | all roles (customers see only their own shipments) |
| Notifications | Notifikasi | all roles |
| Carriers, Locations, Warehouses, Users | Pengangkut, Lokasi, Gudang, Pengguna | admin |
| Audit log | Log audit | admin |

## Tests
```bash
php artisan test
```
Tests use an in-memory SQLite database (see `phpunit.xml`), so they never touch your MySQL data. They cover: authentication, shipment API (CRUD, validation, authorization), tracking events and invalid status transitions, ETA/delay rule (unit + flow), exceptions, external API success/error/timeout/empty responses, dashboard scoping, locale handling, reports (PDF), delay reasons, the ARRIVED -> confirmed flow with notifications and the demo simulator. The latest run is saved in `docs/test-evidence.txt`. CI: `.github/workflows/tests.yml` runs the suite on every push.

## Security
Password hashing (bcrypt) · auth middleware and server-side authorization (policies, role middleware) · Form Request validation · CSRF tokens on web forms · Blade escaping (XSS) and DOM `textContent` for map popups · Eloquent/query-builder bindings and whitelisted sort columns (SQL injection) · `$fillable` mass-assignment protection (`role` is never mass-assignable) · rate limiting on login, geocoding and API · delivery can only be confirmed by the owner (or an admin), enforced in `TrackingService` · `.env` and API keys are git-ignored.

## Deployment
1. Provision PHP 8.2+, MySQL, Composer (shared hosting with SSH, VPS, or PaaS).
2. Point the web root to `public/`.
3. `composer install --no-dev --optimize-autoloader`, create `.env` with `APP_ENV=production`, `APP_DEBUG=false`, the real `APP_URL`, DB credentials, and `SHOW_DEMO_ACCOUNTS=true` only for the demo.
4. `php artisan key:generate && php artisan migrate --seed --force` (the last migration drops the legacy `payments` table if an older database still has it)
5. Make `storage/` and `bootstrap/cache/` writable. Optionally `php artisan config:cache route:cache`.
6. Set `GEOCODING_USER_AGENT` to your own contact.
7. The service worker needs HTTPS (or localhost).

## Troubleshooting
- **Dashboard cards show "–", the clock does not move, or the map is stuck.** The browser may be serving old JavaScript from the service worker cache. Open DevTools > Application > Service workers > Unregister, then Storage > Clear site data, and reload with Ctrl+F5. When you edit files in `public/js`, bump `CACHE` in `public/sw.js` (currently `cargotrace-v4`).
- **`Table 'users' already exists` on migrate.** Do not mix the SQL import files with `php artisan migrate`. Run `php artisan migrate:fresh --seed`.
- **The map can be zoomed but not dragged.** Unregister the service worker and hard-reload (see the first item). If it persists, run `document.elementFromPoint(...)` at the map centre in the console to find an element covering the map.
- **Report PDF buttons show "PDF engine is not installed".** Run `composer install` (the package is `barryvdh/laravel-dompdf`).
- **Map or charts do not load.** Leaflet, Chart.js and Tailwind are loaded from public CDNs (unpkg, jsDelivr), so an internet connection is required.

## Documentation
- `docs/ERD.md` — ERD, cardinality and design decisions
- `docs/DATA_DICTIONARY.md` — tables and columns
- `docs/ARCHITECTURE.md` — layers and request flow
- `docs/CODE_GUIDE_ID.md` — code guide (Indonesian)
- `docs/DEPLOYMENT.md` — step-by-step deployment and pre-submission checklist
- `docs/ACCEPTANCE_CHECKLIST.md` — each requirement of the UAS brief mapped to its implementation and tests
- `postman/` — API collection

## Scope: the UAS brief and what goes beyond it
Everything required by the brief is implemented and mapped one by one in [`docs/ACCEPTANCE_CHECKLIST.md`](docs/ACCEPTANCE_CHECKLIST.md): roles, shipment lifecycle, tracking timeline, ETA/delay rule with configurable thresholds, exceptions, Leaflet map, external API (Open-Meteo, Nominatim), dashboard, search/filter/pagination, notifications, audit log, REST API with Sanctum, tests, Postman collection and documentation.

Extras that are **not required** by the brief and are documented as such: warehouses (master data), the statistical delay forecast, auto-shipping demo mode, e-mail verification, 7-language UI, PWA and PDF reports. Payments, payment gateways and accounting were removed because the brief puts financial transactions out of scope.

## Known limitations and future work
- Tailwind, Leaflet and Chart.js are loaded from CDNs for simplicity; a production build with Vite would remove the runtime dependency.
- No direct integration with real carriers, customs or port systems; no real-time GPS (out of scope). Positions come from tracking events (operators, an API client or the demo simulator); between two events the vehicle position on the map is an estimate toward the ETA. Demo positions are synthetic.
- Road routes use the **public OSRM demo server** (internet required, best-effort, cached in the browser); sea and air routes are great-circle estimates, not real shipping lanes or flight paths.
- Payments, invoicing and accounting are out of scope of the brief and are **not implemented** (the app never handles money).
- Admin notifications are polled every 15 s (no WebSocket push).
- Reopening a DELIVERED/CANCELLED shipment is not implemented.
- Carriers, locations, warehouses and users have create and activate/deactivate, but no edit or delete screens.
- E-mail notifications and Docker are not included.

## Attribution
- Laravel (MIT), Laravel Sanctum (MIT), PHPUnit (BSD-3)
- Tailwind CSS (MIT), Leaflet (BSD-2), Chart.js (MIT)
- Map tiles and geodata: © OpenStreetMap contributors (ODbL); geocoding via Nominatim; road routes via OSRM public router
- Weather: Open-Meteo (CC BY 4.0, non-commercial use)
- PDF: barryvdh/laravel-dompdf (MIT) with Dompdf (LGPL-2.1)
- **AI usage:** I used **Claude (Anthropic)** as a coding assistant for feature implementation, test drafts, debugging and README/documentation wording. The business rules and requirements come from the lecturer's brief. Every AI-assisted change was run and reviewed by me before committing, and I can explain the main flows (`TrackingService`, `EtaService`, `ShipmentPolicy`, the migrations) and change them live. Code I did not write is limited to the libraries listed above.

## UI theme
The interface uses a matcha green (primary) and sakura pink (accent) palette. All design tokens (colors, font, buttons, cards, form inputs) live in `resources/views/partials/theme.blade.php`. Change the palette there and every page follows.