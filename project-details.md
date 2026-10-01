# ACADOCS — Project Details

**ACADOCS** is a web-based School Management Information System built for
**Matabungkay National High School** (Lian, Batangas — Schools Division of
Batangas Province, DepEd Region IV-A CALABARZON). It brings the school's
day-to-day administrative work — document submission and review, tasks,
announcements, staff attendance, enrollment, KPI and MPS reporting,
templates and certificates, property inventory, and internal communication —
into one system behind a single role-based login.

The system has three user roles:

| Role | Who uses it |
|------|-------------|
| **Admin** | The School Head / Principal |
| **Teacher** | Teaching staff |
| **ADAS** | Administrative Assistant / support staff |

---

## 1. Objectives of the System

### General Objective

To develop a centralized, role-based School Management Information System
that digitizes and automates the administrative, academic-reporting and
communication processes of Matabungkay National High School, reducing manual
paperwork and giving the school head accurate, up-to-date data for
decision-making.

### Specific Objectives

1. **Centralize document submission and review** — let teachers and staff
   upload required documents against assigned tasks, and let the principal
   review, return, or approve them with feedback in one place.
2. **Streamline task assignment and monitoring** — let the principal and ADAS
   post tasks with deadlines to a whole role or to specific people, and track
   who has and hasn't submitted.
3. **Automate data feeding into the dashboard** — let users import the
   school's existing Excel/Word reports (enrollment sheets, MPS score sheets,
   DepEd KPI reports, biometric attendance logs) so the system reads the data
   and automatically produces the dashboard charts, KPI tiles and summaries
   without manual re-encoding.
4. **Provide decision support through automatic insights** — turn imported
   data into plain-language observations (e.g. lowest-performing subject,
   rising drop-out rate, low submission compliance) so the principal knows
   where to act.
5. **Monitor academic performance** — compute Mean Percentage Scores (MPS)
   per subject, grade level and term from teachers' entries or uploaded
   score sheets.
6. **Track enrollment and DepEd Key Performance Indicators** — store
   enrollment per grade level and month, plus the DepEd KPI rates
   (enrolment, cohort survival, promotion, retention, completion, drop-out,
   etc.) per school year, and show their trends over time.
7. **Automate staff attendance records** — convert biometric-scanner exports
   into daily Present / Late / Absent records automatically.
8. **Speed up document and certificate preparation** — keep a shared
   template library and generate certificates in bulk from a Word template
   and an Excel list of recipients.
9. **Improve school communication** — provide announcements, in-app
   notifications and a built-in chat (direct and group messages).
10. **Keep an inventory of school property** — record room and section
    assets and their condition.
11. **Secure access by role** — make sure each user only sees and does what
    their role allows.

---

## 2. What the System Centralizes

Before ACADOCS, this information lived in separate Excel files, printed
forms, group chats, and individual computers. The system now keeps all of it
in one database:

| Area | What is centralized |
|------|---------------------|
| **Documents & submissions** | Every submitted file, organized automatically into one folder per task, with its review status and feedback history |
| **Tasks & deadlines** | All assigned tasks, who they're for, deadlines, and submission counts |
| **Announcements** | School-wide announcements, forms, and questionnaires |
| **Academic performance** | MPS scores per test period, subject, section, grade level, term and school year |
| **Enrollment** | Monthly enrollment per grade level (male / female / total / sections) |
| **DepEd KPIs** | Gross/Net Enrolment, Cohort Survival, Repetition, Promotion, Retention, Graduation, Completion, Transition and Drop-Out rates per school year |
| **Staff attendance** | Daily time-in / time-out and status for every employee, plus the school's holiday list |
| **Templates** | The school's document and certificate templates, grouped by category |
| **Resource links** | External links to DepEd forms, guidelines, and questionnaires |
| **Property inventory** | Items per grade and section with their condition |
| **Communication** | Chat conversations, attachments, and notifications |
| **User accounts** | All staff accounts, roles, and teacher subject loads |

