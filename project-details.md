# ACADOCS — Project Details

**ACADOCS** is a web-based School Management Information System built for
**Matabungkay National High School** (Lian, Batangas — Schools Division of
Batangas Province, DepEd Region IV-A CALABARZON). It brings the school's
day-to-day administrative work — document submission and review, tasks,
announcements, staff attendance, enrollment, KPI and MPS reporting,
templates, property inventory, and internal communication —
into one system behind a single role-based login.

The system has three user roles:

| Role | Who uses it |
|------|-------------|
| **Admin** | The School Head / Principal |
| **Teacher** | Teaching staff |
| **ADAS** | Administrative Assistant / support staff |

---

## Summary

ACADOCS replaces the school's scattered spreadsheets, printed forms, USB
drives and group chats with **one shared system**. Every staff member logs in
with their own account, and what they see depends on their role.

In simple terms, the system does five big jobs:

1. **Collects work** — teachers and staff submit required documents online
   against tasks with deadlines, and the principal reviews them.
2. **Collects data** — enrollment sheets, DepEd KPI reports, MPS score sheets
   and biometric attendance are fed into the system from the files the school
   already uses (or pulled straight from the biometric scanner).
3. **Turns data into information** — the system computes totals, averages and
   rates, draws the dashboard charts, and writes plain-language **insights**
   and **alerts** (e.g. "Math · Grade 8 is below mastery").
4. **Keeps everyone informed** — announcements, notifications, reminders and a
   built-in chat connect the principal, teachers and ADAS.
5. **Keeps school records in order** — templates, resource links, property
   inventory with acknowledgment receipts, staff attendance, and user
   accounts.

Each role has a clear job inside this loop: **teachers supply** academic data
and submissions, the **ADAS processes** records and runs daily operations, and
the **principal monitors, reviews and decides**. Because all three work on the
same data, one person's action immediately shows up for the others — a
teacher's MPS entry appears on the principal's dashboard, an ADAS biometric
import appears on each teacher's attendance count, and a principal's review
appears in the teacher's To Do List. Sections 8 and 9 explain these connections in
detail, including who monitors what.

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
8. **Speed up document preparation** — keep a shared, searchable template
   library of the school's forms and documents with in-browser preview.
9. **Improve school communication** — provide announcements, in-app
   notifications and a built-in chat (direct and group messages).
10. **Keep an inventory of school property** — record room and section
    assets and their condition, issue them to advisers with a Property
    Acknowledgment Receipt, and handle reported concerns.
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
| **Templates** | The school's document and form templates, grouped by category |
| **Resource links** | External links to DepEd forms, guidelines, and questionnaires |
| **Property inventory** | Items per grade and section, their condition (Working / Not Working) and audit history, who each item is issued to, acknowledgment receipts, and reported concerns |
| **Communication** | Chat conversations, attachments, and notifications |
| **User accounts** | All staff accounts, roles, biometric numbers, and teacher subject loads |
| **Reports** | Printable school performance reports generated from the same data as the dashboard |

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
| **Add Enrollment** | Admin, ADAS | Excel (.xlsx / .xls / .csv) — the school's per-section enrollment sheet | Reads every sheet (one per count date), finds the GRADE 7–10 blocks, adds up male/female per section, counts sections, uses the latest count of each month, and checks the sums against the sheet's own TOTAL rows | **Enrollment chart** (per grade, male vs female), **Total Enrollees** tile with year-over-year change and sparkline |
| **Import KPI Report** | Admin, ADAS | Word (.docx) — DepEd "Key Performance Indicator" report | Finds the Indicator table and reads each rate; asks which school year the data is for (YYYY – YYYY prompt) | **Drop-Out Rate** tile (with the 0% target), **KPI trend chart**, historical KPI table |
| **Enter / Import MPS Scores** | Teacher | Excel (.xlsx / .xls) — the school's MPS workbook, or typed in on the form | Reads Summative Test 1, Summative Test 2 and Term Examination grids, accepts only the teacher's own subjects/grades, then **computes MPS per subject and per grade level** | **Average MPS** tile (with the 75% target), **Performance chart** per grade level, subject ranking, lowest-subject alert |
| **Biometric Sync (automatic)** | Nobody — runs by itself | Connects to the school's biometric scanner over the network every few minutes | Reads only the new punches since the last sync and rebuilds those days' attendance using the same rules as an import; the ADAS can also press **Sync** to pull right away | Time Records stay current through the day without anyone importing a file |
| **Import Time Records** | Admin, ADAS | Excel — biometric scanner export (punch log or daily record) | Groups punches per employee per day, takes earliest time-in and latest time-out, marks **Late** after 7:30 AM, marks **Absent** when there's no punch, skips weekends and holidays, matches employees by AC-No and ties each record to the person's account (so history stays with them even if their number changes) | Daily attendance table, **personal Present/Absent counts** on the Teacher dashboard |

