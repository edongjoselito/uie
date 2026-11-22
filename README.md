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
