# Data dictionary

All timestamps are stored and displayed in **UTC**.

## users
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name, email | string | email unique |
| email_verified_at | timestamp null | set when a self-registered customer verifies the e-mail link |
| password | string | bcrypt hash (`hashed` cast) |
| role | string(20) | `admin`, `operator`, `customer` (not mass-assignable) |

## carriers
| Column | Type | Notes |
|---|---|---|
| name | string | |
| code | string(10) unique | e.g. SCAC-style code |
| transport_mode | string(10) | SEA, AIR, ROAD (decides the map icon and route type) |
| contact_email | string null | |
| is_active | bool | inactive carriers cannot be chosen for new shipments |

## locations
| Column | Type | Notes |
|---|---|---|
| name, code (unique), country (ISO-2) | string | |
| type | string | PORT, AIRPORT, WAREHOUSE, OTHER |
| latitude / longitude | decimal(10,7) | validated -90..90 / -180..180 |
| is_active | bool | |

## shipments
| Column | Type | Notes |
|---|---|---|
| shipment_number | string(30) unique | generated `CTyymmdd-XXXXX` |
| reference_number | string null | customer PO / booking reference (searchable) |
| customer_id, carrier_id, origin_id, destination_id, created_by | FK | |
| warehouse_id | FK null | optional warehouse the cargo is assigned to (`nullOnDelete`) |
| cargo_type | string | GENERAL, CONTAINER_FCL, CONTAINER_LCL, BULK, REFRIGERATED, LIQUID, HAZARDOUS |
| weight_kg, volume_cbm | decimal | weight > 0 |
| expected_departure_at, expected_arrival_at | datetime | plan; arrival must be after departure |
| estimated_arrival_at | datetime | current ETA (changed by ETA_UPDATE events) |
| actual_arrival_at | datetime null | set when status becomes ARRIVED (delay is evaluated against it) |
| current_status | string | CREATED, BOOKED, PICKED_UP, IN_TRANSIT, ARRIVED, DELIVERED, CANCELLED |
| current_latitude / longitude / current_location_label / last_event_at | snapshot | from the latest event that has coordinates |
| is_delayed / delay_hours / delay_severity | snapshot | result of `EtaService` |
| deleted_at | soft delete | |

## shipment_items
`shipment_id`, `description`, `quantity` (>=1), `weight_kg`, `volume_cbm`.

## tracking_events (append-only)
| Column | Notes |
|---|---|
| shipment_id | FK, cascade |
| event_type | STATUS_UPDATE, CHECKPOINT, ETA_UPDATE, EXCEPTION (EXCEPTION is created only by the exception workflow) |
| status | only for STATUS_UPDATE, must be a valid transition |
| location_id, location_label, latitude, longitude | where it happened |
| occurred_at | not in the future, not earlier than the latest event |
| description, is_exception, created_by | |

## shipment_exceptions
`type` (CUSTOMS_HOLD, WEATHER_DELAY, PORT_CONGESTION, DAMAGED, MISROUTED, DOCUMENTATION_ISSUE), `severity` (LOW, MEDIUM, HIGH, CRITICAL), `status` (OPEN, INVESTIGATING, RESOLVED), `description`, `detected_at`, `resolved_at`, `resolution_notes`, `reported_by`.

## notifications
`user_id`, `shipment_id` (nullable), `type`, `message`, `read_at`.

## audit_logs
`user_id` (nullable), `action` (e.g. `shipment.created`), `entity_type`, `entity_id`, `changes` (JSON), `ip_address`, `created_at`.

## warehouses
| Column | Type | Notes |
|---|---|---|
| name | string | |
| code | string(12) unique | |
| location_id | FK null | linked location (`nullOnDelete`) |
| capacity_kg | unsigned int | used for the utilisation figure in the admin warehouse page |
| is_active | bool | |

## payments
Freight-fee records (no online payment gateway). Used by Accounting, the receipt PDF and the ERP read API.
| Column | Type | Notes |
|---|---|---|
| shipment_id, user_id | FK | cascade / restrict |
| order_id | string(60) unique | e.g. `CT-1-DEMO-001` |
| amount | unsigned bigint | IDR |
| status | string(15) | PENDING, PAID, FAILED, EXPIRED, CANCELLED (indexed) |
| payout_status | string(20) | PENDING_SETTLEMENT, SETTLED (admin action, audited) |
| payment_type | string(30) null | e.g. qris, bank_transfer |
| paid_at, settled_at | datetime null | |
| snap_token, redirect_url, raw_notification | null | legacy columns from an earlier gateway integration; unused |
