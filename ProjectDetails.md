# ACADOCS — Project Details

## 1. Purpose

Acadocs is a **School Management Information System** built for **Matabungkay
National High School** to replace paper/manual processes for day-to-day
school administration. It centralizes document submission and review, staff
time & attendance tracking, enrollment and academic-performance KPIs, an
internal Messenger-style chat, a document/certificate template library, and
room/property inventory — all behind a single role-based login for the
school's administrators, teachers, and ADAS (Administrative Assistant) staff.

## 2. Tech Stack

| Layer               | Technology                                                          |
|----------------------|----------------------------------------------------------------------|
| Framework            | CodeIgniter 4.7 (PHP MVC)                                            |
| Backend language     | PHP 8.1+                                                              |
| Database             | MySQL / MariaDB 10.4+                                                |
| Frontend             | Server-rendered PHP views + Bootstrap 5.3.3, Bootstrap Icons, SweetAlert2 |
| Charts               | Chart.js 4.4.3                                                       |
| Fonts                | Google Fonts (Inter)                                                 |
| Office doc rendering | PhpWord / PhpSpreadsheet (bundled), docx-preview + JSZip (CDN), LibreOffice (optional, local) |

There is no separate frontend SPA/build — pages are rendered server-side by
CodeIgniter views, progressively enhanced with AJAX (fetch/`XMLHttpRequest`)
for navigation, forms, chat, and file uploads.

## 3. Overall Architecture

Classic CodeIgniter 4 MVC, organized by **role** at the controller/view layer
and by **resource** at the API layer:

```
app/
├── Config/
│   ├── Routes.php          — all page + API route definitions
│   └── Filters.php         — registers the `authGuard` filter (session login gate)
├── Controllers/
│   ├── Admin/               — principal-facing: Dashboard, Documents, Users, Tasks, Properties, EnrollmentKpis
│   ├── Teacher/              — TeacherDashboard, SubmitDocuments, PerformanceMps
│   ├── Adas/                 — AdasDashboard
│   ├── Shared/                — reachable by more than one role: Chat, Templates, TimeRecords,
│   │                            Announcements, DocumentLinks, MyTasks, Profile, Notifications,
│   │                            DocumentFile(Download), TaskDownload, Auth
│   └── Api/                   — JSON REST controllers, one per resource (Auth, Announcements,
│                                 Teachers, Documents, Performance, TimeRecords, Properties,
│                                 DocumentLinks, ParentMeetings)
├── Models/                    — one Eloquent-style Model per database table
├── Views/
│   ├── layout/                — shared chrome: sidebar, topbar, AJAX page navigation,
│   │                            upload/page-transition loading overlays
│   ├── auth/login.php         — login page with a hand-authored animated SVG illustration
│   └── pages/{admin,teacher,adas,shared}/*.php — one view template per page
├── Libraries/
│   ├── OfficeConverter.php     — LibreOffice-headless Office→PDF conversion (optional, graceful degrade)
│   ├── CertificateGenerator.php— fills a Word template's ${Field} placeholders from an uploaded Excel list
│   ├── MpsCalculator.php        — MPS/NDS score computations
│   ├── MpsScoreImporter.php     — imports raw MPS test-score spreadsheets
│   └── EnrollmentKpiDocxImporter.php — imports DepEd enrollment KPI Word forms
├── Helpers/acadocs_helper.php  — e(), currentUser(), hasRole()
├── Filters/AuthGuard.php        — login-required guard (session-based) + throttled chat presence-ping
└── Database/
    ├── Migrations/              — one migration per table/column-group change
    └── Seeds/                    — DatabaseSeeder + per-table seeders (demo data)

public/
├── index.php                    — front controller
└── assets/css/app.css            — design tokens, sidebar, cards, badges, chat, motion-loading,
                                     login-illustration animations
```

Authentication is session-based (no JWT); every page and API route sits
behind the `authGuard` filter. Role checks are enforced per-controller via
the `hasRole()` helper, and sidebar link visibility is layered on top of that
in `app/Views/layout/header.php`.

## 4. User Roles & Permissions

