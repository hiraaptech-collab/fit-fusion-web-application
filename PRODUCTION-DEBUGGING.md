# FitFusion production repair

Observed on 2026-10-15: live GET /api/config returned HTTP 503 with Retry-After: 30.
No production migrations, pushes, deploys, seeds, or database changes were performed.

## Findings
- bootstrap/app.php deliberately translates QueryException to HTTP 503. SQLSTATE 42S02 means a table is missing, rather than a powered-off database. The exact production table is still unverified.
- /api/config queries gym_settings. Web sessions, throttle cache and cache locks may also query tables before controllers run. AI provider reservations query gym_settings and audit_logs; saved client chat additionally needs ai_conversations and ai_messages.
- All these tables have migrations, in dependency order. Do not create duplicate migrations for tables already defined. A partially applied MySQL migration can leave tables without a migration ledger entry because DDL is not transactional.
- gym_settings id=1 was only created by DatabaseSeeder. AI reservations lock it with firstOrFail; settings screens also require it. The new additive migration initializes only an absent row and retains it on rollback.
- Production now requires provider replies. Existing canned fallback is retained for development compatibility; missing keys/provider failures no longer masquerade as successful production AI. Guest access remains controlled by AI_ALLOW_GUEST_PROVIDER. Blueprint enables bounded public provider access; set false to require sign-in.
- Docker already includes PDO MySQL, mbstring, OpenSSL and the Aiven CA. Blueprint now explicitly selects the CA; cached local bootstrap PHP files are excluded from Docker builds.
- /up is a liveness check, not database/provider readiness. Use the read-only command below for readiness.
- No visual assets, templates, styles, panel routes or authentication logic were changed.

## Read-only production diagnosis
On the NEW application revision, inside /var/www/html (Render shell if available, or a trusted machine/container with production environment injected):
```sh
php artisan fitfusion:check-production
php artisan migrate:status
```
The first command reports missing tables, key columns, the settings row and pending migrations; production checks also verify active MySQL TLS and key presence. It never calls the AI provider or prints credentials.
Do not run local test/audit scripts against production.

If tables already exist but their creation migration is pending, stop. Compare SHOW CREATE TABLE with that migration and reconcile the partial migration on a restored backup first. Do not blindly retry, drop tables, or insert migration ledger entries.

## Environment
Preserve the existing APP_KEY; changing it invalidates sessions/encrypted values.
Set APP_ENV=production, APP_DEBUG=false, APP_URL=https://fit-fusion-web-application.onrender.com.
DB_CONNECTION=mysql, DB_DATABASE=fitfusion_db, DB_HOST/DB_PORT/DB_USERNAME/DB_PASSWORD must match Aiven service details (the port is often not 3306).
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/aiven-ca.pem. Confirm the bundled CA matches this Aiven service.
Clear conflicting DB_URL if using individual DB_* variables. Keep TLS verification enabled.
Use SESSION_DRIVER=database, CACHE_STORE=database once schema is ready. Keep SESSION_DOMAIN empty; secure cookies enabled.
AI_REQUIRE_PROVIDER=true; AI_API_KEY must be a valid existing Groq key; AI_BASE_URL=https://api.groq.com/openai; AI_MODEL=openai/gpt-oss-20b.
Set AI_ALLOW_GUEST_PROVIDER=true for the public chatbot, or false to require login. Existing per-IP, guest, global and concurrency quotas still apply.
RUN_MIGRATIONS=false for routine restarts. Configure a working SMTP mailer for password resets; delivery cannot be established from code alone.

## Backup and approval before migration
Use an Aiven backup or a verified logical backup first. Example with the MySQL 8 client; substitute service host/port/user and CA path, and enter the password at the prompt:
```sh
mysqldump --host=HOST --port=PORT --user=USER --password --ssl-mode=VERIFY_CA --ssl-ca=/path/to/ca.pem --single-transaction --no-tablespaces --set-gtid-purged=OFF --result-file=fitfusion-before-repair.sql fitfusion_db
```
Verify that the backup restores in a separate database. Review every pending migration on that restored copy.
Only AFTER explicit production migration approval, and only if migration status and schema agree:
```sh
php artisan migrate --force
php artisan config:cache
php artisan view:cache
php artisan fitfusion:check-production
php artisan migrate:status
```
No migrate:fresh, refresh, reset, rollback or db:seed is needed. Keep production records.
If Render Free offers no shell, after approval set RUN_MIGRATIONS=true for one planned deployment with the reviewed revision, then set it back to false. Do not enable it before schema reconciliation/backup.

