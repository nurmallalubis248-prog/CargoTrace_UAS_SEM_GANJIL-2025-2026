# Cargo lifecycle, freight fee and finance flow

There is **no online payment gateway** in CargoTrace (it is not required by the brief). The freight fee is calculated
from the cargo weight, payments are records in the `payments` table (seeded demo data), and the app does not process money.

## Status flow (state machine, enforced in `TrackingService`)

```
CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> ARRIVED -> DELIVERED
                          marker moves     |        |          |
                                           |        |          +-- customer pressed "Confirm received"
                                           |        |              -> admin is notified
                                           |        +-- cargo reached the destination: marker STAYS on the map,
                                           |            customer is notified
                                           +-- operator events / GPS / auto-shipping demo mode
CANCELLED is only reachable from CREATED / BOOKED. DELIVERED and CANCELLED are terminal.
```

| Step | Who | Rule | Side effects |
|---|---|---|---|
| IN_TRANSIT | operator / carrier / auto-shipping | valid transition | marker moves for customer and admin |
| ARRIVED | operator / auto-shipping | from IN_TRANSIT only | `actual_arrival_at` = arrival time (delay is computed from it), customer notified |
| DELIVERED | customer ("Confirm received"); admin may do it for the customer | needs ARRIVED | admins get `DELIVERY_CONFIRMED` |
| Settled | admin ("Mark settled") | only PAID payment records | `payout_status = SETTLED`, `settled_at`, audit log |

Operators cannot set DELIVERED: only the customer (or an admin) can.

Exception: in auto-shipping demo mode (`CARGOTRACE_SIMULATION=true` and `CARGOTRACE_SIMULATION_AUTO_DELIVER=true`) the server itself records DELIVERED on arrival (`TrackingService::addEvent(..., system: true)`, callable only from server code, never from user input).

## Freight fee
`fee = max(CARGOTRACE_MIN_FEE, round(weight_kg x CARGOTRACE_RATE_PER_KG))`, in IDR (`PaymentController::amountFor`). Other currencies are display-only (static rates in `config/cargotrace.php`).

## Where things are
- Receipt PDF (customer + staff): `GET /payments/{payment}/receipt` (PAID records only).
- Admin finance report: Admin > Accounting > "Download PDF report" (`GET /admin/accounting/pdf`, filters: paid from/to, payout status), plus CSV export.
- ERP read API: `GET /api/erp/payments` (header `X-ERP-KEY`).
- Live notifications: the sidebar badge polls `GET /notifications/unread` every 15 s and shows a toast for new ones.
- PDF language follows the language selected in the browser (`?lang=en|id`).

## Auto-shipping demo mode (moving cargo)
`CARGOTRACE_SIMULATION=true`: by default the position follows the schedule and the real clock (`CARGOTRACE_SIMULATION_MODE=time`); every page request catches the shipment up, and `php artisan schedule:work` (optional) keeps it moving when nobody is browsing. Each step is a real tracking event (labelled "[Simulation]"). Old mode: `php artisan cargotrace:simulate --mode=step --step=25`.
Real deployments post positions with `POST /api/shipments/{id}/tracking` instead.
