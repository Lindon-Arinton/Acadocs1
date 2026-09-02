# ACADOCS — School Management Information System (CodeIgniter 4)

A CodeIgniter 4 MVC application for day-to-day school administration: document
submission/review, staff time records, enrollment & performance KPIs, a
Messenger-style internal chat, a document/certificate template library with
in-browser preview, and room/property inventory — all behind a single
role-based login.

---

## Tech Stack

| Layer              | Technology                                                        |
|---------------------|--------------------------------------------------------------------|
| Framework           | CodeIgniter 4.7                                                    |
| Backend             | PHP 8.1+                                                            |
| Database            | MySQL / MariaDB 10.4+                                              |
| Frontend            | Bootstrap 5.3.3, Bootstrap Icons 1.11.3, SweetAlert2 11 (all CDN)  |
| Charts              | Chart.js 4.4.3                                                     |
| Fonts               | Google Fonts — Inter                                               |
| Office doc rendering| PhpWord / PhpSpreadsheet (bundled), docx-preview + JSZip (CDN), LibreOffice (optional, local) |

---

## Requirements

- PHP 8.1+ with `intl`, `mbstring`, `mysqli` extensions
- Composer
- MySQL/MariaDB 10.4+
- **Optional:** LibreOffice, installed locally, for the highest-fidelity
  Excel/PowerPoint/other-Office template preview and the "Convert to PDF"
  download option. Not required — see [Template preview](#template-preview)
  below for how the app degrades without it.

---

## Setup

### 1. Install dependencies

```bash
composer install
```

### 2. Configure the environment

Copy `env` to `.env` (already done in this repo) and edit the database block:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = 127.0.0.1
database.default.database = acadocs
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
```

Create the database:

```sql
CREATE DATABASE acadocs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Load the schema and seed demo data

This repo's dev database was originally seeded directly from
`database schema/acadocs.sql` rather than built up through `php spark migrate`,
so the migration-history table doesn't reflect every table. On a **fresh**
database, migrating from scratch works normally:

```bash
php spark migrate --all
php spark db:seed DatabaseSeeder
```

If you're instead cloning this exact dev database, import the dump directly
and skip `migrate`:

```bash
mysql -u root acadocs < "database schema/acadocs.sql"
```

> `database schema/acadocs.sql` is kept in sync with every schema change made
> via the migrations in `app/Database/Migrations/` — treat it as the
> authoritative snapshot if `migrate` and the migrations directory ever
> disagree on a long-lived, hand-seeded database like this one.

### 4. (Optional) Install LibreOffice for full-fidelity template preview

```powershell
winget install --id TheDocumentFoundation.LibreOffice
```

`App\Libraries\OfficeConverter` auto-detects it at its default install path —
no configuration needed. Skip this step entirely if you don't need it; see
[Template preview](#template-preview).

### 5. Serve the app

```bash
php spark serve
```

Navigate to `http://localhost:8080/` — you'll be redirected to the login page.

---

## Login Credentials

| Role            | Email                              | Password    |
|------------------|-------------------------------------|-------------|
| Admin (Principal)| jorge.bautista002@deped.gov.ph      | admin123    |
| ADAS             | secretary@school.edu                | sec123      |
| Teacher (any)    | e.g. judith.abitong@deped.gov.ph    | teacher123  |

All teacher accounts share the same demo password (`teacher123`) — see
`app/Database/Seeds/UserSeeder.php` for the full seeded roster (~30 teachers).

> Passwords are stored as bcrypt hashes in the `users` table.

---

## Project Structure

```
app/
├── Config/
│   ├── Routes.php              — page + API route definitions
│   └── Filters.php             — registers the `authGuard` filter
├── Controllers/
│   ├── Admin/                  — principal-facing controllers (Dashboard, Documents, Users, Tasks, Properties...)
│   ├── Teacher/                — TeacherDashboard, SubmitDocuments, PerformanceMps
│   ├── Adas/                   — AdasDashboard
│   ├── Shared/                 — controllers reachable by more than one role (Chat, Templates, TimeRecords, Announcements, DocumentLinks, MyTasks, Profile...)
│   └── Api/                    — JSON REST controllers, one per resource
├── Models/                     — one Model per table
├── Views/
│   ├── layout/header.php, layout/footer.php — shared chrome: sidebar, topbar, AJAX page navigation, the upload/page motion loading overlays
│   ├── auth/login.php          — login page with a hand-authored animated SVG illustration
│   └── pages/{admin,teacher,adas,shared}/*.php — one view per page
├── Libraries/
│   ├── OfficeConverter.php     — LibreOffice-headless Office→PDF conversion (optional; degrades gracefully)
│   └── CertificateGenerator.php— fills a Word template's ${Field} placeholders per row of an uploaded Excel list
├── Helpers/acadocs_helper.php  — e(), currentUser(), hasRole()
├── Filters/AuthGuard.php       — login-required guard (session-based) + throttled presence-ping for chat
└── Database/
    ├── Migrations/             — one migration per table/column-group change
    └── Seeds/                  — DatabaseSeeder + per-table seeders (demo data)

public/
├── index.php                   — front controller
└── assets/css/app.css          — design tokens, sidebar, cards, badges, chat, motion-loading and login-illustration animations
```

---

## Database Schema

| Table                        | Description                                                     |
|-------------------------------|-------------------------------------------------------------------|
| `users`                      | Login accounts with bcrypt passwords + roles                    |
| `teachers`                   | Staff roster                                                     |
| `teacher_subjects`           | Many-to-many: teachers ↔ subjects                                |
| `announcements`               | School-wide announcements, forms, questionnaires                |
| `documents`                   | Teacher-submitted DLLs/lesson plans, with attached files         |
| `document_feedback`           | Principal feedback on submitted documents                       |
| `tasks`, `task_assignees`, `task_submissions`, `task_feedback` | Principal-assigned tasks and staff submissions against them |
| `kpi_snapshots`                | School-wide KPI metrics per school year                         |
| `deped_kpi_reports`            | Imported DepEd KPI report rows backing the dashboard trend chart |
| `enrollment_by_level`          | Student headcount per grade level                               |
| `performance_by_level`         | MPS/NDS scores per grade level                                   |
| `performance_by_subject`       | MPS per subject and instructor                                   |
| `mps_test_scores`              | Raw imported MPS test-score rows                                 |
| `parent_meetings`              | PTA conference attendance records (API-only; no page route)      |
| `document_links`               | ADAS-managed external resource links                             |
| `time_records`                 | Daily employee time-in/time-out attendance (`employee_id` = `AC-{users.ac_no}`) |
| `biometric_employees`          | Fallback registry for scanner rows with no matching `users.ac_no` |
| `holidays`                     | Non-school days excluded from time-record imports                |
| `deped_documents`              | DepEd-required forms with completion tracking (API-only; no page route) |
| `room_properties`              | Room/grade-section asset inventory (`grade`, `section`, `item_name`, `condition_status`) |
| `template_categories`, `templates` | Document/certificate template library, grouped by category  |
| `conversations`, `conversation_participants`, `conversation_typing` | Chat: direct + group conversations, membership/read-state/mute, live typing indicators |
| `messages`, `message_reactions` | Chat messages (reply-to, edit, soft-delete/"unsend") and per-message reactions |
| `notifications`                | In-app notification bell feed                                    |
| `api_tokens`                   | Tokens for the JSON API surface                                  |

---

## Role-Based Access

There is no separate Secretary role in practice — the `users.role` enum has a
legacy `secretary` value from an early schema, but only `admin`, `teacher`,
and `adas` are wired into the sidebar/permission logic. Seed new ADAS accounts
with `role = 'adas'`.

| Feature                        | Admin | Teacher | ADAS |
|----------------------------------|:-----:|:-------:|:----:|
| Admin Dashboard                  | ✓     |         |      |
| Teacher Dashboard (+ own present/absent count) |       | ✓       |      |
| ADAS Dashboard (+ own present/absent count)    |       |         | ✓    |
| Submit Documents                 | ✓     | ✓       |      |
| Manage Documents                 | ✓     |         |      |
| Tasks & Assignments (create)     | ✓     |         |      |
| My Tasks (view/submit)           |       | ✓       | ✓    |
| Performance / MPS entry          |       | ✓       |      |
| Announcements                    | ✓     | ✓       | ✓    |
| Time Records                     | ✓     |         | ✓    |
| Document Links                   | ✓     | ✓       | ✓    |
| Templates — view/preview/download| ✓     | ✓       | ✓    |
| Templates — manage (categories/upload/delete) |  |    | ✓    |
| Property Management — view       | ✓     | ✓       | ✓    |
| Property Management — add/delete | (view-only) | ✓ | (view-only) |
| Chat (direct messages, incl. starting new ones) | ✓ | ✓ | ✓    |
| Chat — create a group            | ✓     |         |      |
| User Management                  | ✓     |         |      |

Enforced per-controller via the `hasRole()` helper. All page and API routes
additionally require an authenticated session via the `authGuard` route
filter. Sidebar visibility (which links a role even sees) is separate from
and layered on top of this — see the role branches in
`app/Views/layout/header.php`.

---

## Key Features

### Chat
Polling-based (4s interval, no WebSockets) internal messenger under
`Shared\Chat`: read receipts, typing indicators, emoji reactions, edit/unsend
(with a visible "this message was unsent" placeholder), reply-to-message,
online/last-seen presence, group management (view/add/remove members, mute,
leave), and a right-side Chat Info panel. Fully responsive — on small
viewports the list/thread/info panel each take the full screen with a Back
button rather than sharing space.

### Template preview
`Shared\Templates::preview()` tries, in order:
1. **LibreOffice** headless conversion to PDF, if installed (`OfficeConverter`) — highest fidelity, works for any Office format.
2. **Client-side exact render** for `.doc`/`.docx` via the `docx-preview` library (loaded from CDN, needs `JSZip` loaded alongside it) — reproduces the real OOXML layout, fonts, and images in-browser regardless of server capability.
3. **Server-side HTML fallback** for other Office formats via PhpWord/PhpSpreadsheet (already Composer dependencies for certificate generation) — lower fidelity, but works everywhere with zero extra setup.

None of these are required for the app to function — each tier degrades to
the next rather than failing.

### Motion loading overlays
`app/Views/layout/header.php` + `footer.php` carry two full-screen animated
overlays (`public/assets/css/app.css`): one for document uploads (a cloud +
rising-arrow motif, driven by real `XMLHttpRequest` upload progress) and one
for AJAX page navigation (a continuously flipping page, delayed ~150ms so
fast loads never flicker it). Both are wired into the existing global
`ajaxFormSubmit()`/`loadPage()` functions, so no per-page changes are needed
to use them.

### Design system
Solid maroon (`--primary: #800000`) throughout — no gradients anywhere in the
system except the login illustration panel's soft radial backdrop. Light mode
background is `--bg: #ffe4e9`; dark mode is a separate token set toggled via
`[data-bs-theme="dark"]`. The sidebar is a flat, always-expanded list per role
(no collapsible/accordion sections).

---

## URL Routes

See `app/Config/Routes.php` for the authoritative list. Page routes (all
behind `authGuard`):

```
/login, /logout
/dashboard, /teacher-dashboard, /adas-dashboard
/submit-documents, /documents, /documents/(:num)/file, /documents/(:num)/download
/performance/mps
/announcements, /time-records, /deped-documents, /document-links
/templates, /templates/download/(:num), /templates/preview/(:num)
/property-management
/users
/tasks, /tasks/(:num), /my-tasks
/profile
/chat, /chat/(:num)/messages, /chat/(:num)/send, /chat/(:num)/typing,
/chat/(:num)/messages/(:num)/react|edit|delete,
/chat/(:num)/members, /chat/(:num)/members/add|remove,
/chat/(:num)/leave, /chat/(:num)/mute, /chat/attachment/(:num)
```

---

## API Endpoints

All endpoints require an active session (log in via `/login` or
`POST /api/auth/login` first). Responses are JSON.

```
POST   /api/auth/login              { email, password }
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/announcements
POST   /api/announcements           { type, title, content, date }
PUT    /api/announcements?id=X
DELETE /api/announcements?id=X

GET    /api/teachers
POST   /api/teachers                { employee_id, name, email, grade_level, subjects[] }
PUT    /api/teachers?id=X
DELETE /api/teachers?id=X

GET    /api/documents[?teacher_id=X]
GET    /api/documents?id=X          (includes feedback)
POST   /api/documents               { teacher_id, type, subject, grade_level }
POST   /api/documents/feedback?id=X { author, comment }
PUT    /api/documents?id=X          { type, subject, grade_level, status }
DELETE /api/documents?id=X

GET    /api/performance?school_year=2025-2026

GET    /api/time-records[?date=YYYY-MM-DD]
POST   /api/time-records            { date, employee_name, employee_id, time_in, time_out, status }
PUT    /api/time-records?id=X
DELETE /api/time-records?id=X

GET    /api/properties[?grade=X&section=Y]
POST   /api/properties              { section, grade, item_name, condition_status }
PUT    /api/properties?id=X         { condition_status }
DELETE /api/properties?id=X

GET    /api/document-links[?category=X]
POST   /api/document-links          { category, title, description, url, access_level }
DELETE /api/document-links?id=X

GET    /api/parent-meetings
POST   /api/parent-meetings         { title, date, expected_parents, actual_attendance }
PUT    /api/parent-meetings?id=X
DELETE /api/parent-meetings?id=X
```

---

## Notes

- **CSRF protection is left disabled**, matching the app's forms (they carry
  no CSRF token field). Enabling `Config\Filters::$globals['before'] =
  ['csrf']` is a reasonable follow-up but out of scope so far.
- **File uploads are real**: submitted documents, chat attachments, and
  templates are stored under `writable/uploads/` with metadata rows pointing
  at them — this is not a metadata-only stub.
- **`php spark migrate` on this specific dev database**: because it was
  seeded directly from the SQL dump rather than built up through migrations,
  the migration-history table doesn't have rows for most existing tables.
  Schema changes made during development were applied as raw SQL against the
  live dev database and mirrored into both a proper migration file and
  `database schema/acadocs.sql`. A **fresh** database migrates cleanly from
  scratch (`php spark migrate --all`) — this only matters if you're working
  directly against the pre-existing dev database rather than starting clean.
