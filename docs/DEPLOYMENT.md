# Deployment and pre-submission checklist

Target: a public URL where a recruiter can log in with a demo account. Any host with PHP 8.2+, MySQL 8 / MariaDB, Composer and the ability to point the web root to `public/` works (VPS, shared hosting with SSH, or a PaaS).

## 1. Prepare the server
1. PHP 8.2+ with `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`.
2. Create an empty MySQL database and a dedicated database user (not `root`).
3. HTTPS enabled (needed by the service worker and the Midtrans webhook).

## 2. Install
```bash
git clone https://github.com/nurmallalubis248-prog/CargoTrace_UAS_SEM_GANJIL-2025-2026.git cargotrace
cd cargotrace
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

## 3. Production `.env`
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
DB_HOST=127.0.0.1
DB_DATABASE=...
DB_USERNAME=...        # dedicated user
DB_PASSWORD=...        # strong password
SHOW_DEMO_ACCOUNTS=true            # demo deployment only
ERP_API_KEY=<random>               # php -r "echo bin2hex(random_bytes(24));"
GEOCODING_USER_AGENT="CargoTrace-UAS (your-email@example.com)"
CARGOTRACE_DELAY_LOW_MAX_HOURS=6   # set to your group's scenario
CARGOTRACE_DELAY_MEDIUM_MAX_HOURS=24
# MIDTRANS_* : sandbox keys only, never commit them
```

## 4. Database, permissions, caches
```bash
php artisan migrate --seed --force
chmod -R ug+rw storage bootstrap/cache
php artisan config:cache && php artisan route:cache
```
Point the web server's document root to `public/`. Use **either** `php artisan migrate` **or** the SQL import files, never both.

## 5. Verify the live site
- [ ] `https://your-domain/login` opens and the demo accounts work (admin, operator, customer).
- [ ] Customer sees only own shipments; opening another customer's shipment returns 403/404.
- [ ] Operator can add a tracking event; an invalid status jump is rejected.
- [ ] Map, weather and geocoding work, and show a friendly message when the provider fails.
- [ ] Dashboard numbers match the data; pagination and filters work.
- [ ] `APP_DEBUG=false`: a wrong URL shows the error page, not a stack trace.
- [ ] `https://your-domain/.env` returns 403/404 (document root is `public/`).

## 6. Fill in the README
Replace `_add your deployed URL here_` with the real URL and complete the **AI usage** line under *Attribution*.

## 7. Test evidence
On your own machine (PHP + Composer installed):
```bash
php artisan migrate:fresh --seed          # must finish with no error
php artisan test | tee docs/test-evidence.txt
```
Commit `docs/test-evidence.txt` and take a screenshot of the passing GitHub Actions run.

## 8. What to submit
Only the GitHub link (plus the live URL and demo accounts). Do **not** zip the working folder: it contains `.env`, `vendor/`, `.git/`, logs and sessions. If a zip is required, use `git archive --format=zip -o cargotrace.zip HEAD`, which contains only tracked files.
