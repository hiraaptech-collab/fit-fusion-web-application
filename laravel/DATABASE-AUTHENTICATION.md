# FitFusion Phase 4.4 — database and authentication

Phase 4.4 is complete locally. The existing Laravel application and `fitfusion_db` were retained. No dashboards or Phase 4.5 work were started. All code changes are inside `laravel/`; the original 699 Node/frontend files are unchanged.

## Database foundation

The application uses the existing XAMPP MariaDB 10.4.32 server at `127.0.0.1:3306`. Laravel uses its `mariadb` connection, the separate `fitfusion_db` database, and `fitfusion_app@localhost`. The password is stored privately in `.env`, not in this guide. Permissions are limited to this database, with no global privileges or permission to grant privileges to others. No server was installed and no global XAMPP settings were changed.

All 34 tables use InnoDB and have primary keys. There are 42 foreign keys and 65 tested Eloquent relationships. All eight existing migrations are already applied; the completion work did not rerun or replace the schema.

| Area | Tables |
| --- | --- |
| Accounts and staff | users, client_profiles, manager_permissions, trainers, workers, staff_schedules, staff_attendances |
| Single gym settings | gym_settings |
| Memberships and finance | membership_plans, memberships, payments, refunds, expenses |
| Fitness | workout_categories, exercises, workout_plans, workout_plan_exercises, diet_plans, client_plan_assignments, fitness_progress |
| Communication | messages, inquiries, ai_conversations, ai_messages, notifications, audit_logs |
| Laravel infrastructure | password_reset_tokens, sessions, cache, cache_locks, jobs, job_batches, failed_jobs, migrations |

The single gym settings row has ID 1. There are no tenant IDs or shared gym databases. Trainers and workers are staff records, not dashboard roles. Sessions and cache currently use files; the infrastructure tables remain available for future configuration. AI history tables exist, but the public chatbot does not save conversations yet.

The models in `app/Models/` use readable fillable fields, casts and relationship methods. The five gym migrations are `2026_10_10_000001` through `000005`; the three stock Laravel migrations provide the framework tables. `User` uses Laravel's notification support for the notifications table.

## Authentication and permissions

Public registration always creates an active Client and its client profile. Submitted role, activation and permission fields cannot elevate an account. Passwords are hashed with Laravel's hashed cast. New passwords require at least 12 characters, upper/lowercase and a number, and cannot exceed bcrypt's 72-byte limit.

Login regenerates the session, checks account activation and applies both IP and email/IP attempt limits. Logout invalidates the session and regenerates its CSRF token. Inactive accounts lose existing authenticated access. Password reset changes the hash and remember token, making earlier authenticated sessions invalid on their next request.

| Role | Authorization foundation |
| --- | --- |
| Owner | Full gym permission gates; exclusively creates Managers and changes their permissions |
| Manager | Only explicit Owner-approved permissions; no permission to create Managers or modify the Owner |
| Client | Personal client profile and existing public fitness features; no management permissions |

The 12 grantable Manager permissions are in `config/gym.php`: trainers.manage, workers.manage, staff.schedules, staff.attendance, clients.manage, memberships.manage, workouts.manage, diets.manage, messages.manage, reports.view, finance.view and finance.manage. Financial access requires an explicit grant. Permission revocation takes effect immediately.

Authentication pages reuse the original navbar, footer, font and cream/gold/brown colors through `layouts/auth.blade.php` and scoped `public/assets/auth.css`. The `/account` page confirms sign-in; management dashboards remain for the next approved phase.

## Routes and request flow

| Method | Route | Access |
| --- | --- | --- |
| GET, POST | /register | Guest; Client registration only |
| GET, POST | /login | Guest |
| POST | /logout | Authenticated |
| GET | /forgot-password | Guest |
| POST | /forgot-password | Guest; neutral response and rate limit |
| GET | /reset-password/{token} | Guest |
| POST | /reset-password | Guest; token/password validation and rate limit |
| GET | /account | Active authenticated account |
| GET | /client/profile | Active Client; own profile only |
| POST | /owner/managers | Active Owner, create-managers gate and rate limit |
| PUT | /owner/managers/{manager}/permissions | Active Owner; Manager targets only |
| POST | /api/chat | Existing same-origin, CSRF-protected Fusion-AI route |