---

## 3. The Intelligent Feature: Data Feeding Mechanism

As the panel requested, an **intelligent feature** was added: a **data
feeding mechanism**. When a user imports a file — mainly an Excel file — the
system **reads the data, saves it, and automatically creates the dashboard
charts and output**. Nobody has to type the numbers into the system or build
the charts by hand.

### How it works

```
  User uploads the school's own file (Excel / Word)
                    │
                    ▼
  System reads and checks the file
  (finds the tables, grade levels, subjects, dates; rejects bad values)
                    │
                    ▼
  System computes and saves the results
  (totals, averages, MPS roll-ups, attendance status)
                    │
                    ▼
  Dashboard updates automatically
  (KPI tiles, trend sparklines, charts, insights)
```

The importers are built for **the layouts the school already uses** — the
same enrollment sheet, MPS workbook, DepEd KPI report and biometric export —
so staff don't have to change how they prepare their reports. Each import
has a **downloadable blank template** in the expected format.

### Data feeds

| Data feed | Who imports | File | What the system does automatically | Output it produces |
|-----------|-------------|------|-------------------------------------|--------------------|
| **Add Enrollment** | Admin | Excel (.xlsx / .xls / .csv) — the school's per-section enrollment sheet | Reads every sheet (one per count date), finds the GRADE 7–10 blocks, adds up male/female per section, counts sections, uses the latest count of each month, and checks the sums against the sheet's own TOTAL rows | **Enrollment chart** (per grade, male vs female), **Total Enrollees** tile with year-over-year change and sparkline |
| **Import KPI Report** | Admin | Word (.docx) — DepEd "Key Performance Indicator" report | Finds the Indicator table and reads each rate; asks which school year the data is for (YYYY – YYYY prompt) | **Drop-Out Rate** tile, **KPI trend chart**, historical KPI table |
| **Enter / Import MPS Scores** | Teacher | Excel (.xlsx / .xls) — the school's MPS workbook, or typed in on the form | Reads Summative Test 1, Summative Test 2 and Term Examination grids, accepts only the teacher's own subjects/grades, then **computes MPS per subject and per grade level** | **Average MPS** tile, **Performance chart** per grade level, subject ranking, lowest-subject alert |
| **Import Time Records** | Admin, ADAS | Excel — biometric scanner export (punch log or daily record) | Groups punches per employee per day, takes earliest time-in and latest time-out, marks **Late** after 7:30 AM, marks **Absent** when there's no punch, skips weekends and holidays, matches employees by AC-No | Daily attendance table, **attendance summary** on the ADAS dashboard, **personal Present/Absent counts** on Teacher and ADAS dashboards |
| **Generate Certificates** | ADAS | Word template + Excel recipient list | Fills the template's `${Field}` placeholders once per Excel row | One certificate per recipient, bundled into a single ZIP |

### Automatic Insights (decision support)

On top of the charts, the dashboards produce **rule-based Insights** —
short, plain-language findings computed from the fed data, sorted most
urgent first (danger → warning → success → info) and capped at five:

**Admin dashboard insights**
- Lowest-performing subject (when its MPS is below 80%), with its grade level and — when on record — the teacher who handles it (recorded automatically when a teacher saves or imports MPS scores)
- Submission compliance compared with the 85% target (critical below 60%)
- Drop-out rate increase or decrease compared with the previous school year
- Average MPS improvement or decline compared with the previous school year
- Top-performing subject (MPS of 85% or higher)
- Enrollment rise or fall compared with the previous school year
- Number of documents still awaiting review

**Teacher dashboard insights**
- Overdue tasks
- The next task due
- New feedback from the principal
- Personal task completion rate
- Absences this month, or perfect attendance

---

## 4. Functions per Role

### 4.1 Admin (Principal)

