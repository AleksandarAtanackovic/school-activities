# Extracurricular Activity Tracker — Deployment Guide (cPanel)

A self-hosted PHP + MySQL web app for tracking high-school extracurricular
activities. No framework, no build step, no external services — it runs on any
standard cPanel/Apache + MySQL shared host.

This is **Milestone 1**: accounts & roles, activities, teacher assignment,
applications & approval, enrollment, notifications, and user management.
Attendance tracking and the Excel/PDF reports come in the next milestones
(the database is already built to support them).

---

## What's included

```
config.php          <- the ONLY file you need to edit
schema.sql          <- import this once into your MySQL database
.htaccess           <- security hardening
index.php           login.php   logout.php   dashboard.php
activities.php      activity_edit.php   activity_view.php   apply.php
applications.php    my_activities.php   notifications.php   users.php
includes/           shared code (db, auth, helpers, layout)
assets/style.css    styling
```

---

## Deploy to cPanel (about 5 minutes)

### 1. Create the database
In cPanel → **MySQL Databases**:
1. Create a new database (e.g. `extra`). cPanel will name it like `cpaneluser_extra`.
2. Create a new MySQL user with a strong password.
3. Add that user to the database and grant **ALL PRIVILEGES**.
Write down the final database name, user name, and password.

### 2. Import the tables + sample data
In cPanel → **phpMyAdmin**:
1. Select your new database in the left sidebar.
2. Open the **Import** tab → choose `schema.sql` → **Go**.
You should see the tables created and sample data inserted.

### 3. Upload the files
In cPanel → **File Manager** (or FTP):
- Upload everything **into `public_html`** (or into a subfolder like
  `public_html/activities/` if you want it at `yoursite.com/activities`).
- Make sure the hidden `.htaccess` files are uploaded too (enable
  "Show Hidden Files" in File Manager settings).

### 4. Configure
Edit **`config.php`** and set:
```php
define('DB_HOST', 'localhost');          // usually 'localhost' on cPanel
define('DB_NAME', 'cpaneluser_extra');   // your database name
define('DB_USER', 'cpaneluser_extra');   // your database user
define('DB_PASS', 'your-password');      // your database password
```
Also set `define('DEBUG', false);` once it's working, for the live site.

### 5. Open it
Visit your site. You'll land on the login screen.

---

## Demo accounts (from the seed data)

| Role    | Email                | Password    |
|---------|----------------------|-------------|
| Admin   | admin@school.test    | admin123    |
| Teacher | adams@school.test    | teacher123  |
| Student | anna@school.test     | student123  |

(Other teachers: baker@, cohen@ — same password. Other students:
ben@, clara@, … — password `student123`.)

**Before going live:** log in as admin → **Users**, create your real accounts,
and deactivate or change the demo ones. Each new user gets a temporary password
you set when creating them.

---

## What each role can do (Milestone 1)

**Admin** — sees and manages everything: create/edit activities, assign one or
more teachers to each activity, set capacity and schedule, review and approve/
reject any application, manage all users.

**Teacher** — sees only their assigned activities; edits their details, sets the
schedule and the maximum student count, reviews applications and approves/rejects
them (capacity is enforced), sees enrolled students, and posts notifications.

**Student** — browses open activities, applies (one application per activity),
tracks application status, sees a "My Extracurricular Activities" page, and reads
a single notifications feed from all activities they're enrolled in.

---

## Notes

- Passwords are stored hashed (bcrypt). All database access uses prepared
  statements; all forms are CSRF-protected; pages are guarded by role on the
  server side.
- All links are relative, so the app works whether it sits at the domain root
  or in a subfolder.
- If you ever get a `500` error right after uploading, your host may use an
  older Apache. Open `.htaccess` and replace each `Require all denied` line with
  `Deny from all`, then retry.

## Coming next (milestones 2 & 3)
- **Attendance**: per-session attendance taking (fast "mark all present" +
  exceptions), with flexible dates driven by when the teacher records them.
- **Reports** with Excel (.xlsx) and PDF export: student list per activity,
  number of sessions, the student × date attendance matrix, and the end-of-year
  summary with per-student attendance counts and percentages.