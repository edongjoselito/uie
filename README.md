# UIEIMS — Unified Inclusive Education Information and Management System

Centralized web-based data management for DepEd programs:
**IPEd** (Indigenous Peoples Education), **Madrasah Education**, **SNED** (Special Needs Education), **SPFL** (Special Program in Foreign Language).

Built on **CodeIgniter 3 + PHP 8.2 + MySQL 8 + Bootstrap 5 + jQuery + Chart.js**.
Operating identity: **DepEd Region XI – Davao Region**.

## Status

✅ **Phase 1 — System Foundation** (complete)
- CI3 scaffold (PHP 8.2-patched), database, authentication (bcrypt, lockout, CSRF)
- RBAC: 8 roles, 43 permissions, role→permission mapping
- Region / Division / School / User management (Superadmin)
- Centralized learner registry + enrollment records + record history
- School year management (active SY, open/close)
- Audit trail logging
- Region XI seed data (11 SDOs, 9 schools, test accounts)

## Setup

1. **Requirements**: XAMPP PHP 8.2+, MySQL 8+, Apache mod_rewrite.
   All 42 tables use the **`uie_` prefix** (`dbprefix` in `database.php`).
2. **Database**:
   ```bash
   mysql -h127.0.0.1 -uroot -p < db/schema.sql
   mysql -h127.0.0.1 -uroot -p < db/seed_region11.sql
   ```
3. **DB credentials**: `application/config/database.php` (default `root` / `moth34board`).
4. **Base URL**: `application/config/config.php` → `$config['base_url']`.
5. Browse `https://localhost/uie/` — sign in (see **`DEFAULT_ACCOUNTS.md`** for all seeded accounts; default `admin` / `Admin@123`).

## Directory map

```
application/
  core/MY_Controller.php   MY_Controller, Admin_Controller, Superadmin_Controller
  core/MY_Model.php        shared CRUD base model
  libraries/Access_control.php   auth state, role/permission gates, org scoping
  helpers/uie_helper.php e(), badges, pagination, audit_log(), settings
  controllers/
    Auth.php, Dashboard.php, Ajax.php
    Superadmin/{Dashboard,Regions,Divisions,Schools,Users,School_years,Learners}.php
  models/  Region, Division, School, User, Role, Learner, School_year, Audit
  views/   layouts/, auth/, dashboard/, superadmin/{...}
assets/    bootstrap 5.3.3, bootstrap-icons, jquery 3.7.1, chart.js 4.4.7, app.css/js
db/        schema.sql, seed_region11.sql, DATABASE.md
```

## Security notes

- Session auth, `password_hash()` bcrypt, 5-attempt → 15-min lockout (configurable in `system_settings`)
- CSRF on all forms (CI3 `csrf_protection`), XSS escaping via `e()` helper
- Server-side authorization in every protected controller (`Admin_Controller` + `Access_control`)
- Regional/Division/School data isolation enforced in models (not just hidden links)
- Full audit trail in `audit_logs`; learner changes in `learner_record_history`
- Learner records are archived, never hard-deleted

## Roadmap (per master spec)

Phase 2 Super Admin module polish → Phase 3 School module (encoding, submissions)
→ Phases 4–6 IPEd/Madrasah/SNED modules → Phase 7 validation & consolidated
reporting → Phase 8 testing & deployment.

## Secure password recovery

Run the additive migration on existing installations (also required after a fresh schema import):

```sh
mysql -h127.0.0.1 -uroot -p uie_db < db/migrations/20261122_password_resets.sql
```

Configure authenticated SMTP in Apache's environment using `UIE_SMTP_HOST`,
`UIE_SMTP_PORT` (default 587), `UIE_SMTP_USER`, `UIE_SMTP_PASS`,
`UIE_SMTP_CRYPTO` (`tls` or `ssl`), and `UIE_MAIL_FROM`.
Settings are read by `application/config/password_reset.php`; keep credentials out
of source control. Restart Apache after changing its environment. Configure
`base_url` with the trusted HTTPS address that email recipients can reach
(`localhost` only works on the same computer).

The login page links to `/forgot-password`. Responses do not disclose whether
an account exists. Limits are 3 requests per email and 10 per IP in each fixed
15-minute window; reset verification/submission is limited to 30 per IP.
Tokens contain 32 random bytes, expire after 30 minutes, are stored only as
SHA-256 hashes, and are consumed in a transaction. A new link replaces the
previous link. Tokens travel in email URL fragments and CSRF-protected POST
bodies rather than access-log URLs; JavaScript is required to open a reset link.
Passwords require at least 12 characters and at most 72 bytes. A reset clears
login lockouts and invalidates all previous authenticated sessions. Existing
sessions created before this feature must sign in again. Inactive accounts
cannot reset passwords. Email failure revokes the new token and records a
non-sensitive server log entry; no reset link is displayed or logged.

Verify SMTP delivery with a designated test mailbox before enabling recovery
for users. Do not enable request-body logging for recovery endpoints.

## School learner encoding

Run `db/migrations/20261122_learner_program_tags.sql` against `uie_db` after the
base schema. It adds enrollment-specific program tags and maps legacy learner
permission names to the controller permission names without granting new roles.
Roles, permissions, and school assignments refresh on the next request, including
for existing sessions.

