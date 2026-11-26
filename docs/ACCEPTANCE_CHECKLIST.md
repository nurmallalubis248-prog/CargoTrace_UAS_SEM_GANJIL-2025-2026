# Brief compliance checklist

Maps the requirements of the "CargoTrace UAS Final Project Brief" (slides 2-24) to where they are implemented, so a reviewer can verify each item quickly.

## Functional requirements

| Brief requirement | Where it is implemented | Verified by |
|---|---|---|
| Roles: Administrator, Logistics Operator, Customer; authorization on the server (slide 5, 13) | `EnsureRole` middleware, `ShipmentPolicy`, `Gate::authorize` in controllers | `AuthTest`, `ShipmentApiTest`, `TrackingTest`, `ExceptionTest` (access without permission) |
| Shipment lifecycle and validated status transitions; terminal status (slide 6) | `ShipmentStatus::canTransitionTo()`, `TrackingService::addEvent()` | `TrackingTest` (valid flow, invalid transition = 422, terminal DELIVERED) |
| Minimum shipment data (number, customer, carrier, origin, destination, cargo type, weight/volume, expected departure/arrival, status) (slide 6) | `database/migrations/..._create_shipments_tables.php`, `StoreShipmentRequest` | `ShipmentApiTest` |
| Tracking events are historical, ordered by time, with location, coordinates, description and creator; exceptions are distinguishable (slide 7) | `tracking_events` table, `TrackingEvent` model (blocks update), `TrackingService` | `TrackingTest` (timeline order, no future/out-of-order events) |
| Current location from the latest valid event (slide 7) | denormalised `current_*` columns, updated in `TrackingService` | `TrackingTest` (checkpoint updates current location) |
| ETA and delay rule, LOW / MEDIUM / HIGH, configurable thresholds, reason and values shown (slide 8) | `EtaService`, `config/cargotrace.php` (`CARGOTRACE_DELAY_*` env) | `EtaServiceTest`, `DelayFlowTest`, `DelayReasonTest` |
| Exception types, severity, OPEN / INVESTIGATING / RESOLVED, detected and resolved time (slide 8) | `ExceptionService`, `shipment_exceptions` table, `ExceptionType` / `ExceptionStatus` enums | `ExceptionTest` |
| Leaflet + OpenStreetMap map with origin, destination, tracking points, popups and attribution (slide 9) | `resources/views/shipments/show.blade.php`, `public/js/ct-map.js` | manual (see README) |
| External API (geocoding and weather) via the Laravel HTTP Client with timeout, error and empty-data handling; weather is not a delay prediction (slide 9) | `GeocodingService` (Nominatim), `WeatherService` (Open-Meteo) | `ExternalApiTest` |
| Dashboard from database data, search / filter / sort / server-side pagination, in-app notifications, audit log (slide 10) | `DashboardService`, `ShipmentFilterRequest`, `Shipment::scopeFilter`, `NotificationService`, `AuditService` | `DashboardTest`, `ShipmentApiTest` |
| Data model with FK, unique constraints, indexes, timestamps, soft delete only where needed (slide 11) | `database/migrations/`, `docs/ERD.md`, `docs/DATA_DICTIONARY.md` | `php artisan migrate --seed` |
| REST API endpoints (auth, shipments, tracking, carriers, locations, exceptions) (slide 12) | `routes/api.php` | `ShipmentApiTest`, `TrackingTest`, `ExceptionTest` |
| JSON, 200/201/401/403/404/422, pagination, consistent errors without stack traces, Postman collection (slide 13) | `bootstrap/app.php`, API Resources, `postman/CargoTrace.postman_collection.json` | feature tests |
| Service layer instead of logic in controllers (slide 15) | `ShipmentService`, `TrackingService`, `EtaService`, `ExceptionService` in `app/Services` | `docs/ARCHITECTURE.md` |
| Security: password hashing, auth middleware, backend authorization, form requests, CSRF/XSS/SQL injection prevention, mass-assignment protection, no secrets in the repository (slide 16) | see README "Security" section; `.env` is git-ignored, `.env.example` is provided | `AuthTest`, `ShipmentApiTest` |
| Group scenario parameters, e.g. delay threshold 12 / 18 / 24 h (slide 20, 23) | README "Changing the delay thresholds (per-group scenario)" | `EtaServiceTest` |

## Deliverables (slide 18, 22, 24)

| Deliverable | Status |
|---|---|
| Public GitHub repository with meaningful commit history | repository exists; keep commits specific and in English |
| English README (problem, features, setup, tests, deployment) | `README.md` |
| `.env.example` and seed / demo data | `.env.example`, `database/seeders/DatabaseSeeder.php` |
| ERD and data dictionary | `docs/ERD.md`, `docs/DATA_DICTIONARY.md` |
| Architecture diagram | `docs/ARCHITECTURE.md` |
| Postman collection / API documentation | `postman/CargoTrace.postman_collection.json` |
| Automated tests and test evidence | `tests/`, CI in `.github/workflows/tests.yml`; **regenerate `docs/test-evidence.txt` with `php artisan test` before submitting** |
| Known limitations and future work | README "Known limitations and future work" |
| Attribution for libraries and external code, AI usage | README "Attribution" |
| Live deployed application with a demo account | **TODO: deploy (see `docs/DEPLOYMENT.md`) and put the URL in the README** |
| Screenshots and short demo video | optional, not included |
| Docker | advanced / optional, not included |

## Out of scope in the brief (slide 4)
No real carrier/customs integration, no payments or financial transactions (payments, payment gateways and accounting are **not implemented**), no real GPS device tracking, no mandatory ML. The auto-shipping demo mode generates tracking events from the schedule and is a demonstration feature, not real tracking.
