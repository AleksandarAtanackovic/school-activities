# Testing Guide — Extracurricular Activity Tracker (Milestone 1)

This walks you through testing everything built so far, on your local XAMPP
install (`http://localhost/extra`). Work through it top to bottom the first
time; afterwards you can dip into whichever section you care about.

**Scope of this milestone:** accounts & roles, activities, teacher assignment,
applications & approval, enrollment, notifications, user management, and the
new "change password" / forced-first-login feature. Attendance and the Excel/PDF
reports are the next milestones and are intentionally not here yet.

---

## 0. Setup & ground rules

- You should have imported **`schema.sql`** (the demo data) for testing.
- Keep two browsers (or one normal + one private/incognito window) open so you
  can be logged in as two roles at once — e.g. a teacher in one and a student in
  the other. This makes the "student applies → teacher approves" loop fast.
- After login you'll see your name and role in the top-right corner.

### Demo accounts (from `schema.sql`)

| Role    | Email              | Password   | Notes |
|---------|--------------------|------------|-------|
| Admin   | admin@school.test  | admin123   | sees everything |
| Teacher | adams@school.test  | teacher123 | owns Math Project + Robotics |
| Teacher | baker@school.test  | teacher123 | owns Drama Club + Choir |
| Teacher | cohen@school.test  | teacher123 | co-owns Robotics |
| Student | anna@school.test   | student123 | enrolled in Math Project; pending Drama |
| Student | ben@school.test    | student123 | enrolled in Math Project |
| Student | …→ jack@school.test | student123 | (anna, ben, clara, david, ella, felix, gina, hugo, iris, jack) |

### Resetting the data
Any time you want a clean slate, re-import `schema.sql` in phpMyAdmin
(select the database → **Import** → choose `schema.sql` → **Go**). It drops and
recreates everything, so all your test changes disappear.

---

## 1. Five-minute smoke test

1. Visit `http://localhost/extra` → you should be redirected to the **login** page.
2. Log in as **admin@school.test / admin123** → you land on the **Dashboard** with
   four stat cards (Activities, Students, Teachers, Pending applications).