School admins and school encoders with active school assignments can open
**Dashboard → Encode learner**, or **Learners → Profile** in the sidebar. Choose an
open school year, grade (Kindergarten through Grade 12), optional section, and any
combination of SNED, Madrasah, IPEd, and FL / SPFL. No program tag is also allowed.
Register a new learner or select one already recorded at that school to add a
new year's enrollment. Each year keeps its own grade and tags; encoding an older
year does not replace the latest current placement. School users can edit their
school's enrollment fields and tags, while shared identity corrections and
cross-school transfers remain administrator tasks. Closed years are view-only;
school viewers cannot encode or edit. LRN is optional but must contain exactly
12 digits when supplied. Duplicate school/year enrollments and existing LRNs are
rejected. All enrollment and tag changes are saved together with record history.

The school dashboard counts learners and program tags in the active school year.
Multi-tagged learners count once in each applicable program. The learner list
supports year, grade, program, and name/LRN filters, including earlier years.
Division accounts can browse current learner enrollments across their assigned
division from the dashboard or sidebar. Their learner directory is read-only;
create and edit routes are denied regardless of additional role grants.
Selecting a program on the enrollment form reveals its additional profile
fields; those details are saved with the enrollment and restored when editing.
The manual entry form covers the complete **SF 1 (School Register)** field
set — extension name, mother tongue, IP (ethnic group), religion, full address,
parent/guardian names, relationship, and contact number — plus the
program-specific profile fields. Apply the additive SF 1 migration:

```sh
mysql -h127.0.0.1 -uroot -p uie_db < db/migrations/20261122_sf1_learner_fields.sql
```

For existing installations, apply the additive FL / SPFL profile migration:

```sh
mysql -h127.0.0.1 -uroot -p uie_db < db/migrations/20261122_spfl_learner_profiles.sql
```

### SF 1 upload import

Accounts with `learners.import` (school admins) can open **Learners →
Import SF 1** or `/learners/import` to upload the class register (.xlsx, .xls,
or .csv, up to 8 MB / 500 rows). The parser detects the official two-row merged
header or a flat header row and maps LRN, the four name columns, sex, birth
date (mm/dd/yyyy, including Excel serials), mother tongue, IP ethnic group,
religion, the four address columns, parent/guardian names and relationship,
contact number, and remarks. A blank template matching the expected layout is
downloadable from the upload page.

Uploading only shows a **review screen** first — nothing is saved. Each row is
classified: new learner, existing school record (adds the year's enrollment),
already enrolled (refreshes placement/details), or needs fixing (bad LRN, name
or date, duplicates inside the file, or an LRN registered at another school —
those need an administrator transfer). Valid rows import with a single school
year, grade level, and optional section applied; an option tags rows with an
IP ethnic group as IPEd, auto-linking the matching IPEd ethnic group when it
exists in the reference list. Re-imports preserve program tags already set on
an enrollment, and non-empty SF 1 fields enrich the existing identity without
blanking stored values. Results are summarized and audit-logged.

CLI database integration checks (using temporary records that are cleaned up):

```sh
php tests/school_learners.php
php tests/sf1_import.php
```

## School learner reports

**Reports** is available to school, division, and regional accounts with the
`reports.view` permission. Results are restricted to the assigned school,
division, or region; accounts without an active matching assignment cannot open
the report URL. Division and regional users can additionally filter by District,
using each school’s recorded municipality/city.

Open `/reports` and select the school year, grade level, district (for division
and regional reports), and learner group.
Groups are SNED, Madrasah, IPEd, FL / SPFL, Multiple Programs, and No Program Tags.
The summary shows male, female, and total counts for each group, plus the unique
learner count. Program groups overlap; their totals must not be added together.
Detailed lists include LRN, learner name, sex, birth date, grade, section, program
tags, and enrollment status; division and regional lists also show each learner’s
school. All Groups previews up to 25 learners per group;
View Full Group provides pagination for additional records. Print This Page
prints the visible rows and clearly states this page limit. Generation time is
shown in Philippine time. Report responses use private, no-store caching.

### Excel, Word, and PDF downloads

Every report summary and learner-group section has Excel (`.xlsx`), Word
(`.docx`), and PDF downloads for accounts with `reports.export`. Downloads retain
the selected school year, grade, district, and assigned organizational scope and
include all matching rows across pages. Scope and both report-view and
report-export permissions are enforced on the server. Excel LRNs are text to
preserve leading zeros; learner text cannot be interpreted as spreadsheet
formulas. Private temporary files are deleted after document generation, and
responses are sent as no-store attachments.

The required PhpSpreadsheet, PHPWord, and Dompdf packages are pinned in
`composer.lock`. On a new checkout, run `composer install --no-dev --no-scripts`
with PHP 8.2 and its zip, XML, GD, and mbstring extensions. The PHP process must
have access to a writable system or PHP upload temporary directory. Document
libraries are loaded only for exports, and `/vendor/` is blocked by Apache.

Document-generation checks: `php tests/report_exports.php`.