Every modifying web route uses CSRF protection. A registration form posts to `AuthController`, which validates input, creates the User and profile through Eloquent in a transaction, and starts a regenerated session. Protected requests pass through authentication, active-account checks, role middleware and permission gates.

Fusion-AI still sends JavaScript requests directly to Laravel's `ChatController` and `FusionAiService`, then Groq. The provider remains Groq, model `openai/gpt-oss-20b`. Existing prompts, fallback replies and chatbot appearance are preserved. Keys remain server-side.

## Fitness imports and financial history

The idempotent seeders import the existing JSON into seven workout categories, 57 exercises, seven workout plans, 57 plan/exercise links and eight diet plans. Repeating the seeders creates no duplicates or changes to existing imported records. Public content continues to use its preserved JSON files.

Membership records store their original plan name, duration, agreed price and currency. Later plan edits cannot change those snapshots. Foreign keys prevent hard deletion of referenced clients, plans, memberships and payments. Membership, Payment and Refund model deletion is rejected; use cancellation/void/refund status changes in future management code. Archived clients and plans remain readable through membership/payment history relationships. Future controllers must use these model rules rather than bypassing them with bulk SQL deletes.

Do not use `migrate:fresh`, database reset, rollback or the one-time provisioning helper against this installation. All existing tables and data were preserved. A real Owner account and its creation/login audit records appeared during verification and were retained; no permanent test Owner was created.
## Exact local steps

1. Open `http://127.0.0.1:8000/login` and sign in with your private Owner credentials. An Owner account already exists and was preserved. The setup command refuses to replace it. Confirm that this is the account you created; do not send its password or reset link in chat.
2. For a fresh installation without an Owner, run the following in your own PowerShell terminal. Enter your real name/email and both hidden password prompts yourself. There is no default Owner email or password and no password command-line option.

```powershell
Set-Location 'C:\Users\pro saqi\Desktop\fit-fusion\laravel'
& 'C:\xampp\php\php.exe' artisan fitfusion:create-owner
```

3. If Laravel is stopped, start the local server from the same directory:

```powershell
& 'C:\xampp\php\php.exe' artisan serve --host=127.0.0.1 --port=8000
```

4. Client registration is at `/register`. Forgot password is at `/forgot-password`. The original public URLs remain `/`, `/index.html`, `/about.html`, `/dietplans.html`, `/workouts.html`, `/BMI.html`, `/fusion-ai.html` and `/contact.html`.
5. To inspect the database, open `http://localhost/phpmyadmin/` using the existing local phpMyAdmin login, select `fitfusion_db`, and use Structure or Browse. XAMPP's MySQL service is this MariaDB server. Do not edit global XAMPP/phpMyAdmin settings. If a database tool asks for application credentials, enter `fitfusion_app` and the private password from Laravel `.env` locally.

## Local password-reset previews

Local reset delivery uses `FITFUSION_PASSWORD_RESET_DELIVERY=preview` and `MAIL_MAILER=array`. An active account's reset request writes a JSON email preview under:

```text
C:\Users\pro saqi\Desktop\fit-fusion\laravel\storage\app\private\.password-resets\
```

Both the folder and each JSON filename begin with a dot. The filename protection is necessary because the preserved original Express static server can serve ordinary files inside dot directories. Verification confirmed that a generated dot-file preview returns 404 through Express and cannot be read through Laravel's unsigned storage URL.

Open your own newest JSON file locally, then open its `url` privately in the browser. The link expires after 60 minutes and can only be used once. The database stores a hash of the token, not its plaintext value. Repeat link generation is throttled. Unknown/inactive emails receive the same neutral response but no preview. Reset URLs use trusted `APP_URL`; tokens and passwords are not written to application logs. Reset previews are credentials: do not publish, commit or share them.