| Function | What it does |
|----------|--------------|
| **Admin Dashboard** | One-page overview of the school: Total Enrollees, Drop-Out Rate, Average MPS and Submission Compliance tiles (each with a trend sparkline and change vs last year); KPI trend chart; enrollment chart by grade level and month; performance chart per grade level; subject MPS ranking (average or full per-grade/teacher view); document status chart; recent submissions; and the **Insights** panel. A school-year selector switches every figure. |
| **Add Enrollment** | Uploads the enrollment Excel sheet (data feed). Can download a blank sheet in the school's layout with section names pre-filled from teachers' subject loads. |
| **Import KPI Report** | Uploads the DepEd KPI Word report (data feed) and enters its school year. Can download the blank KPI template. |
| **Announcements** | Posts announcements, forms, and questionnaires; every user gets a notification. Can filter, search, sort, and delete. |
| **Manage Documents** | Opens the folder of each task (created automatically on the first upload), sees every person's submitted files, previews/downloads them, and marks each one **Reviewed** or **Returned** (returning requires a comment saying what to fix). The submitter is notified. |
| **Tasks & Assignments** | Creates tasks with title, description, and deadline, assigned to **all Teachers**, **all ADAS**, or **specific people** (filterable by department). Sees how many submitted out of how many are expected. Opens a task to review submissions and leave feedback; can close, reopen, or delete tasks. Assignees are notified. |
| **Time Records** | Views daily attendance of all staff (filter by date/status, search, sort), imports biometric exports, edits a record (time in/out, status, remarks), and manages the holiday list. |
| **Document Links** | Adds and deletes links to external resources (category: Forms, Guidelines, Questionnaires, Templates; access level: All Users, Teachers, Admin). |
| **Templates** | Views, previews, and downloads templates (with an optional "Convert to PDF" download). |
| **Property Management** | Views the property inventory (view-only). |
| **User Management** | Adds, edits, and deletes accounts of **any** role (including other admins) and resets passwords. For teachers, also sets advisory, grade level, and subjects. |
| **Chat** | Direct messages **and creating group chats**; manages group members. |
| **Profile / Notifications** | Updates personal info, photo, and password; receives notifications (e.g. new submissions). |

### 4.2 Teacher

| Function | What it does |
|----------|--------------|
| **My Dashboard** | Personal overview: task statistics (total, completed, pending, overdue), recent activity, own Present/Absent count (filterable by month), latest announcements, document links for teachers, recent feedback from the principal, and personal **Insights**. |
| **To Do List** | Sees every task assigned to teachers or to them personally, sorted by deadline. Submits files (PDF, Word, Excel, PowerPoint, images; up to 10 MB each) with notes; can resubmit to replace files. Sees status (Pending / Reviewed / Returned) and the principal's feedback. Admin and ADAS are notified on each submission. |
| **Enter MPS Scores** | Enters MPS per **section** for Summative Test 1, Summative Test 2, and Term Examination, by school year and term — only for the subjects, grades and sections in their own subject load. Or **imports** the MPS Excel workbook (data feed), with a downloadable template built from their subject load. The system computes subject and grade-level MPS automatically. |
| **Announcements** | Reads announcements, forms, and questionnaires (read-only). |
| **Document Links** | Opens shared resource links (read-only). |
| **Templates** | Views, previews, and downloads templates. |
| **Property Management** | **Adds and deletes** property items for a grade and section, with item name, quantity, and condition (Excellent / Good / Fair / Poor). Teachers are the only role that can add or delete items. |
| **Chat** | Direct messages (start new conversations, reply, react, edit, unsend, send attachments). Can take part in groups but cannot create them. |
| **Profile** | Updates personal info, photo, and password, and manages their **Subject Load** (subjects, grade levels, sections), which controls their MPS entry form. |

### 4.3 ADAS (Administrative Assistant)