3. Click **Activities** → you see four activities (Choir, Drama Club, Math Project,
   Robotics) with no broken/overflowing lines near the View/Edit buttons.
   *(This is the layout bug you reported — confirm it's gone.)*
4. Click **Log out** → back to the login page.
5. Repeat the login with a teacher and a student account to confirm each lands on
   a different dashboard.

If all five pass, the core install is healthy. Now test properly.

---

## 2. (Optional) Add more students for bigger lists

To see how longer student lists and capacity behave, you can bulk-add 20 students
in one step. In phpMyAdmin → your database → **SQL** tab, paste and run:

```sql
INSERT INTO users (name, email, password, role, grade_class) VALUES
('Test Student 01','s01@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','9-A'),
('Test Student 02','s02@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','9-A'),
('Test Student 03','s03@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','9-A'),
('Test Student 04','s04@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','9-B'),
('Test Student 05','s05@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','9-B'),
('Test Student 06','s06@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','9-B'),
('Test Student 07','s07@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','9-C'),
('Test Student 08','s08@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','9-C'),
('Test Student 09','s09@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','9-C'),
('Test Student 10','s10@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','10-C'),
('Test Student 11','s11@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','10-C'),
('Test Student 12','s12@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','10-C'),
('Test Student 13','s13@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','11-C'),
('Test Student 14','s14@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','11-C'),
('Test Student 15','s15@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','11-C'),
('Test Student 16','s16@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','12-C'),
('Test Student 17','s17@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','12-C'),
('Test Student 18','s18@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','12-C'),
('Test Student 19','s19@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','12-D'),
('Test Student 20','s20@school.test','$2y$10$oH7T6OSEvYIxhhna58UJ1OoCJsj/wbtur2agtPvwFiVoEbHTCBcKC','student','12-D');
```

All of them log in with password **student123** (e.g. s05@school.test / student123).

---

## 3. Admin tests

Log in as **admin@school.test**.

**Create an activity**
1. Activities → **+ New activity**.
2. Fill in name "Chess Club", a description, location "Library", schedule
   "Tuesdays 16:00", **Maximum students = 3**, status Open.
3. Under **Assigned teachers**, tick **two** teachers (e.g. Mr. Adams + Ms. Baker).
4. Save → you land on the activity page showing both teachers and capacity 0/3.
   ✅ Confirms: one activity can have multiple teachers, capacity is set by admin.

**Edit and re-assign**
1. Open Chess Club → **Edit** → untick one teacher → Save.
2. ✅ The teacher list updates. (Confirms admin controls assignments.)

**Create users**
1. **Users** → fill the "Add a user" form → create one **teacher** and one
   **student** (give the student a class like "10-A"). Use a password ≥ 6 chars.
2. ✅ They appear in the list below. Try creating a second user with the **same
   email** → expect a clear "user with that email already exists" error.
3. Try a password under 6 characters → expect a validation error.

**Activate / deactivate**
1. In Users, click **Deactivate** on a test user → status flips to Inactive.
2. Try logging in as that user (other browser) → expect login to fail.
3. Note you **cannot** deactivate your own admin account (it shows "You").

**Review applications (admin sees all)**
1. **Applications** → you see pending ones across *all* activities.
2. Approve one and reject another → statuses update; pending ones sort to the top.

**See everything**
1. Open any activity (even ones you don't teach) → you can Edit it and see its
   enrolled students and post notifications. ✅ Admin override works.

---

## 4. Teacher tests

Log in as **adams@school.test** (owns Math Project + Robotics).

1. **Dashboard / Activities** → you see **only** Math Project and Robotics, not
   Drama Club or Choir. ✅ Scoping works.
2. Open **Math Project** → **Edit** → change the schedule text and set a different
   **maximum students** → Save. ✅ Teachers can edit their own activity's details,
   schedule, and capacity. (Note: the teacher form has no "assigned teachers"
   box — that's admin-only, as intended.)
3. On the Math Project page, post a **notification** (title + message) →
   it appears in the list below. (You'll verify the student sees it in §5.)
4. **Applications** → you see applications only for Math Project and Robotics.
   Approve a pending one → the enrolled count goes up.
5. **Capacity test:** set Math Project's max to a number equal to its current
   approved count, then go to Applications and try to approve another student for
   it → the **Approve** button is disabled / blocked with a "at capacity" message.
   ✅ Capacity is enforced.
6. **Permission test:** in the address bar, manually visit
   `http://localhost/extra/activity_edit.php?id=2` (Drama Club, which Baker owns,
   not Adams) → expect an **"Access denied"** page. ✅ Teachers can't edit others'
   activities even by guessing URLs.

---

## 5. Student tests

Log in as **anna@school.test** (other browser/incognito is ideal).

1. **Activities** → you see open activities with a **My status** column, capacity,
   and a "Full" badge where applicable. Activities you can't apply to (full, or
   not open) won't show an Apply button.
2. Open an activity you're not in (e.g. Robotics) → click **Apply** →
   you get a confirmation and your status becomes **Pending**.
3. Try to apply to the same activity again → it won't let you create a duplicate
   ("you have already applied"). ✅ One application per activity.
4. **My Activities** → shows everything you've applied to, with status
   (approved / pending / rejected), schedule, and teacher(s).
5. **Notifications** → shows notifications **only** from activities you're
   *enrolled in* (approved). Now go approve anna's Robotics application as the
   teacher/admin in the other browser, then reload → posts to that activity start
   appearing in her feed, each tagged with the activity name.
6. **Permission test:** as the student, manually visit
   `http://localhost/extra/users.php` and `http://localhost/extra/applications.php`
   → both should show **"Access denied"**. ✅ Students can't reach staff pages.

---

## 6. Auth & account tests (all roles)

1. **Wrong password:** on the login page, enter a correct email with a wrong
   password → "Incorrect email or password."
2. **Change password:** while logged in, click **Password** (top-right) →
   enter current + a new password twice → it updates and returns you to the
   dashboard. Log out and log back in with the **new** password to confirm; the
   old one should now be rejected.
3. **Logged-out access:** while logged out, try visiting
   `http://localhost/extra/dashboard.php` directly → you're redirected to login.

---

## 7. Edge cases worth a look

- Approve a student, then on the activity page confirm they appear under
  **Enrolled students**.
- Reject a student and confirm they do **not** appear as enrolled and their
  status shows Rejected in their My Activities.
- Set an activity's status to **Closed** or **Archived** (admin/teacher edit) →
  students should no longer be able to apply to it.
- Make an activity **full** and confirm the student-side Apply button disappears
  and shows "This activity is full."

---

## 8. When you're done testing → switch to the clean install

For the real cPanel deployment you said you want **no sample data**, just one
admin. Use **`schema-clean.sql`** instead of `schema.sql`:

1. Import `schema-clean.sql` into your (empty) production database.
2. It creates all the tables plus a single admin:
   - **Login:** `admin@admin.com`
   - **Password:** `admin123`
3. On first login the app **forces** you to set a new password before you can do
   anything else. After that, go to **Users** and create your real teachers and
   students.

(You can also try this flow locally: create a second empty database, import
`schema-clean.sql`, point `config.php` at it, and log in as admin@admin.com.)

---

## Found something off?

Note the page, what you did, and what you expected vs. what happened, and send it
over. Once you're happy with Milestone 1, tell me and I'll start Milestone 2
(attendance), with the report layouts (both orientations) coming in Milestone 3.
