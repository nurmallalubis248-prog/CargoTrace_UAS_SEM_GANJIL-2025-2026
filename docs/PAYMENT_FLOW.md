# Cargo lifecycle, payment and finance flow

## Status flow (state machine, enforced in `TrackingService`)

```
CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> ARRIVED -> DELIVERED
                          marker moves     |        |          |
                                           |        |          +-- customer pressed "Confirm received" (needs a PAID payment)
                                           |        |              -> cargo leaves the live map, admin is notified
                                           |        +-- cargo reached the destination: marker STAYS on the map,
                                           |            the freight invoice opens, customer is notified
                                           +-- operator events / GPS / demo simulator
CANCELLED is only reachable from CREATED / BOOKED. DELIVERED and CANCELLED are terminal.
```

| Step | Who | Rule | Side effects |
|---|---|---|---|
| IN_TRANSIT | operator / carrier / simulator | valid transition | marker moves for customer and admin |
| ARRIVED | operator / simulator | from IN_TRANSIT only | `actual_arrival_at` = arrival time (delay is computed from it), customer notified |
| Pay | customer | any active status (CREATED ... ARRIVED), not after DELIVERED/CANCELLED; once | Midtrans Snap shown as an **in-app popup** (`snap.pay`, no redirect to midtrans.com): QRIS, BCA/BNI/BRI/Permata/CIMB VA, Mandiri bill, e-wallet, card |
| PAID | Midtrans webhook (signature checked) or "refresh status" | idempotent, a paid payment is never downgraded | admins get `PAYMENT_RECEIVED`, customer gets `PAYMENT_CONFIRMED`, ERP webhook, payout status = PENDING_SETTLEMENT |
| DELIVERED | customer ("Confirm received"); admin may do it for the customer | needs ARRIVED **and** a PAID payment | admins get `DELIVERY_CONFIRMED`, marker removed from the active map |
| Settled | admin ("Mark settled") | only PAID payments | `payout_status = SETTLED`, `settled_at`, audit log |

Operators cannot set DELIVERED: only the customer (or an admin) can, and never before the payment is PAID.

## Where things are
- Receipt PDF (customer + staff): `GET /payments/{payment}/receipt` (PAID only).
- Admin finance report: Admin > Accounting > "Download PDF report" (`GET /admin/accounting/pdf`, filters: paid from/to, payout status).
- Live notifications: the sidebar badge polls `GET /notifications/unread` every 15 s and shows a toast for new ones.
- PDF language follows the language selected in the browser (`?lang=en|id`).

## Midtrans webhook (local testing)
Midtrans must reach `POST /midtrans/notification` over public HTTPS. Use a tunnel (ngrok / cloudflared) and set the
"Payment Notification URL" in the Midtrans dashboard. Without a tunnel, the "refresh status" button pulls the status instead.

## Demo mode (moving cargo)
`CARGOTRACE_SIMULATION=true` and `php artisan schedule:work` move every active shipment along its route every minute by
recording real tracking events (labelled "[Simulation]"). The cargo stops at ARRIVED. One-off run: `php artisan cargotrace:simulate --step=25`.
Real deployments post positions with `POST /api/shipments/{id}/tracking` instead.
