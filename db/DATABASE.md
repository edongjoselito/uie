# UIEIMS Database Documentation

Database: `uie_db` (MySQL 8.x, InnoDB, utf8mb4)
Host: `127.0.0.1` — credentials in `application/config/database.php`

**Table prefix:** every physical table carries the `uie_` prefix
(`uie_regions`, `uie_learners`, `uie_iped_learner_profiles`, …).
Models refer to tables by their logical names — CodeIgniter applies the
prefix automatically via `$db['default']['dbprefix'] = 'uie_'`.
Inline/raw SQL uses the prefixed names directly.

## Entity-Relationship Overview

```
regions ──< divisions ──< schools ──< learner_school_records >── learners
   │            │            │                  │
   │            │            │                  ├──< iped_learner_profiles
   │            │            │                  ├──< madrasah_learner_profiles ──< madrasah_learner_programs >── madrasah_programs
   │            │            │                  └──< sned_learner_profiles ──< sned_learner_disabilities >── sned_disability_categories
   │            │            │                                              ├──< sned_assessments
   │            │            │                                              ├──< sned_interventions
   │            │            │                                              ├──< sned_iep_records
   │            │            │                                              └──< sned_support_services
   └──< user_assignments >── users ──< user_roles >── roles ──< role_permissions >── permissions
```

## Centralized Learner Registry (core design)

- **`learners`** — one row per learner, common identity only (LRN, name, sex, birth,
  address, guardian info). `lrn` is UNIQUE (NULLs allowed for learners without LRN).
- **`learner_school_records`** — enrollment rows linking a learner to
  `school + division + region + school_year` (UNIQUE on learner+school+year).
  One row per school year enables history, transfers, and trend reports.
- **Program profiles** (`iped_/madrasah_/sned_learner_profiles`) attach to
  `learner_school_records` — a learner participates in multiple programs with
  **zero identity duplication**.

## Organizational scoping

`user_assignments` ties a user to `region_id` / `division_id` / `school_id`.
The `Access_control` library derives the scope and models apply it in queries —
Regional users see only their region's data, Division users only their division,
School users only their school. Superadmin has NULL scope (unrestricted).

## Tables (42)

### Organization
| Table | Purpose | Key columns |
|---|---|---|
| regions | DepEd regions | code (UQ), name, status |
| divisions | Schools Division Offices | code (UQ), region_id FK |
| schools | Schools w/ DepEd ID | school_id (UQ), division_id, region_id |
| school_years | SY reference + active flag | year_label (UQ), is_active, status |

### Users & RBAC
| Table | Purpose |
|---|---|
| users | Accounts (bcrypt hash, lockout fields, must_change_password) |
| roles | 8 seeded roles: superadmin, regional_admin, division_admin, school_admin, school_encoder + 3 viewer roles |
| permissions | module.action slugs (43) |
| role_permissions | role→permission mapping |
| user_roles | user→role mapping |
| user_assignments | org scope per user |
| school_users | school staff roster |

### Learner core
| Table | Purpose |
|---|---|
| learners | Central identity (soft delete via status=archived) |
| learner_school_records | Enrollment history per SY |
| learner_record_history | Field-level change audit (JSON old/new) |

### Program tables (Phase 4–6 usage)
- `iped_learner_profiles`, `iped_ethnic_groups`, `iped_cultural_communities`, `iped_program_references`, `iped_learner_history`
- `madrasah_learner_profiles`, `madrasah_programs`, `madrasah_learner_programs`, `madrasah_program_references`, `madrasah_learner_history`
- `sned_learner_profiles`, `sned_disability_categories`, `sned_learner_disabilities`, `sned_assessments`, `sned_interventions`, `sned_iep_records`, `sned_support_services`, `sned_learner_history`

### Submission workflow (Phase 3/7 usage)
| Table | Purpose |
|---|---|
| data_submissions | School→Division submission headers w/ status enum |
| data_submission_details | Per-learner/per-program lines |
| data_validation_logs | Division validation actions |
| school_submissions / school_submission_history | School-level tracking |
| school_data_completeness | Cached completeness metrics |
| school_import_logs | Excel import audit |

### System
| Table | Purpose |
|---|---|
| audit_logs | Every login/logout + CUD action w/ IP & user agent |
| notifications | User notifications |
| system_modules | iped/madrasah/sned enable flags |
| system_settings | Key-value config (branding, lockout policy, pagination) |

## Seed data

- 18 Philippine regions incl. **R-XI (Davao Region)** as operating region
- 11 real SDOs under Region XI (`seed_region11.sql`)
- 9 sample schools (Davao City, Digos, Tagum) — *verify IDs vs EBEIS in production*
- 7 school years (2020–2027), active: **2025-2026**
- Roles/permissions/role_permissions fully mapped
- SNED disability categories (10), IPEd ethnic groups & ICCs, Madrasah programs

## Accounts (all password `Admin@123`, bcrypt)

| Username | Level | Scope |
|---|---|---|
| admin | Super Admin | system-wide |
| region11.admin | Regional Admin | Region XI |
| davaocity.admin | Division Admin | SDO Davao City |
| dcnhs.admin | School Admin | Davao City NHS (304301) |
| dcnhs.encoder | School Encoder | Davao City NHS (304301) |

## Migration files

| File | Contents |
|---|---|
| `db/schema.sql` | Full schema + base seeds |
| `db/seed_region11.sql` | Region XI divisions, schools, settings, test accounts |
