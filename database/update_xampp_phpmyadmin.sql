-- Optional: for phpMyAdmin imports WITHOUT php artisan migrate. Run on database `cargotrace`.
-- (Recommended instead: php artisan migrate:fresh --seed, which also loads the demo data.)
ALTER TABLE carriers ADD COLUMN transport_mode VARCHAR(10) NOT NULL DEFAULT 'SEA' AFTER code;

CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shipment_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  order_id VARCHAR(60) NOT NULL UNIQUE,
  amount BIGINT UNSIGNED NOT NULL,
  status VARCHAR(15) NOT NULL DEFAULT 'PENDING',
  payment_type VARCHAR(30) NULL,
  snap_token VARCHAR(255) NULL,
  redirect_url TEXT NULL,
  raw_notification JSON NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  INDEX payments_status_index (status),
  CONSTRAINT payments_shipment_fk FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
  CONSTRAINT payments_user_fk FOREIGN KEY (user_id) REFERENCES users(id)
);
INSERT INTO migrations (migration, batch) SELECT '2026_01_01_000006_add_transport_mode_to_carriers', MAX(batch) FROM migrations;
INSERT INTO migrations (migration, batch) SELECT '2026_01_01_000007_create_payments_table', MAX(batch) FROM migrations;
