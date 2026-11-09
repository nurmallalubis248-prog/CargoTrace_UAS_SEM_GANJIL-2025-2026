# CargoTrace — Global Shipment Visibility & Tracking Platform

Web Programming 2 · Final Project (UAS). A Laravel application that lets logistics companies and their customers follow shipments across the world: lifecycle, tracking timeline, live map, ETA/delay detection, delay forecast, exception management, payments, accounting, warehouses, dashboard, multi-language UI and a REST API.

> **Repository:** https://github.com/nurmallalubis248-prog/CargoTrace_UAS_SEM_GANJIL-2025-2026
> **Live demo:** _add your deployed URL here after following [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)_ (demo accounts are listed below)

## Problem and solution
Shipment information is scattered, customers cannot see the latest status, delays and exceptions are invisible, and operators have no place to prioritise follow-ups. CargoTrace centralises shipments, carriers, locations, warehouses and tracking events, shows the journey on a map and timeline, flags and forecasts delays with deterministic rules, lets operators manage exceptions, and lets customers pay the freight online once the cargo has arrived, confirm receipt and download a PDF receipt.

## Features

### Shipments and tracking
- **Roles:** Administrator, Logistics Operator, Customer. Authorization is enforced on the server (policies + route middleware) for every page and API endpoint.
- **Shipment CRUD** (create, list, detail, edit, delete) with search, filter, sort and server-side pagination by number/reference, status, carrier, origin, destination, date range and delay.
- **Shipment lifecycle** with a validated state machine: `CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> ARRIVED -> DELIVERED` (`CANCELLED` from CREATED/BOOKED). Terminal states cannot be reopened.
  - `ARRIVED`: the cargo reached the destination. The arrival time is stored then (the delay is computed from it), the marker **stays on the live map** and the freight invoice opens.
  - `DELIVERED`: only after the freight payment is `PAID` **and** the customer pressed **Confirm received** (an admin may do it on the customer's behalf; operators cannot). Then the cargo leaves the active map. Details: [`docs/PAYMENT_FLOW.md`](docs/PAYMENT_FLOW.md).
- **Tracking events** (append-only history), chronological timeline, normal vs. exception events, current location from the latest valid event. An event cannot be in the future or earlier than the latest event.
- **Cargo types:** general, container (FCL/LCL), bulk, refrigerated, liquid, hazardous.

### Delay detection and forecast
- **ETA / delay rule:** `delay = ETA (or actual arrival) - expected arrival`; `<= 6 h` LOW, `<= 24 h` MEDIUM, `> 24 h` HIGH. Thresholds are configurable in `.env`. The UI shows the reason and the values used.
- **Delay forecast** (dashboard table, `ForecastService`): a statistical model, not an external AI service. `predicted delay = max(current delay, 0.6 x average delay of the same carrier + lane) + penalty of open exceptions`. Confidence grows with the number of historical shipments on the lane. It is an estimate, not a guarantee.

### Exceptions
- CUSTOMS_HOLD, WEATHER_DELAY, PORT_CONGESTION, DAMAGED, MISROUTED, DOCUMENTATION_ISSUE; severity; `OPEN -> INVESTIGATING -> RESOLVED`; detection and resolution time stored.

### Dashboard (Chart.js + Fetch)
- Totals, in transit, delivered, delayed, open exceptions, delay rate, exception rate, on-time delivery rate, average delay and average exception resolution time, revenue received, shipments by status / carrier, delays by severity, forecast table — all computed from the database.
- **Live map** (Leaflet + OpenStreetMap) with plane / truck / ship markers, destination pin, latitude/longitude grid and cursor coordinates; drag with the mouse, zoom with the wheel.
- **Live route** (shipment page, and on the dashboard for the selected shipment): road shipments follow **real roads** (OSRM), sea/air use great-circle estimates; the part already travelled is drawn solid, the rest faded; the vehicle keeps moving toward the ETA with a "remaining km" label, a speed selector (Live / x60 / x600 / x6000 simulation) and a "show vehicle" button. The position between two scans is an **estimate**, not GPS.
- Cards and charts are **clickable** (they open the filtered shipment list) and a table lists delayed shipments with their **reason**.
- Live clock and calendar that follow the selected country's time zone.

### Master data and administration (admin)
- **Carriers** (with transport mode AIR / ROAD / SEA), **Locations** (geocoding via Nominatim), **Warehouses** (code, location, capacity) and **Users** (create, change role). Carriers, locations and warehouses can be activated / deactivated.
- **Accounting:** payment ledger, revenue / pending / failed totals, monthly revenue, **payout (settlement) status** (`PENDING_SETTLEMENT` / `SETTLED`, admin button "Mark settled"), **CSV export** and a **PDF financial report** (filters: paid from/to, payout status).
- **Audit log** (actor, action, time, entity) and in-app **notifications**. The sidebar badge refreshes every 15 s; admins are notified when a payment is received and when the customer confirms receipt, customers when their cargo arrives and when their payment is confirmed.

### Payments (Midtrans Snap, sandbox)
- Payment opens as a Snap popup inside the app (the customer stays on CargoTrace; requires `MIDTRANS_CLIENT_KEY`).
- Freight fee = weight x `CARGOTRACE_RATE_PER_KG` (default Rp 5.000), at least `CARGOTRACE_MIN_FEE` (default Rp 50.000). **Payment is open at any active status** (not after `DELIVERED` or `CANCELLED`); confirming receipt still needs `ARRIVED` + a PAID payment.
- QRIS, e-wallets, virtual accounts and cards through Midtrans Snap. Payment status can be refreshed manually (works on localhost) or updated by the signed webhook `POST /midtrans/notification` (SHA-512 signature verified, CSRF-exempt).
- After `PAID`: the customer can **download a PDF receipt** and press **Confirm received**; admins get an instant notification and the payment enters the ledger as `PENDING_SETTLEMENT`.
- Optional push of a `payment.paid` event to an external ERP (`ERP_WEBHOOK_URL`); it never breaks the payment flow.

### Demo mode (moving cargo)
Set `CARGOTRACE_SIMULATION=true` and run `php artisan schedule:work`: every minute each active shipment advances along its route by `CARGOTRACE_SIMULATION_STEP` percent by recording **real tracking events** (same rules, notifications and audit log as an operator; descriptions start with "[Simulation]"). The cargo stops at `ARRIVED`; paying and confirming is the customer's job. One-off: `php artisan cargotrace:simulate --step=25`. Real deployments post positions with `POST /api/shipments/{id}/tracking`.

### Language, currency and PWA
- **Languages (whole app, all roles):** Indonesian, English, Chinese, Japanese, Spanish, French, Arabic (RTL). Dictionary: `public/js/ct-dict.js`; engine: `public/js/ct-i18n.js`.
- **Country selector:** picks a country, switches the language and filters the dashboard map.
- **Display currency:** IDR, USD, EUR, CNY, JPY, SGD, MYR, SAR (static approximate rates in `config/cargotrace.php`; payments are always charged in IDR).
- **PWA:** installable ("Pasang aplikasi" button), `public/sw.js` service worker, `public/manifest.webmanifest`.

### External APIs (Laravel HTTP Client)
- Open-Meteo weather (shipment page) and Nominatim geocoding (admin location form), with timeouts, HTTP-error and empty-data handling. Weather is informational only and is not used to predict delays.

## Tech stack
PHP 8.2+ (8.3 recommended) · Laravel 12 · MySQL / MariaDB (XAMPP) · Eloquent + migrations/seeders · Blade · Tailwind CSS (CDN) · Vanilla JS + Fetch · Leaflet + OpenStreetMap · Chart.js · Laravel Sanctum · Midtrans Snap · barryvdh/laravel-dompdf · OSRM routing · PHPUnit · Postman.

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
| `CARGOTRACE_RATE_PER_KG`, `CARGOTRACE_MIN_FEE` | Freight fee rule (IDR) |
| `SHOW_DEMO_ACCOUNTS` | Show demo accounts on the login page |
| `EXTERNAL_API_TIMEOUT`, `WEATHER_BASE_URL`, `GEOCODING_BASE_URL`, `GEOCODING_USER_AGENT` | External API settings (set your own contact in the Nominatim user agent) |
| `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION` | Midtrans credentials (never commit real keys) |
| `CARGOTRACE_SIMULATION`, `CARGOTRACE_SIMULATION_STEP` | Demo mode: move active shipments every minute (default off, 10 % per step) |
| `ERP_API_KEY`, `ERP_WEBHOOK_URL` | ERP integration key (empty = `/api/erp/*` disabled; generate a random value on every deployment) and optional outgoing webhook |

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

## Payments with Midtrans (sandbox money)
1. Create a free account at https://dashboard.sandbox.midtrans.com and copy the **Server Key** and **Client Key** (Settings > Access Keys).
2. Put them in `.env` (`MIDTRANS_SERVER_KEY=SB-Mid-server-...`, `MIDTRANS_CLIENT_KEY=SB-Mid-client-...`, `MIDTRANS_IS_PRODUCTION=false`), then run `php artisan config:clear`.
3. Open a shipment whose status is `ARRIVED` (run the demo mode or add the events as operator) and click **Pay now**.
4. Simulate the payment at https://simulator.sandbox.midtrans.com, then click **refresh status** (no webhook needed on localhost).
5. Then, as the customer, click **Confirm received** (enabled only after `PAID`) and download the receipt PDF. As admin, see the payment in **Accounting**, mark it settled and export the PDF report.
6. Optional webhook (needs a public URL, e.g. ngrok): set Settings > Payment > Notification URL to `https://<your-host>/midtrans/notification`.

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
| ERP | `GET /erp/shipments`, `GET /erp/payments` (header `X-ERP-KEY`) |

Errors: `401` unauthenticated, `403` forbidden, `404` not found, `422` validation or business-rule violation (`{"message": "...", "errors": {...}}`), `429` rate limited. No stack traces are exposed.

## Web modules and access
| Module | Menu | Access |
|---|---|---|
| Dashboard | Dasbor | all roles (customers see only their own shipments) |
| Shipments (CRUD, tracking, exceptions, payment) | Pengiriman | per `ShipmentPolicy` |
| Reports (+ PDF) | Laporan | all roles (customers see only their own shipments) |
| Notifications | Notifikasi | all roles |
| Carriers, Locations, Warehouses, Users | Pengangkut, Lokasi, Gudang, Pengguna | admin |
| Accounting (ledger, payout status, CSV + PDF export) | Akuntansi | admin |
| Audit log | Log audit | admin |

## Tests
```bash
php artisan test
```
Tests use an in-memory SQLite database (see `phpunit.xml`), so they never touch your MySQL data. They cover: authentication, shipment API (CRUD, validation, authorization), tracking events and invalid status transitions, ETA/delay rule (unit + flow), exceptions, external API success/error/timeout/empty responses, dashboard scoping, locale handling, reports (PDF), delay reasons, Midtrans payments and webhook (signature, idempotency, receipts), the ERP API, the ARRIVED -> PAID -> confirmed flow with notifications, the finance report and the demo simulator. The latest run is saved in `docs/test-evidence.txt`. CI: `.github/workflows/tests.yml` runs the suite on every push.

## Security
Password hashing (bcrypt) · auth middleware and server-side authorization (policies, role middleware) · Form Request validation · CSRF tokens on web forms · Blade escaping (XSS) and DOM `textContent` for map popups · Eloquent/query-builder bindings and whitelisted sort columns (SQL injection) · `$fillable` mass-assignment protection (`role` is never mass-assignable) · rate limiting on login, geocoding, payment and API · Midtrans webhook signature check (idempotent, a paid payment is never downgraded) · delivery can only be confirmed by the owner (or an admin) after a PAID payment, enforced in `TrackingService` · receipts only for PAID payments of shipments the user may view · ERP endpoints protected by `X-ERP-KEY` · `.env` and API keys are git-ignored.

## Deployment
1. Provision PHP 8.2+, MySQL, Composer (shared hosting with SSH, VPS, or PaaS).
2. Point the web root to `public/`.
3. `composer install --no-dev --optimize-autoloader`, create `.env` with `APP_ENV=production`, `APP_DEBUG=false`, the real `APP_URL`, DB credentials, and `SHOW_DEMO_ACCOUNTS=true` only for the demo.
4. `php artisan key:generate && php artisan migrate --seed --force` (includes the payout columns migration)
5. Make `storage/` and `bootstrap/cache/` writable. Optionally `php artisan config:cache route:cache`.
6. Set `GEOCODING_USER_AGENT` to your own contact and replace the ERP demo key.
7. The service worker and Midtrans webhook need HTTPS (or localhost).

## Troubleshooting
- **Dashboard cards show "–", the clock does not move, or the map is stuck.** The browser may be serving old JavaScript from the service worker cache. Open DevTools > Application > Service workers > Unregister, then Storage > Clear site data, and reload with Ctrl+F5. When you edit files in `public/js`, bump `CACHE` in `public/sw.js` (currently `cargotrace-v3`).
- **`Table 'users' already exists` on migrate.** Do not mix the SQL import files with `php artisan migrate`. Run `php artisan migrate:fresh --seed`.
- **The map can be zoomed but not dragged.** Unregister the service worker and hard-reload (see the first item). If it persists, run `document.elementFromPoint(...)` at the map centre in the console to find an element covering the map.
- **Receipt / report PDF buttons show "PDF engine is not installed".** Run `composer install` (the package is `barryvdh/laravel-dompdf`).
- **Map or charts do not load.** Leaflet, Chart.js and Tailwind are loaded from public CDNs (unpkg, jsDelivr), so an internet connection is required.

## Documentation
- `docs/ERD.md` — ERD, cardinality and design decisions
- `docs/DATA_DICTIONARY.md` — tables and columns
- `docs/ARCHITECTURE.md` — layers and request flow
- `docs/CODE_GUIDE_ID.md` — code guide (Indonesian)
- `docs/DEPLOYMENT.md` — step-by-step deployment and pre-submission checklist
- `docs/PAYMENT_FLOW.md` — cargo lifecycle, payment, confirmation, receipts and finance flow
- `postman/` — API collection

## Known limitations and future work
- Tailwind, Leaflet and Chart.js are loaded from CDNs for simplicity; a production build with Vite would remove the runtime dependency.
- No direct integration with real carriers, customs or port systems; no real-time GPS (out of scope). Positions come from tracking events (operators, an API client or the demo simulator); between two events the vehicle position on the map is an estimate toward the ETA. Demo positions are synthetic.
- Road routes use the **public OSRM demo server** (internet required, best-effort, cached in the browser); sea and air routes are great-circle estimates, not real shipping lanes or flight paths.
- Payout (settlement) status is recorded manually by an admin; it is not reconciled automatically with Midtrans settlement reports.
- Admin notifications are polled every 15 s (no WebSocket push).
- Display currency rates are static approximations.
- Reopening a DELIVERED/CANCELLED shipment is not implemented.
- Carriers, locations, warehouses and users have create and activate/deactivate, but no edit or delete screens.
- E-mail notifications and Docker are not included.

## Attribution
- Laravel (MIT), Laravel Sanctum (MIT), PHPUnit (BSD-3)
- Tailwind CSS (MIT), Leaflet (BSD-2), Chart.js (MIT)
- Map tiles and geodata: © OpenStreetMap contributors (ODbL); geocoding via Nominatim; road routes via OSRM public router
- Weather: Open-Meteo (CC BY 4.0, non-commercial use)
- Payments: Midtrans (sandbox)
- PDF: barryvdh/laravel-dompdf (MIT) with Dompdf (LGPL-2.1)
- **AI usage:** _Fill in the bracketed parts so they match what really happened, then delete this line._ I used **[tool name, e.g. Claude / ChatGPT / Copilot]** for **[e.g. initial project scaffold, test drafts, README wording, debugging]**. I did **not** use it for **[e.g. the database design / business rules]**. Every AI-assisted file was run, tested and reviewed by me before committing, and I can explain the main flows (`TrackingService`, `EtaService`, `ShipmentPolicy`, the migrations) and change them live. Code I did not write is limited to the libraries listed above.

## UI theme
The interface uses a matcha green (primary) and sakura pink (accent) palette. All design tokens (colors, font, buttons, cards, form inputs) live in `resources/views/partials/theme.blade.php`. Change the palette there and every page follows.