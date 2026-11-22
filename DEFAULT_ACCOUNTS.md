# UIEIMS — Default User Accounts

All accounts use password **`Admin@123`** (bcrypt-hashed in the database).
Sign in at `https://localhost/uie/login` with the **username** or the email.

| Username | Password | Role | Level | Organizational Scope | Lands On |
|---|---|---|---|---|---|
| `admin` | `Admin@123` | Super Administrator | System | System-wide (no restriction) | `superadmin/dashboard` |
| `region11.admin` | `Admin@123` | Regional Administrator | Region | **DepEd Region XI – Davao Region** | `dashboard` (region view) |
| `davaocity.admin` | `Admin@123` | Division Administrator | Division | **SDO – Davao City** (Region XI) | `dashboard` (division view) |
| `dcnhs.admin` | `Admin@123` | School Administrator | School | **Davao City National High School** (ID `304301`, SDO Davao City) | `dashboard` (school view) |
| `dcnhs.encoder` | `Admin@123` | School Encoder | School | **Davao City National High School** (ID `304301`, SDO Davao City) | `dashboard` (school view) |

## Role → capability matrix (Phase 1)

| Capability | Super Admin | Regional Admin | Division Admin | School Admin | School Encoder |
|---|:-:|:-:|:-:|:-:|:-:|
| Manage regions/divisions/schools/users | ✅ | — | — | — | — |
| Manage school years, modules, settings | ✅ | — | — | — | — |
| View learner registry | ✅ all | ✅ region only | ✅ division only | ✅ school only | ✅ school only |
| Create / edit learners | ✅ | — | ✅ | ✅ | ✅ |
| Archive / restore learners | ✅ | — | ✅ | — | — |
| Import / export / print | ✅ | export | ✅ | ✅ | — |
| Submit records to Division | — | — | — | ✅ | — |
| Review / validate / return submissions | — | review | ✅ | — | — |
| View / export reports | ✅ | ✅ | ✅ | ✅ | — |
| View sensitive SNED data | ✅ | ✅ | ✅ | — | — |
| View audit trail | ✅ | — | — | — | — |

*Viewer roles (`regional_viewer`, `division_viewer`, `school_viewer`) are seeded but have no accounts yet — create them under **Super Admin → Users** as needed.*

## Security notes

- **Change every default password before any real deployment.** Accounts created via *Super Admin → Users* are forced to change their password on first login (`must_change_password = 1`); the five seeded accounts have this flag off for local development convenience.
- To reset any password: **Super Admin → Users → 🔑 (reset)** — generates a temporary password and forces a change on next login.
- Repeated failed logins (default **5 attempts**) lock the account for **15 minutes**. A locked account shows a 🔓 unlock button in Users.
- Password policy: minimum 8 characters (enforced server-side). Tune `login_max_attempts` / `login_lockout_minutes` in the `system_settings` table.

## Organizational hierarchy of the seeded accounts

```
admin  ──► Super Admin (system-wide)
region11.admin  ──► DepEd Region XI – Davao Region
        └── davaocity.admin  ──► SDO – Davao City
                └── dcnhs.admin / dcnhs.encoder  ──► Davao City NHS (304301)
```

This mirrors the real four-level UIEIMS structure:
**Super Admin → Regional Office → Schools Division Office → School**.