| Function | What it does |
|----------|--------------|
| **My Dashboard** | Today's school-wide attendance summary (Present, Late, Absent, On Leave) and own Present/Absent count (filterable by month). |
| **My Tasks** | Sees tasks assigned to ADAS or to them personally and submits files and notes; sees review status and feedback. |
| **Tasks & Assignments** | Like the admin: creates tasks for teachers, ADAS, or specific people, tracks submissions, reviews them, and gives feedback. |
| **Announcements** | Posts and deletes announcements, forms, and questionnaires. |
| **Document Links** | Adds and deletes resource links. |
| **Time Records** | Imports biometric exports (data feed), edits records, and manages holidays — the main role responsible for attendance encoding. |
| **Templates (manager)** | The **only role that manages the template library**: creates and deletes categories, uploads and deletes templates. Also **generates certificates in bulk** from a Word template and an Excel recipient list. |
| **Property Management** | Views the inventory (view-only). |
| **User Management** | Adds, edits, deletes, and resets passwords of **teacher and ADAS** accounts; cannot create or modify admin accounts. |
| **Chat** | Direct messages; can take part in groups but cannot create them. |
| **Profile** | Updates personal info, photo, and password. |

### 4.4 Shared by All Roles

- **Secure login** with a role-based sidebar; each page also checks the role on the server.
- **Forgot password**: a 6-digit code is emailed to the user, who enters it with a new password.
- **Notification bell** for new tasks, feedback, submissions, and announcements, each linking to the right page.
- **Chat**: read receipts, typing indicator, online/last-seen status, emoji reactions, reply-to, edit and unsend, file attachments, mute and leave group.
- **In-browser file preview** of Word, Excel, PowerPoint, and PDF files without downloading.
- **Light and dark mode**, and a layout that works on phones.

---

## 5. Access Summary

| Feature | Admin | Teacher | ADAS |
|---------|:-----:|:-------:|:----:|
| School-wide dashboard (KPIs, charts, insights) | ✓ | | |
| Personal dashboard | | ✓ | ✓ |
| Import enrollment / KPI report | ✓ | | |
| Enter / import MPS scores | | ✓ | |
| Import time records, edit attendance, holidays | ✓ | | ✓ |
| Create tasks and review submissions | ✓ | | ✓ |
| Submit files to tasks | | ✓ | ✓ |
| Manage Documents (task folders) | ✓ | | |
| Post announcements / add document links | ✓ | | ✓ |
| Read announcements / document links | ✓ | ✓ | ✓ |
| View / download templates | ✓ | ✓ | ✓ |
| Manage templates, generate certificates | | | ✓ |
| Add / delete property items | | ✓ | |
| View property inventory | ✓ | ✓ | ✓ |
| Manage user accounts | ✓ (all roles) | | ✓ (teacher & ADAS only) |
| Create chat groups | ✓ | | |
| Direct chat | ✓ | ✓ | ✓ |

---

## 6. Differences in the Three Roles' Workflows

The three roles work on the **same data** but from different positions:
the **Teacher supplies** academic data and submissions, the **ADAS
processes** records and supports operations, and the **Admin monitors,
reviews, and decides**.

### Admin — monitor, review, decide

1. Logs in to the **Admin Dashboard** and reads the KPI tiles, charts, and **Insights** to see where the school stands.
2. Feeds school-level data: uploads the **enrollment Excel sheet** and the **DepEd KPI report**; the dashboard updates automatically.
3. Posts **tasks** (e.g. "Submit DLL for Week 5") to teachers, ADAS, or specific people, and posts **announcements**.
4. Watches submission counts, then opens **Manage Documents** / the task page to **review** each submission — marks it Reviewed or Returns it with a comment.
5. Uses insights (low MPS subject, low compliance, rising drop-out) to follow up with specific teachers.
6. Manages **user accounts** and creates **group chats** for coordination.

*Focus:* school-wide view and approvals. The admin does not submit tasks, enter MPS, or manage templates.

### Teacher — receive, submit, report performance