Actual production SMTP delivery was not tested because SMTP credentials were unavailable. This does not block local testing. Before deployment, configure a real mailer privately: set `FITFUSION_PASSWORD_RESET_DELIVERY=smtp`, `MAIL_MAILER=smtp`, and the provider's host, port, TLS settings, username, password and sender address. Verify a real delivery after configuration. Production refuses local preview mode and log/array reset delivery.

## Verification actually performed

The final standalone Laravel backend suites passed **774 database/authentication checks** and **151 AI checks**, including a real HTTP timeout against a loopback fixture. All fixture database changes rolled back. Owner creation and password hashing were tested transactionally before a real Owner existed; the final run tested refusal to overwrite the existing Owner. Manager creation, grants, revocation, financial denial, inactive accounts, CSRF, strong passwords, hashed reset tokens, expiry and one-use resets were verified.

The final Chrome browser suite passed **140 checks**, including **88 page/width layouts**: seven public pages and four authentication forms at 320, 375, 390, 768, 992, 1024, 1440 and 1920 pixels. It exercised real page scrolling and existing AOS animations, mobile navigation/Escape, registration, keyboard login, logout, reset email preview, reset submission, rejection of the old password, login with the new password, old-session revocation and inactive-session logout. Browser fixture cleanup succeeded.

Additional chatbot checks covered eight chat layouts, Enter/Shift+Enter, loading, formatting, escaping, clearing, local fallback badges, timeout/retry, and mobile viewport restoration. The mobile keyboard was simulated in Chrome; no physical-device, Safari or Firefox test is claimed. Real Groq replies succeeded through both Laravel and the original Node chatbot. No JavaScript exceptions or CORS failures occurred. Controlled invalid-request/CSRF responses and the original Node favicon 404 are expected test results.

The existing imported rows remained unchanged. The original baseline had no users or audit entries; the new real Owner and its two creation/login audit entries were retained. No test account remains. The original 699 files and protected Laravel public assets/pages/AI implementation passed SHA-256 comparison. Credential scans found no actual database/API secrets in public assets, Blade sources, reports or application logs. Debugging is disabled; sessions are encrypted with HttpOnly/SameSite=Lax cookies.

Tests use standalone scripts because PHPUnit/Mockery are not installed. No `artisan test` or PHPUnit run is claimed, and no testing package was installed. Run from the Laravel directory when needed:

```powershell
& 'C:\xampp\php\php.exe' tests/verify-database-auth.php
& 'C:\Program Files\nodejs\node.exe' tests/verify-ai-timeout.cjs
& 'C:\Program Files\nodejs\node.exe' tests/verify-auth-browser.cjs
& 'C:\xampp\php\php.exe' tests/verify-auth-security.php
```

Browser tests require the local Laravel server on 8000, the unchanged comparison Node server on 3000, installed Chrome and internet access to existing CDN assets/Groq. Do not run database-writing verification suites simultaneously. Detailed reports and screenshots are in `storage/app/private/phase4-4-verification/`; the compact final report is `PHASE-4.4-VERIFICATION.json`.

## Deployment prerequisites and phase boundary

Configure production SMTP, the real HTTPS `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false` and secure session cookies. Preserve `APP_KEY`; changing it invalidates encrypted state. Use a maintained MariaDB/MySQL release before production: the installed MariaDB 10.4 is suitable for this verified local installation but is out of maintenance. Any server installation/upgrade needs your separate approval.

Serve Laravel's `public/` directory as the production web root. Keep the original Node server for local comparison/rollback; its broad project-root static hosting must not be used as the production Laravel web root. Protect private environment files, storage and backups. No dashboards, persistent chatbot history, payments UI or later phase was started. Phase 4.5 requires your approval.

The new completion checkpoint is `C:\Users\pro saqi\Desktop\fit-fusion-phase4-4-completion-backup-20261011-023850`. It contains a private code/environment checkpoint and a logical database backup. Do not publish it or restore it over the current database during normal verification.
Transaction-based fixtures may advance auto-increment counters even when rolled back. ID gaps are normal; the existing rows and their IDs were preserved. Final syntax checks passed for 72 PHP files and four Node test scripts. phpMyAdmin responded with HTTP 200. The final comparison covered 699 original files and 61 protected Laravel files, with zero changes.