## Git and deployment (not executed)
Review:
```sh
git diff --check
git diff
git status --short
```
After approval, stage only intended files (avoid the pre-existing unrelated untracked file):
```sh
git add .dockerignore render.yaml PRODUCTION-DEBUGGING.md laravel/config/services.php laravel/app/Services/FusionAiService.php laravel/app/Console/Commands/CheckProduction.php laravel/database/migrations/2026_10_15_000001_initialize_gym_settings.php laravel/tests/verify-production-regressions.php
git commit -m "Fix production AI failure handling and schema readiness"
git push origin HEAD
```
In Render, select the existing service, verify environment variables, then Manual Deploy > Deploy latest commit, only after deployment approval. Do not create a second service from the Blueprint.
Updating render.yaml alone does not necessarily update a manually configured service.

## Verify after deployment
```sh
curl -i https://fit-fusion-web-application.onrender.com/up
curl -i -H "Accept: application/json" https://fit-fusion-web-application.onrender.com/api/config
```
In a browser, open /fusion-ai.html and send a question; confirm /api/chat returns 200 with a provider reply (no source=local). Requests require the page session cookie and CSRF token; a bare curl POST will correctly fail CSRF.
Test sign-in, logout, registration, all three dashboards and permissions, client saved chat and SMTP password reset. No live credentials were available for these checks.

## VS Code
Both bmewburn.vscode-intelephense-client-1.18.5 and zobo.php-intellisense-1.3.3 are installed. Their competing language analysis is a plausible cause of the reported PDO stub diagnostics; the actual 42 diagnostic messages were not provided.
PDO.php under the extension directory is an IDE stub, not an application source file. Do not edit it or load it into the app.
Workspace settings now target PHP 8.2 and exclude generated directories; .vscode is already ignored by Git, so this is a local fix.
Disable PHP IntelliSense (zobo) for this workspace, keep Intelephense enabled, then run Intelephense: Clear Cache and Developer: Reload Window. Close the extension PDO.php tab.
"Error while fetching extensions" concerns VS Code Marketplace/network access. Check the Extensions output log and proxy/certificate/network configuration; do not disable TLS verification. The Marketplace failure was not reproduced here.

## Local verification results (2026-10-15)
- PHP syntax: 110 application/config/route/migration/bootstrap files passed, plus the new regression script.
- Existing Fusion AI suite: 149 checks passed with fake HTTP provider responses and dummy keys.
- Database/auth suite: 779 checks passed; 35 tables, 44 foreign keys, 68 relationships queried; test accounts rolled back.
- Management suite: 191 checks passed; database snapshot preserved on serial rerun.
- Reviews suite: 94 checks passed; database snapshot preserved.
- New production regressions: 11 passed using SQLite memory only and HTTP fakes. Usage accounting is stubbed in this small suite; existing integration suites exercise database accounting.
- The first management preservation check was invalidated by concurrent chatbot audit writes in the same isolated local database. Serial rerun passed without application changes.
- Read-only diagnostic tested on the isolated local schema: connection OK, correctly reported the new pending initialization migration and returned failure status.
- git diff --check passed.
- Live read-only recheck: /up HTTP 200; /api/config HTTP 503, code DATABASE_UNAVAILABLE.
- Docker is unavailable locally. Container build/startup, actual Aiven schema/TLS, live provider authentication/model availability, browser layout/interaction, Marketplace connectivity and production SMTP remain unverified.
- Existing design files were not modified. These checks do not prove every production feature is fixed.

Run local regression suites from laravel/ (the existing isolated audit database fixture must already exist):
```powershell
& C:/xampp/php/php.exe tests/verify-production-regressions.php
& C:/xampp/php/php.exe tests/run-isolated-audit.php verify-fusion-ai.php
& C:/xampp/php/php.exe tests/run-isolated-audit.php verify-database-auth.php
& C:/xampp/php/php.exe tests/run-isolated-audit.php verify-gym-management.php
& C:/xampp/php/php.exe tests/run-isolated-audit.php verify-client-reviews.php
```
Run these sequentially because some suites compare complete database snapshots.