1. Logs in to **My Dashboard** and checks Insights for overdue or upcoming tasks and new feedback.
2. Opens the **To Do List**, uploads the required files for each task before the deadline, and resubmits if a file is **Returned**.
3. At the end of each test period, opens **Enter MPS Scores** and types in or **imports** the MPS sheet for their sections. The computed MPS feeds directly into the principal's dashboard.
4. Keeps their **Subject Load** in the profile up to date so the MPS form shows the right subjects and sections.
5. Records classroom **property** items and their condition.
6. Reads announcements, uses document links and templates, and chats with colleagues.

*Focus:* their own tasks and classes. Teachers see only their own data and cannot see other teachers' submissions or the school-wide dashboard.

### ADAS — process records, support operations

1. Logs in to **My Dashboard** and sees today's staff attendance summary.
2. **Imports the biometric export** into Time Records; the system marks Present / Late / Absent automatically. Corrects records and keeps the **holiday list** current.
3. Maintains the **template library** and **generates certificates in bulk** for events (e.g. recognition, seminars) from an Excel list.
4. Helps the principal by **posting announcements and links**, **creating tasks**, and **reviewing submissions**.
5. Creates and maintains **teacher and ADAS accounts**.
6. Submits their own assigned work under **My Tasks**.

*Focus:* operational and clerical processing. ADAS has wide management access but not the school-wide analytics dashboard, and cannot manage admin accounts.

### Workflow differences at a glance

| | Admin | Teacher | ADAS |
|---|---|---|---|
| **Main purpose** | Monitor and decide | Submit and report | Process and support |
| **Starts the day with** | School KPIs and insights | Own tasks and feedback | Today's attendance |
| **Data they feed** | Enrollment, DepEd KPI report | MPS scores | Biometric attendance, certificate lists |
| **Tasks** | Creates and reviews | Receives and submits | Creates, reviews, and also submits |
| **Documents** | Reviews all task folders | Uploads own files | Reviews submissions; manages templates |
| **Scope of data seen** | Whole school | Only their own | Operational records (attendance, users, templates) |

---

## 7. How the System Makes Manual Work Easier

| Manual process before | With ACADOCS |
|-----------------------|--------------|
| Typing enrollment counts from each section's sheet into a summary and computing totals by hand | Upload the same Excel sheet; totals per grade, sections, and monthly snapshots are computed and charted automatically |
| Encoding DepEd KPI figures into separate reports and making trend charts manually | Upload the KPI Word report; rates are stored per school year and trend charts update automatically |
| Collecting MPS sheets from every teacher and averaging them per subject and grade | Teachers enter or upload their own MPS; subject and grade-level MPS are computed and shown on the dashboard instantly |
| Checking biometric logs row by row to mark who was late or absent | Import the scanner export; Present / Late / Absent is decided automatically, skipping weekends and holidays |
| Typing each certificate one by one | Generate all certificates at once from one template and one Excel list |
| Collecting printed or USB documents and tracking who hasn't submitted | Teachers upload online; files are filed into task folders automatically with live submitted/expected counts |
| Giving feedback face to face or on paper | Review status and written feedback stay attached to each submission, and the teacher is notified |
| Posting notices on boards or group chats | Announcements reach every user with an in-app notification |
| Searching folders for the right form or template | One searchable template library with in-browser preview and PDF download |
| Reading spreadsheets to spot problems | The Insights panel points out the lowest subject, compliance gaps, and drop-out changes automatically |
| Paper property inventory per room | Digital inventory per grade and section with condition status, searchable and sortable |

---

## 8. Technology Used

| Layer | Technology |
|-------|------------|
| Framework | CodeIgniter 4 (PHP 8.1+, MVC) |
| Database | MySQL / MariaDB |
| Front end | Bootstrap 5, Bootstrap Icons, SweetAlert2 |
| Charts | Chart.js |
| File reading/writing | PhpSpreadsheet (Excel), PhpWord (Word) |
| File preview | docx-preview, LibreOffice (optional), Office Online viewer (deployed sites) |