### Automatic Insights (decision support)

On top of the charts, the dashboards produce **rule-based Insights** —
short, plain-language findings computed from the fed data, sorted most
urgent first (danger → warning → success → info) and capped at five:

**Admin dashboard insights**
- Lowest-performing subject (when its MPS is below the **75% MPS target**), with its grade level and — when on record — the teacher who handles it (recorded automatically when a teacher saves or imports MPS scores); the teacher's name is clickable and opens their profile
- Submission compliance compared with the 85% target (critical below 60%)
- Drop-out rate compared with the school's **0% drop-out target**, and its increase or decrease compared with the previous school year
- Average MPS improvement or decline compared with the previous school year
- Top-performing subject (when it meets the 75% MPS target)
- Enrollment rise or fall compared with the previous school year
- Number of documents still awaiting review

**Teacher dashboard insights**
- Overdue tasks
- The next task due
- New feedback on a submission, naming who wrote it
- Personal task completion rate
- Absences this month, or perfect attendance

### Learner Performance Alerts

Whenever a teacher saves or imports MPS scores, the system checks each class.
If a class's MPS falls **below 75% (mastery level)**, both the **principal**
and **the subject teacher** get a notification naming the class and its
descriptor (e.g. *Developing* or *Emerging*). The alert updates if the class
drops further, and disappears by itself once the class reaches 75% again — so
the notification list always reflects the current situation.

### Template Auto-Classification and Auto-Tagging

When the ADAS uploads a template, the system **reads the document's own
text** (Word, Excel, PowerPoint, PDF) along with its file name and title, then
**suggests which section it belongs to** and **adds tags** such as
*Certificate*, *Report*, *Checklist* or *Procurement*, plus the document's key
words. It learns from the templates already filed, so suggestions get better
the more the library is used. Several files can be uploaded at once and each
is filed on its own. The tags and key words make the library searchable by
content, not just by file name. All of this runs on the school's own server —
no outside service sees the files.

### Automations (work the system does by itself)

Some jobs happen without anyone clicking anything. They run on a schedule
(Windows Task Scheduler) and, as a backup, during normal page visits:

| Automation | What happens |
|------------|--------------|
| **Scheduled announcements** | An announcement can be set to publish on a future date (everyone is notified then) and to archive itself when it expires. |
| **Term reminders** | When the end-of-term period starts, teachers are reminded to enter MPS scores; the day after the term ends, those still missing scores get a follow-up. |
| **Report pack notices** | The day after a term (or the school year) ends, the principal and ADAS are told the end-of-term / end-of-year report pack is ready. |
| **Biometric pull** | New scanner punches are copied into Time Records every few minutes. |
| **Daily cleanup** | Removes used password-reset codes, old notifications, abandoned chats and files, and leftover import files, keeping the system tidy. |

The system also follows the **DepEd school calendar** (school years, terms,
and academic breaks), so it knows which term today falls in, how many class
days are left, and when term-based reminders and reports are due.

---

## 4. Functions per Role

### 4.1 Admin (Principal)

