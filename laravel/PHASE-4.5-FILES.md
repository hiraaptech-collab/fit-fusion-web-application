# Phase 4.5 source-file inventory

Paths are relative to laravel/. Compared with the backup created before Phase 4.5. Generated storage/vendor/cache files are excluded. No environment values or credentials are listed.

## Created files

- `app/Http/Controllers/Gym/AccountController.php`
- `app/Http/Controllers/Gym/AiHistoryController.php`
- `app/Http/Controllers/Gym/ClientController.php`
- `app/Http/Controllers/Gym/CommunicationController.php`
- `app/Http/Controllers/Gym/DashboardController.php`
- `app/Http/Controllers/Gym/FinanceController.php`
- `app/Http/Controllers/Gym/FitnessPlanController.php`
- `app/Http/Controllers/Gym/MembershipController.php`
- `app/Http/Controllers/Gym/SettingsController.php`
- `app/Http/Controllers/Gym/StaffController.php`
- `app/Http/Controllers/Gym/StaffOperationsController.php`
- `app/Http/Controllers/PublicDataController.php`
- `app/Http/Controllers/PublicSubmissionController.php`
- `app/Http/Requests/AccountRequest.php`
- `app/Http/Requests/AttendanceRequest.php`
- `app/Http/Requests/ContactRequest.php`
- `app/Http/Requests/ExpenseRequest.php`
- `app/Http/Requests/FitnessPlanRequest.php`
- `app/Http/Requests/MembershipPlanRequest.php`
- `app/Http/Requests/MembershipRequest.php`
- `app/Http/Requests/PaymentRequest.php`
- `app/Http/Requests/ProfileRequest.php`
- `app/Http/Requests/ProgressRequest.php`
- `app/Http/Requests/ScheduleRequest.php`
- `app/Http/Requests/StaffRequest.php`
- `app/Policies/ClientRecordPolicy.php`
- `app/Support/GymActivity.php`
- `app/Support/GymClock.php`
- `database/migrations/2026_10_11_000001_add_inquiry_responses.php`
- `database/migrations/2026_10_11_000002_add_assignment_cancellation.php`
- `PHASE-4.5-FILES.md`
- `PHASE-4.5-REPORT.md`
- `public/assets/dashboard.css`
- `public/javascript/dashboard.js`
- `public/javascript/public-submissions.js`
- `resources/views/gym/account-form.blade.php`
- `resources/views/gym/accounts.blade.php`
- `resources/views/gym/ai.blade.php`
- `resources/views/gym/audit.blade.php`
- `resources/views/gym/client-dashboard.blade.php`
- `resources/views/gym/client-memberships.blade.php`
- `resources/views/gym/client-payments.blade.php`
- `resources/views/gym/client-plans.blade.php`
- `resources/views/gym/dashboard.blade.php`
- `resources/views/gym/finance.blade.php`
- `resources/views/gym/fitness-plan-form.blade.php`
- `resources/views/gym/fitness-plans.blade.php`
- `resources/views/gym/membership-plans.blade.php`
- `resources/views/gym/memberships.blade.php`
- `resources/views/gym/messages.blade.php`
- `resources/views/gym/notifications.blade.php`
- `resources/views/gym/partials/account-fields.blade.php`
- `resources/views/gym/partials/ai-panel.blade.php`
- `resources/views/gym/partials/expense-fields.blade.php`
- `resources/views/gym/partials/field.blade.php`
- `resources/views/gym/partials/membership-fields.blade.php`
- `resources/views/gym/partials/membership-plan-fields.blade.php`
- `resources/views/gym/partials/payment-fields.blade.php`
- `resources/views/gym/partials/schedule-fields.blade.php`
- `resources/views/gym/partials/staff-fields.blade.php`
- `resources/views/gym/partials/staff-select.blade.php`
- `resources/views/gym/profile.blade.php`
- `resources/views/gym/progress.blade.php`
- `resources/views/gym/reports.blade.php`
- `resources/views/gym/settings.blade.php`
- `resources/views/gym/staff.blade.php`
- `resources/views/gym/staff-form.blade.php`
- `resources/views/gym/staff-operations.blade.php`
- `resources/views/layouts/dashboard.blade.php`
- `resources/views/pages/memberships.blade.php`
- `routes/gym.php`
- `tests/gym-browser-fixture.php`
- `tests/verify-gym-browser.cjs`
- `tests/verify-gym-live-ai.php`
- `tests/verify-gym-management.php`

## Modified files

- `.env`
- `app/Http/Controllers/AuthController.php`
- `app/Models/ClientPlanAssignment.php`
- `app/Models/Inquiry.php`
- `app/Models/Membership.php`
- `app/Models/StaffAttendance.php`
- `app/Models/StaffSchedule.php`
- `app/Providers/AppServiceProvider.php`
- `config/fitfusion.php`
- `public/javascript/bmi.js`
- `public/javascript/contact.js`
- `public/javascript/diet.js`
- `public/javascript/fusion-ai.js`
- `public/javascript/script.js`
- `public/javascript/workouts.js`
- `resources/views/layouts/public.blade.php`
- `resources/views/pages/bmi.blade.php`
- `resources/views/pages/fusion-ai.blade.php`
- `routes/web.php`
- `tests/verify-auth-browser.cjs`
- `tests/verify-auth-security.php`
- `tests/verify-database-auth.php`

## Models

No additional model layer was introduced. Existing models were reused. The five modified models are ClientPlanAssignment, Inquiry, Membership, StaffAttendance and StaffSchedule; the complete paths are listed above.

## Preservation

Original FitFusion/Node files were not changed. No baseline source files were deleted. The two additive migrations extend existing tables; all 34 tables remain. Composer/npm dependencies were not added or upgraded. Temporary editing helpers were removed before this inventory.
