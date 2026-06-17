<?php
// =====================================================================
// CONFIGURATION  --  EDIT THESE VALUES AFTER UPLOADING TO cPanel
// =====================================================================
// In cPanel: create a MySQL database + a MySQL user, add the user to the
// database with ALL PRIVILEGES, then paste those details below.
// On cPanel, DB names/users are usually prefixed, e.g. "cpanelusr_extra".

define('DB_HOST', 'localhost');          // almost always 'localhost' on cPanel
define('DB_NAME', 'extracurricular');    // your database name
define('DB_USER', 'root');               // your database user
define('DB_PASS', '');                   // your database password

define('APP_NAME', 'Extracurricular Activity Tracker');

// School year boundaries (used as defaults for reports). Adjust per year.
define('SCHOOL_YEAR_START', '2025-09-01');
define('SCHOOL_YEAR_END',   '2026-06-30');

// Show PHP errors? Set to false on the live site.
define('DEBUG', true);