| Function | What it does |
|----------|--------------|
| **Admin Dashboard** | One-page overview of the school: Total Enrollees, Drop-Out Rate, Average MPS and Submission Compliance tiles (each with a trend sparkline and change vs last year); KPI trend chart; enrollment chart by grade level and month; MPS chart per grade level; subject MPS ranking (average or full per-grade/teacher view); document status chart; and the **Insights** panel. The **School Year** filter switches every figure, and its **Custom…** option shows a year range — the last 5 years, the last 10 years, or any range (e.g. 2014–2022) — for the trend chart, the DepEd history, and the insights. |
| **Generate Report** | Opens a pop-up that pre-selects the school year (or year range) the dashboard is filtered to and lists checkboxes for each section: key figures, insights, enrollment by grade level, total enrollees, drop-out rate, average MPS, performance by learning area, DepEd historical KPIs, and document submission status. Produces a printable A4 report with the DepEd letterhead, charts and tables matching the dashboard, the insights, and a signature block — ready to print or save as PDF. Available to the principal and ADAS. |
| **End-of-Term / End-of-Year Report Pack** | A ready-made report for one term (or the whole school year) on the DepEd calendar: enrollment, staff attendance, MPS by learning area, classes below mastery, and task compliance. The system announces when it is ready. Available to the principal and ADAS. |
| **Add Enrollment** | Uploads the enrollment Excel sheet (data feed). Can download a blank sheet in the school's layout with section names pre-filled from teachers' subject loads. |
| **Import KPI Report** | Uploads the DepEd KPI Word report (data feed) and enters its school year. Can download the blank KPI template. |
| **Announcements** | Posts announcements, forms, and questionnaires (only the title is required; content and a **photo** are optional); every user gets a notification. Opening one shows a pop-up with the photo (or the type's icon), title, date, **posted by**, and content, closed with **I Understand**; the photo can be viewed full size. Any announcement can be **shared to chat** (people or group chats), where it appears as a card. Can filter, search, sort, and delete. |
| **Manage Documents** | Opens the folder of each task (created automatically on the first upload) and sees every person's submitted files, with the date and time uploaded and an **On time / Late** badge. Previews files in a pop-up, downloads, or **annotates** them. Marks each **Pending** upload **Reviewed** or **Returned** (returning requires a comment saying what to fix); a decided upload can only be switched to the other decision. Each status shows **who reviewed it and when**. The submitter is notified. |
| **Tasks & Assignments** | Creates tasks with title, description, and deadline, assigned to **all Teachers**, **all ADAS**, or **specific people** (filterable by department). Sees how many submitted out of how many are expected, who hasn't submitted yet, and whether each submission was **on time or late** (and by how much). Opens a task to **review submissions**, **leave feedback**, and **annotate files** — drawing, highlighting, and writing notes directly on a file and saving a marked-up copy the teacher can open (the original is never changed). These approvals are principal-only, and each one records who did it and when. **Open in Manage Documents** jumps to the task's folder. Can close, reopen, or delete tasks. Assignees are notified. |
| **Time Records** | Views daily attendance of all staff (filter by date/status, search, sort), imports biometric exports, edits a record (time in/out, status, remarks), and manages the holiday list. Employee names open their profile. |
| **Document Links** | Adds and deletes links to external resources (category: Forms, Guidelines, Questionnaires, Templates; access level: All Users, Teachers, Admin). |
| **Templates** | Views, previews, searches (by title, tag, or content key words), and downloads templates (with an optional "Convert to PDF" download). |
| **Property Management** | Views the property inventory, acknowledgment receipts, and concerns (view-only), filtered by grade, section, and condition — to see the state of school assets without changing them. |
| **User Management** | Adds, edits, and deletes accounts of **any** role (including other admins) and resets passwords. For teachers, also sets advisory, grade level, and subjects. Sets each person's **Biometric No. (AC-No)** — the number they use on the scanner — which links their time records to their account (numbers must be unique; unlinked scanner numbers are suggested). |
| **Chat** | Direct messages **and creating group chats**; manages group members. |
| **Profile / Notifications** | Updates personal info, photo, and password; receives notifications (e.g. new submissions). |

### 4.2 Teacher

| Function | What it does |
|----------|--------------|
| **My Dashboard** | Personal overview: task statistics (total, completed, pending, overdue), recent activity, own Present/Absent count (filterable by month), latest announcements, document links for teachers, recent feedback, and personal **Insights**. |
| **To Do List** | Sees every task assigned to teachers or to them personally, sorted by deadline. Submits files (PDF, Word, Excel, PowerPoint, images; up to 10 MB each) with notes; can resubmit to replace files (a resubmission needs a fresh review). Sees when they submitted and whether it was **on time or late**, the status (Pending / Reviewed / Returned) with **who reviewed it**, the feedback with its author, and the principal's **marked-up copy** of their file, if any. Admin and ADAS are notified on each submission. |
| **Enter MPS Scores** | Enters MPS per **section** for Summative Test 1, Summative Test 2, and Term Examination, by school year and term — only for the subjects, grades and sections in their own subject load. Or **imports** the MPS Excel workbook (data feed), with a downloadable template built from their subject load. The system computes subject and grade-level MPS automatically. |
| **Announcements** | Reads announcements, forms, and questionnaires (read-only). |
| **Document Links** | Opens shared resource links (read-only). |
| **Templates** | Views, previews, and downloads templates. |
| **Property Management** | Sees **only the items issued to them** as adviser. **Confirms receipt** by approving the Property Acknowledgment Receipt (PAR) the ADAS sends — or returns it if something is wrong — and **reports concerns** about an item (e.g. broken, missing), which notifies the ADAS. |
| **Chat** | Direct messages (start new conversations, reply, react, edit, unsend, send attachments). Can take part in groups but cannot create them. |
| **Profile** | Updates personal info, photo, and password, and manages their **Subject Load** (subjects, grade levels, sections), which controls their MPS entry form. Shows their **Biometric No.** (read-only; set in User Management). |

### 4.3 ADAS (Administrative Assistant)

| Function | What it does |
|----------|--------------|
| **Dashboard** | The same school-wide dashboard as the principal: KPI tiles, trend charts, enrollment and performance charts, Insights, the school-year / year-range filter, and **Generate Report**. |
| **Add Enrollment / Import KPI Report** | Feeds enrollment Excel sheets and DepEd KPI reports into the dashboard (data feed), same as the principal. |
| **My Tasks** | Sees tasks assigned to ADAS or to them personally and submits files and notes; sees review status and feedback. |
| **Tasks & Assignments** | Creates tasks for teachers, ADAS, or specific people and tracks who has and hasn't submitted. Can open, preview, and download every submission and see the principal's marked-up copies and feedback, but **reviewing, feedback, and annotating are reserved for the principal**. |
| **Announcements** | Posts and deletes announcements, forms, and questionnaires. |
| **Document Links** | Adds and deletes resource links. |
| **Generate Report / Report Pack** | Produces the printable school report and the end-of-term / end-of-year report pack, same as the principal. |
| **Time Records** | Watches the automatic biometric sync (and can press **Sync** to pull now), imports biometric exports when needed (data feed), edits records, and manages holidays — the main role responsible for attendance. |
| **Templates (manager)** | The **only role that manages the template library**: creates and deletes sections, adds optional **folders inside a section** (and folders within folders) to organize large sections, uploads, moves and deletes templates. The system suggests each template's section and tags from its content; the ADAS confirms or changes them. |
| **Property Management (manager)** | The **only role that runs the inventory**: adds, edits and removes items; **audits** each item's condition (Working / Not Working, with remarks and a history of changes); **issues** a room's items to the section adviser with a **Property Acknowledgment Receipt (PAR)**; **resolves concerns** teachers report; and exports the inventory to Excel. |
| **User Management** | Adds, edits, deletes, and resets passwords of **teacher and ADAS** accounts, including their **Biometric No. (AC-No)**; cannot create or modify admin accounts. |
| **Chat** | Direct messages; can take part in groups but cannot create them. |
| **Profile** | Updates personal info, photo, and password. |

### 4.4 Shared by All Roles

- **Secure login** with a role-based sidebar; each page also checks the role on the server.
- **Forgot password**: a 6-digit code is emailed to the user, who enters it with a new password.
- **Notification bell** for new tasks, feedback, submissions, and announcements, each linking to the right page. Deleting a task or announcement removes its notifications too.
- **Profile cards**: clicking a person's name — in the Insights, Time Records, Manage Documents, Tasks, User Management, or Chat — opens a pop-up with their role, email, Biometric No., advisory class, subjects and sections handled, and a **Chat** button.
- **Chat**: read receipts, typing indicator, online/last-seen status, emoji reactions, reply-to, edit and unsend, file attachments, shared announcement cards, mute and leave group.
- **In-browser file preview** of Word, Excel, PDF, and image files without downloading.
- **Clear forms**: required fields are marked with a red asterisk and the rest with "(optional)".
- **Login page** with the live date and time.
- **Light and dark mode**, and a layout that works on phones.

---

## 5. Access Summary

| Feature | Admin | Teacher | ADAS |
|---------|:-----:|:-------:|:----:|
| School-wide dashboard (KPIs, charts, insights) | ✓ | | ✓ |
| Generate printable school report | ✓ | | ✓ |
| Personal dashboard | | ✓ | |
| Import enrollment / KPI report | ✓ | | ✓ |
| Enter / import MPS scores | | ✓ | |
| Import time records, edit attendance, holidays | ✓ | | ✓ |
| Create tasks and track submissions | ✓ | | ✓ |
| Review submissions (Reviewed / Returned), give feedback, annotate files | ✓ | | |
| Submit files to tasks | | ✓ | ✓ |
| Manage Documents (task folders) | ✓ | | |
| Post announcements / add document links | ✓ | | ✓ |
| Read announcements / document links | ✓ | ✓ | ✓ |
| View / download templates | ✓ | ✓ | ✓ |
| Manage templates | | | ✓ |
| End-of-term / end-of-year report pack | ✓ | | ✓ |
| Run property inventory (add, audit, issue PAR, resolve concerns) | | | ✓ |
| Confirm PAR / report property concern | | ✓ (own items) | |
| View property inventory | ✓ | ✓ (own items) | ✓ |
| Receive learner performance (MPS) alerts | ✓ | ✓ (own classes) | |
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

1. Logs in to the **Admin Dashboard** and reads the KPI tiles, charts, and **Insights** to see where the school stands — for the current year or a range of years — and generates a printable report when needed.
2. Feeds school-level data: uploads the **enrollment Excel sheet** and the **DepEd KPI report**; the dashboard updates automatically.
3. Posts **tasks** (e.g. "Submit DLL for Week 5") to teachers, ADAS, or specific people, and posts **announcements**.
4. Watches submission counts and late submissions, then opens **Manage Documents** / the task page to **review** each submission — marks it Reviewed or Returns it with a comment, and can annotate the file directly.
5. Uses insights (low MPS subject, low compliance, rising drop-out) to follow up with specific teachers.
6. Manages **user accounts** and creates **group chats** for coordination.

*Focus:* school-wide view and approvals. The admin does not submit tasks, enter MPS, or manage templates.

### Teacher — receive, submit, report performance

1. Logs in to **My Dashboard** and checks Insights for overdue or upcoming tasks and new feedback.
2. Opens the **To Do List**, uploads the required files for each task before the deadline, and resubmits if a file is **Returned**.
3. At the end of each test period, opens **Enter MPS Scores** and types in or **imports** the MPS sheet for their sections. The computed MPS feeds directly into the principal's dashboard.
4. Keeps their **Subject Load** in the profile up to date so the MPS form shows the right subjects and sections.
5. Confirms the **property** issued to their advisory class (approves the PAR) and reports any concerns about it.
6. Reads announcements, uses document links and templates, and chats with colleagues.

*Focus:* their own tasks and classes. Teachers see only their own data and cannot see other teachers' submissions or the school-wide dashboard.

### ADAS — process records, support operations

1. Logs in to the school **Dashboard** (same view as the principal) and feeds **enrollment sheets** and **DepEd KPI reports** into it.
2. **Imports the biometric export** into Time Records; the system marks Present / Late / Absent automatically. Corrects records and keeps the **holiday list** current.
3. Maintains the **template library** — categories, uploads, and removals — so everyone works from the current forms.
4. Helps the principal by **posting announcements and links**, **creating tasks**, and **tracking who hasn't submitted**. Approving the work itself is left to the principal.
5. Runs the **property inventory**: audits item condition, issues items to advisers with a PAR, and resolves reported concerns.
6. Creates and maintains **teacher and ADAS accounts**.
7. Submits their own assigned work under **My Tasks**.

*Focus:* operational and clerical processing, plus keeping the school dashboard's data up to date. ADAS sees the same dashboard as the principal but cannot manage admin accounts.

### Workflow differences at a glance

| | Admin | Teacher | ADAS |
|---|---|---|---|
| **Main purpose** | Monitor and decide | Submit and report | Process and support |
| **Starts the day with** | School KPIs and insights | Own tasks and feedback | School KPIs and insights |
| **Data they feed** | Enrollment, DepEd KPI report | MPS scores | Enrollment, DepEd KPI report, biometric attendance |
| **Tasks** | Creates, reviews, and approves | Receives and submits | Creates, tracks, and also submits |
| **Documents** | Reviews, returns, and annotates submissions | Uploads own files; sees feedback and marked-up copies | Views submissions (no reviewing); manages templates |
| **Property** | Views only | Confirms receipt of own items; reports concerns | Runs the whole inventory |
| **Scope of data seen** | Whole school | Only their own | Whole school, plus operational records (attendance, users, templates) |

---

## 7. How the System Makes Manual Work Easier

| Manual process before | With ACADOCS |
|-----------------------|--------------|
| Typing enrollment counts from each section's sheet into a summary and computing totals by hand | Upload the same Excel sheet; totals per grade, sections, and monthly snapshots are computed and charted automatically |
| Encoding DepEd KPI figures into separate reports and making trend charts manually | Upload the KPI Word report; rates are stored per school year and trend charts update automatically |
| Collecting MPS sheets from every teacher and averaging them per subject and grade | Teachers enter or upload their own MPS; subject and grade-level MPS are computed and shown on the dashboard instantly |
| Checking biometric logs row by row to mark who was late or absent | Import the scanner export; Present / Late / Absent is decided automatically, skipping weekends and holidays |
| Collecting printed or USB documents and tracking who hasn't submitted | Teachers upload online; files are filed into task folders automatically with live submitted/expected counts |
| Giving feedback face to face or on paper | Review status and written feedback stay attached to each submission, and the teacher is notified |
| Marking up a printed document with a red pen | Annotate the submitted file on screen and save a marked-up copy the teacher can open |
| Compiling a performance report for the division by hand | Generate Report produces a printable report with the DepEd letterhead, charts, tables, and insights |
| Posting notices on boards or group chats | Announcements reach every user with an in-app notification |
| Searching folders for the right form or template | One searchable template library with in-browser preview and PDF download |
| Reading spreadsheets to spot problems | The Insights panel points out the lowest subject, compliance gaps, and drop-out changes automatically |
| Paper property inventory per room and handwritten acknowledgment receipts | Digital inventory per grade and section with condition history; PARs issued and confirmed online; concerns reported and tracked to resolution |
| Remembering to remind teachers about MPS at the end of each term | Term reminders and follow-ups are sent automatically from the school calendar |
| Manually copying the biometric log every day | The system pulls new punches from the scanner every few minutes |
| Deciding which folder a new form belongs in | The template library suggests the section and tags from the document's content |

---

## 8. How the Parts Work Together

ACADOCS is not a set of separate tools — it is **one system made of
interrelated parts**. Each part takes something in, does its job, and passes
its result to another part. Remove one and the others lose information they
depend on.

### 8.1 The parts of the system

| Part | Its job | What it receives | What it gives to other parts |
|------|---------|------------------|------------------------------|
| **User accounts & roles** | Decides who can see and do what | Accounts made by the principal / ADAS; teachers' subject loads; Biometric Nos. | Identity for every other part: who submitted, who reviewed, whose attendance, whose classes |
| **Tasks & submissions** | Collects required work by a deadline | Tasks from principal / ADAS; files from teachers and ADAS | Submission counts → dashboard compliance tile and insights; files → Manage Documents |
| **Document review** | Checks the quality of submitted work | Submitted files | Status (Reviewed / Returned), feedback and marked-up copies → back to the submitter; notifications |
| **Data feeds** (enrollment, KPI, MPS, attendance) | Turns the school's own files into stored figures | Excel / Word files; the biometric scanner | Clean numbers → dashboard, insights, alerts, reports |
| **Dashboards & insights** | Shows where the school stands and what needs attention | Figures from every data feed and from tasks | What the principal acts on; content for reports |
| **Reports** (Generate Report, Report Pack) | Produces printable documents for the division and records | Same figures as the dashboard | Printed / PDF reports |
| **School calendar & automations** | Keeps time-based work moving | DepEd calendar, holidays, academic breaks | Reminders, report-pack notices, scheduled announcements, biometric pulls, cleanup |
| **Notifications** | Tells the right person that something happened | Events from every other part | A bell alert that links straight to the right page |
| **Announcements & chat** | Day-to-day communication | Posts and messages from users | Shared information; announcements can be shared into chats |
| **Templates & document links** | Gives everyone the correct, current forms | Uploads by ADAS (auto-tagged); links by principal / ADAS | Forms used to prepare task submissions |
| **Property management** | Keeps track of school assets | Items, audits and PARs from ADAS; confirmations and concerns from teachers | Accountability record of who holds what and in what condition |

### 8.2 How information flows

```
         TEACHERS                    ADAS                     PRINCIPAL
     (supply data & work)    (process & operate)       (monitor & decide)
            │                         │                         ▲
   MPS scores, submissions   Enrollment, KPI report,            │
   PAR confirmations,        biometric attendance,              │
   property concerns         templates, inventory               │
            │                         │                         │
            ▼                         ▼                         │
     ┌──────────────────────────────────────────────┐           │
     │          ONE SHARED SCHOOL DATABASE          │           │
     └──────────────────────────────────────────────┘           │
            │                         │                         │
            ▼                         ▼                         │
   Computations & checks      Calendar & automations            │
   (MPS, rates, totals,       (reminders, sync, pack            │
    attendance status)         notices, cleanup)                │
            │                         │                         │
            └──────────┬──────────────┘                         │
                       ▼                                        │
     Dashboards · Insights · Alerts · Reports · Notifications ──┘
                       │
                       ▼
     Principal's decisions → new tasks, feedback, announcements
                       │
                       └──────────► back to Teachers and ADAS
```

The cycle repeats every term: **data comes in → the system computes → people
are informed → the principal acts → new work goes out**.

### 8.3 Examples of interdependence

- **MPS scores → dashboard → alert → follow-up.** A teacher enters MPS for
  their sections. The dashboard's Average MPS tile and subject ranking update
  at once. If a class is below 75%, both the teacher and the principal are
  alerted, and the insight names the teacher handling the lowest subject. The
  principal can click that name, open the profile, and chat with the teacher.
  *Depends on:* the teacher's subject load in their profile — without it, the
  MPS form would not know which classes to show.
- **Biometric scanner → time records → teacher dashboard.** The scanner's
  punches are pulled automatically and matched to each person by their
  Biometric No. Present / Late / Absent is decided using the holiday list and
  the school calendar. Each teacher's own dashboard then shows their
  attendance count and an absence insight. *Depends on:* the ADAS or principal
  setting each person's Biometric No. in User Management, and the ADAS keeping
  the holiday list current.
- **Task → submission → review → notification.** The principal or ADAS posts
  a task; assignees are notified. A teacher submits; the principal and ADAS
  are notified, and the file lands in the task's folder. The principal
  reviews or returns it with feedback; the teacher is notified and sees the
  marked-up copy. Meanwhile the submission count feeds the **Submission
  Compliance** tile and insight on the dashboard.
- **Templates → submissions.** The ADAS keeps the template library current
  (helped by auto-tagging), so teachers download the right form before
  submitting a task — fewer submissions get returned.
- **Calendar → automations → reports.** The DepEd school calendar tells the
  system when a term ends. Teachers are reminded to finish MPS entries, and
  the next day the principal and ADAS are told the report pack is ready —
  which itself is built from enrollment, attendance, MPS and task data.
- **Inventory → PAR → concern → resolution.** The ADAS issues a room's items
  to its adviser. The teacher confirms receipt (or returns the PAR). Later the
  teacher reports a broken item; the ADAS is notified, audits it, marks it
  *Not Working*, and resolves the concern — the teacher is notified, and the
  principal can see the whole history.
- **User accounts → everything.** Every record carries *who* did it — who
  submitted, who reviewed and when, who posted, who handles a subject. This is
  what makes accountability and the profile cards possible across the system.

---

## 9. Monitoring: Who Watches What

Each role monitors a different slice of the same system.

### Principal (Admin) monitors the whole school

| What is monitored | Where | What signals a problem |
|-------------------|-------|------------------------|
| Learner performance | Dashboard MPS tile, performance chart, subject ranking, MPS alerts | Subject MPS below the 75% target (insight); class below 75% (alert) |
| Enrollment and drop-out | Total Enrollees and Drop-Out Rate tiles, KPI trend chart | Falling enrollment or rising drop-out vs. last year |
| Staff compliance | Submission Compliance tile, task pages | Below 85% target (critical below 60%); late or missing submissions |
| Quality of submitted work | Manage Documents, task review | Documents still pending review |
| Staff attendance | Time Records | Late or absent entries |
| School assets | Property Management (view-only) | Items *Not Working*, open concerns, unconfirmed PARs |
| Long-term trends | School Year filter with year ranges; reports | Multi-year decline in any KPI |

### ADAS monitors operations

| What is monitored | Where | What signals a problem |
|-------------------|-------|------------------------|
| Biometric sync | Time Records (last sync status) | Sync failed or no recent punches |
| Attendance accuracy | Time Records | Unmatched scanner numbers; missing holidays |
| Who hasn't submitted | Tasks & Assignments | Missing or late submissions to follow up on |
| Template library | Templates | Low-confidence section suggestions to double-check |
| Property | Property Management | Open concerns; returned or pending PARs; items needing audit |
| Data feeds for the dashboard | Dashboard | Missing enrollment months or KPI years |
| User accounts | User Management | New staff without accounts or Biometric No. |

### Teacher monitors their own work

| What is monitored | Where | What signals a problem |
|-------------------|-------|------------------------|
| Tasks and deadlines | My Dashboard, To Do List | Overdue tasks; task due soon |
| Feedback on submissions | To Do List, notifications | A **Returned** submission needing a fix |
| Their classes' performance | Enter MPS Scores, MPS alerts | A class below 75% mastery |
| Own attendance | My Dashboard | Absences this month |
| Issued property | Property Management | A PAR waiting for confirmation; condition of their items |

### How monitoring connects the roles

The monitoring is **layered**: teachers watch their own tasks and classes,
the ADAS watches day-to-day operations and data quality, and the principal
watches the overall results. Problems surface at the level that can fix them
first — a teacher sees a returned file before it becomes a compliance issue,
the ADAS sees a failed sync before attendance figures go wrong — and only
what remains reaches the principal's dashboard as an insight.

---

## 10. Technology Used

| Layer | Technology |
|-------|------------|
| Framework | CodeIgniter 4 (PHP 8.1+, MVC) |
| Database | MySQL / MariaDB |
| Front end | Bootstrap 5, Bootstrap Icons, SweetAlert2 |
| Charts | Chart.js |
| File reading/writing | PhpSpreadsheet (Excel), PhpWord (Word) |
| File preview | docx-preview, LibreOffice (optional), Office Online viewer (deployed sites) |
| Annotation | PDF.js (renders files), pdf-lib (saves the marked-up copy) |
| Biometric connection | Direct network link to the ZKTeco scanner |
| Scheduling | Windows Task Scheduler (`php spark acadocs:automate`), with page visits as a backup |