Three active roles: **Admin** (principal), **Teacher**, **ADAS**. (`users.role`
also has a legacy `secretary` enum value from an earlier schema iteration,
but it isn't wired into any sidebar/permission logic today.)

| Feature                                          | Admin       | Teacher | ADAS        |
|---------------------------------------------------|:-----------:|:-------:|:-----------:|
| Admin Dashboard                                    | ✓           |         |             |
| Teacher Dashboard (+ own present/absent count)      |             | ✓       |             |
| ADAS Dashboard (+ own present/absent count)         |             |         | ✓           |
| Submit Documents                                    | ✓           | ✓       |             |
| Manage Documents (review/feedback)                  | ✓           |         |             |
| Tasks & Assignments (create)                        | ✓           |         |             |
| My Tasks (view/submit)                              |             | ✓       | ✓           |
| Performance / MPS entry                             |             | ✓       |             |
| Announcements                                        | ✓           | ✓       | ✓           |
| Time Records                                         | ✓           |         | ✓           |
| Document Links                                       | ✓           | ✓       | ✓           |
| Templates — view/preview/download                    | ✓           | ✓       | ✓           |
| Templates — manage (categories/upload/delete)         |             |         | ✓           |
| Property Management — view                            | ✓           | ✓       | ✓           |
| Property Management — add/delete                       | view-only   | ✓       | view-only   |
| Chat — direct messages, start new ones                | ✓           | ✓       | ✓           |
| Chat — create a group                                  | ✓           |         |             |
| User Management                                        | ✓           |         |             |

## 5. Functional Modules

### Dashboards
Role-specific landing pages (`Admin\Dashboard`, `Teacher\TeacherDashboard`,
`Adas\AdasDashboard`) surfacing KPI summaries, enrollment/performance trend
charts (Chart.js, backed by `kpi_snapshots` / `deped_kpi_reports` /
`enrollment_by_level` / `performance_by_level`), and — for teacher/ADAS — the
signed-in user's own present/absent attendance count.

### Document Submission & Review
Teachers and admins submit DLLs/lesson plans and other required documents
(`Teacher\SubmitDocuments`, `Admin\Documents`) with attached files
(`documents` table). Admins review submissions and leave feedback
(`document_feedback`). File access goes through `Shared\DocumentFile` /
`DocumentFileDownload` rather than direct static links.

### Tasks & Assignments
Admins create tasks and assign them to staff (`Admin\Tasks`,
`tasks`/`task_assignees`); teachers and ADAS view and submit against their
assigned tasks (`Shared\MyTasks`, `task_submissions`, `task_feedback`), with
file downloads via `Shared\TaskDownload`.

### Performance / MPS & Enrollment KPIs
Teachers enter MPS (Mean Percentage Score) test data
(`Teacher\PerformanceMps`, backed by `MpsCalculator` / `MpsScoreImporter`,
tables `mps_test_scores`, `performance_by_level`, `performance_by_subject`).
Admins import DepEd enrollment KPI Word-form reports
(`Admin\EnrollmentKpis`, via `EnrollmentKpiDocxImporter`) which populate the
dashboard trend charts (`deped_kpi_reports`, `enrollment_by_level`).

### Announcements
School-wide announcements, forms, and questionnaires
(`Shared\Announcements`, `announcements` table), visible to all three roles,
with a per-user "last viewed" watermark (`users.last_viewed_announcements_at`)
for unread-state tracking.

### Time Records
Daily employee time-in/time-out attendance (`Shared\TimeRecords`,
`time_records`, keyed off `AC-{users.ac_no}`), including a
`biometric_employees` fallback registry for scanner rows with no matching
user account, and `holidays` to exclude non-school days from imports.

### Document Links
ADAS-managed curated list of external resource links, grouped by category
and access level (`Shared\DocumentLinks`, `document_links`).

### Template Library
A categorized library of document/certificate templates
(`Shared\Templates`, `template_categories` + `templates`) with in-browser
preview (see §6) and `CertificateGenerator` for batch-filling a Word
template's `${Field}` placeholders from rows of an uploaded Excel roster.
ADAS manages categories/uploads/deletes; everyone can view, preview, and
download.

### Room/Property Management
Per-room/grade-section asset inventory (`Admin\Properties`,
`Api\PropertiesController`, `room_properties`: `grade`, `section`,
`item_name`, `condition_status`). Everyone can view; only teachers can
add/delete entries (admin and ADAS are view-only for this module,
despite otherwise being higher-privilege roles).

### Internal Chat
Polling-based (4-second interval, no WebSockets) Messenger-style chat
(`Shared\Chat`, tables `conversations`, `conversation_participants`,
`conversation_typing`, `messages`, `message_reactions`): direct + group
conversations, read receipts, typing indicators, emoji reactions,
edit/unsend (with a visible "this message was unsent" placeholder),
reply-to-message, online/last-seen presence (via the throttled presence-ping
in `AuthGuard`), group management (add/remove members, mute, leave), and a
right-side Chat Info panel. Fully responsive: on small viewports, the
list/thread/info panel each take the full screen with a Back button instead
of sharing space.

### Notifications
An in-app notification bell feed (`Shared\Notifications`, `notifications`
table) with per-notification mark-as-read.

### User Management
Admin-only CRUD over staff accounts (`Admin\Users`, `users` table,
bcrypt-hashed passwords).

### Profile
Self-service profile page for the signed-in user (`Shared\Profile`) —
photo, position, account details.

## 6. Notable Subsystems

### Template preview pipeline
`Shared\Templates::preview()` degrades through three tiers so preview always
works regardless of server capability:
1. **LibreOffice** headless conversion to PDF (`OfficeConverter`), if
   installed — highest fidelity, any Office format.
2. **Client-side exact render** for `.doc`/`.docx` via the `docx-preview`
   CDN library (+ JSZip) — reproduces real OOXML layout/fonts/images
   in-browser.
3. **Server-side HTML fallback** via PhpWord/PhpSpreadsheet — lower
   fidelity, zero extra setup.

### Motion loading overlays
Two full-screen animated overlays defined in `app.css` and wired into the
global `ajaxFormSubmit()` / `loadPage()` JS functions (no per-page changes
needed): a cloud/rising-arrow motif driven by real upload progress
(`XMLHttpRequest`), and a flipping-page motif for AJAX page navigation
(delayed ~150ms so fast loads never flicker it).

### Design system
Solid maroon (`--primary: #800000`) throughout, no gradients except the
login illustration panel's soft radial backdrop. Light background
`--bg: #ffe4e9`; a separate dark-mode token set toggles via
`[data-bs-theme="dark"]`. Sidebar is a flat, always-expanded per-role list
(no collapsible sections).

## 7. Database Schema (Summary)

| Table | Purpose |
|---|---|
| `users` | Login accounts, bcrypt passwords, roles |
| `teachers`, `teacher_subjects` | Staff roster and teacher↔subject assignments |
| `announcements` | School-wide announcements/forms/questionnaires |
| `documents`, `document_feedback` | Submitted documents and principal feedback |
| `tasks`, `task_assignees`, `task_submissions`, `task_feedback` | Assigned tasks and staff submissions |
| `kpi_snapshots`, `deped_kpi_reports` | School-wide KPI metrics and imported DepEd KPI rows |
| `enrollment_by_level` | Student headcount per grade level |
| `performance_by_level`, `performance_by_subject`, `mps_test_scores` | MPS/NDS academic performance data |
| `parent_meetings` | PTA conference attendance (API-only, no page route) |
| `document_links` | ADAS-managed external resource links |
| `time_records`, `biometric_employees`, `holidays` | Attendance tracking and its fallback/exclusion data |
| `deped_documents` | DepEd-required forms with completion tracking (API-only, no page route) |
| `room_properties` | Room/grade-section asset inventory |
| `template_categories`, `templates` | Document/certificate template library |
| `conversations`, `conversation_participants`, `conversation_typing` | Chat structure, membership, typing state |
| `messages`, `message_reactions` | Chat messages and reactions |
| `notifications` | In-app notification feed |
| `api_tokens` | JSON API auth tokens |

The authoritative schema snapshot lives at `php/database schema/acadocs.sql`;
individual changes are also tracked as migrations under
`php/app/Database/Migrations/`.

## 8. Routes & API

Full authoritative lists live in `php/app/Config/Routes.php`; summarized in
`php/README.md` under **URL Routes** and **API Endpoints**. All page routes
sit behind session auth (`authGuard`); the JSON API mirrors most page
resources (`/api/announcements`, `/api/teachers`, `/api/documents`,
`/api/performance`, `/api/time-records`, `/api/properties`,
`/api/document-links`, `/api/parent-meetings`, plus `/api/auth/*`).

## 9. Known Limitations / Notes

- **CSRF protection is disabled** (forms carry no CSRF token field);
  enabling `Config\Filters::$globals['before'] = ['csrf']` is a reasonable
  follow-up but currently out of scope.
- **File uploads are real**, not stubs — submitted documents, chat
  attachments, and templates are stored under `php/writable/uploads/` with
  metadata rows pointing at them.
- **Migration history is incomplete on the long-lived dev database**: it was
  originally seeded directly from `database schema/acadocs.sql` rather than
  built up through `php spark migrate`, so schema changes made afterward
  need to be applied manually (raw SQL) against that specific database even
  though a proper migration file also exists. A **fresh** database migrates
  cleanly from scratch (`php spark migrate --all`).

---

## Laboratory Exercise 1 — Dashboard Analysis and Improvement Proposal

### Part 1 | Analyze Your Existing Dashboard

#### A. System Information

- **Capstone/System Title:** ACADOCS — School Management Information System
- **Organization/Client:** Matabungkay National High School
- **Primary Purpose:** To centralize day-to-day school administration —
  document submission and review, staff time/attendance records, enrollment
  and academic-performance KPI tracking, internal staff communication (chat),
  a document/certificate template library, and room/property inventory — all
  behind a single role-based login for the principal (Admin), teachers, and
  ADAS staff.
- **Primary Data Collected:** staff/user accounts and roles; submitted
  documents (DLLs/lesson plans) and review feedback; task assignments and
  submissions; daily time-in/time-out attendance records; student enrollment
  counts per grade level; MPS/NDS academic performance scores per subject and
  grade level; imported DepEd KPI report figures (enrollment, dropout,
  retention, promotion, etc.); announcements; chat messages; room/property
  inventory and condition status; document templates.

#### B. Actual Dashboard Evidence

*AdminDashboard.png here*

**1. Who is the primary user of this dashboard?**
The school principal / Admin role — this is the `Admin\Dashboard` controller
served at `/dashboard`. Teachers and ADAS staff each get their own simplified
dashboard variant (Teacher Dashboard, ADAS Dashboard) that additionally shows
their own present/absent attendance count, but the full KPI/analytics view
described below is Admin-only.

**2. What is the main purpose of the dashboard?**
To give the principal a single-screen, year-filterable snapshot of the
school's current standing — enrollment, academic performance (MPS), dropout
rate, and document-submission compliance — plus a short list of
system-generated, plain-English "insights" (e.g., a flagged low-performing
subject, a compliance rate below target), so these don't have to be
manually derived from separate reports/tables each time.

**3. What questions can a user currently answer by looking at it?**
- What is total enrollment for the selected school year, and how does it
  compare to the previous year (shown as a % delta with a sparkline)?
- What is the school-wide average MPS for the selected year/term, and is it
  trending up or down vs. the previous year?
- What is the current dropout rate and how has it changed?
- Which subject has the lowest average MPS this term, and who teaches it
  (auto-flagged when it falls below 80%)?
- Which subject is the top performer this term (flagged when ≥ 85%)?
- What is the document-submission compliance rate, and is it above or below
  the 85% target?
- How many submitted documents are currently pending review?
- What were the 5 most recently submitted documents?
- What is enrollment broken down by grade level, for the selected year?
- What is MPS performance broken down by grade level and by subject, for the
  selected year/term?
- What were the historical DepEd KPI figures (enrollment, dropout, etc.) in
  prior school years?

**4. What decisions or actions can currently be supported?**
- Flagging a specific underperforming subject/instructor for follow-up or
  academic intervention.
- Prioritizing pending-document review workload (visible queue count).
- Following up on submission compliance when it drops below the 85% target.
- Noticing year-over-year enrollment growth or decline to inform planning
  (e.g., sectioning, staffing needs).
- Tracking dropout-rate movement as an early-warning signal within the year.
- Comparing this year's headline numbers against last year's without pulling
  separate reports.

**5. What important questions cannot yet be answered by the dashboard?**
- *Why* a subject or grade level is underperforming — there's no
  correlation shown between performance and attendance, teacher workload, or
  student-level detail; everything stops at grade-level/subject aggregates.
- No drill-down from a summary number to the underlying records (e.g.,
  clicking the "10 pending documents" insight doesn't take you to that
  filtered list).
- No multi-year trend chart — only a two-point delta (this year vs. last)
  plus a separate historical DepEd KPI table; there's no single visual
  showing the full trend line across all available years together.
- No staff-attendance analytics on the dashboard itself — `time_records`
  data exists and powers the per-user present/absent count on the
  Teacher/ADAS dashboards, but the Admin dashboard doesn't show school-wide
  attendance trends, chronic tardiness, or an attendance-vs-performance
  correlation.
- No task/assignment analytics — the Tasks module (`tasks`,
  `task_submissions`) has no representation on the dashboard (e.g., overdue
  tasks, completion rate per staff member).
- No announcement reach/engagement metric — `users.last_viewed_announcements_at`
  exists per user, but the dashboard doesn't surface how many staff have
  seen a given announcement.
- No property/inventory condition summary — `room_properties.condition_status`
  (Excellent/Good/Fair/Poor) is tracked but never appears on the dashboard,
  despite being organizationally relevant (e.g., how many items are rated
  "Poor" and need replacement).
- No forecasting or anomaly detection — the "insights" panel is rule-based
  on fixed thresholds (e.g., MPS < 80%, compliance < 85%) and a same-vs-
  previous-year delta, not a projection of where a metric is headed.
- No segmentation/filtering beyond the single school-year dropdown — cannot
  filter the view by a single grade level, a single teacher, or a custom
  date range.
- No export, print, or scheduled-report option for sharing this snapshot
  with DepEd or the school board outside the live app.
