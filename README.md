# StudentHub

StudentHub is a college student portal built for the **Web Development Frameworks (ITUE203)** lab at CHARUSAT (FTE, Semester 3, 2026-27). It has a public website, a student area and an admin area, backed by PHP and MySQL.

**Author:** Keval Solanki (D26IT129)

## User roles

| Role | What they can do |
|---|---|
| Visitor | Browse Home, About, Events, FAQ, Contact and Feedback; register; log in |
| Student | Log in, open the student dashboard, view profile and campus events |
| Admin | Log in, view the admin dashboard (statistics, latest registrations, audit log), manage students, manage events (with poster upload), use the AJAX event manager |

## Features built so far

- Semantic, accessible HTML5 pages with a consistent layout
- Responsive design with Bootstrap, CSS Grid and Flexbox
- JavaScript interactivity: FAQ accordion, modal, event slider, hamburger menu, light/dark theme saved in `localStorage`
- Registration form with client-side validation and a password strength meter
- JSON rendering (events, FAQs) with Fetch API, search, filter, sort and pagination
- PHP form processing with server-side validation and CSV storage
- MySQL schema (courses, students, events, registrations, users, audit_logs, remember_tokens, password_resets)
- Secure user registration: duplicate email check and `password_hash()`
- Secure login and logout: sessions, `password_verify()`, session ID regeneration, role-based redirect, 15-minute timeout, remember-me token
- Student management CRUD with search, filters, pagination and delete confirmation
- Event management CRUD with poster upload (JPG, PNG or WEBP, maximum 2 MB)
- PHP JSON API (`api/events.php`) used by an AJAX/Fetch event manager with no page reload
- Admin dashboard with role-based menu and audit log

All database access uses MySQLi prepared statements.

## Technologies

HTML5, CSS3, Bootstrap 5, JavaScript (ES6+), PHP, MySQL, XAMPP, Git and GitHub.

## Folder structure

```
StudentHub/
├── index.html            Home page
├── pages/                Static pages (about, event, faq, contact, feedback, login, register...)
│                         and PHP pages (student_dashboard, admin_dashboard,
│                         student_management, manage_events, manage_events_ajax)
├── php/                  Form processors, login/logout, session checks, DB connection, audit log
├── api/                  JSON API (events.php)
├── css/                  Stylesheet
├── js/                   script.js (UI behaviour) and data-rendering.js (JSON rendering)
├── data/                 events.json, faqs.json, students.json, registrations.csv, schema.sql
├── img/                  Images and logo
├── uploads/events/       Uploaded event posters
└── docs/                 Sitemap and wireframes
```

## Setup (local, XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL** from the control panel.
2. Copy this project folder into `C:\xampp\htdocs\` (for example `C:\xampp\htdocs\StudentHub-main`).
3. Open `http://localhost/phpmyadmin`, click **Import**, choose `data/schema.sql` and run it. This creates the `studenthub` database with sample data.
4. Check the database settings in `php/db_connect_mysqli.php`. The defaults are host `localhost`, user `root`, empty password, database `studenthub`.
5. Open `http://localhost/StudentHub-main/index.html` in a browser.

## Login accounts

The `users` table holds the login accounts. Create or use accounts that exist in your own database.

| Role | Email | Password |
|---|---|---|
| Admin | _add admin email_ | _add password_ |
| Student | _add student email_ | _add password_ |

## Main pages

| Page | URL (after setup) |
|---|---|
| Home | `/StudentHub-main/index.html` |
| Register | `/StudentHub-main/pages/register_user.html` |
| Login | `/StudentHub-main/pages/login.html` |
| Student dashboard | `/StudentHub-main/pages/student_dashboard.php` |
| Admin dashboard | `/StudentHub-main/pages/admin_dashboard.php` |
| Manage students | `/StudentHub-main/pages/student_management.php` |
| Manage events | `/StudentHub-main/pages/manage_events.php` |
| AJAX events | `/StudentHub-main/pages/manage_events_ajax.php` |

## Status

Practicals 1 to 14 are implemented. Final integration, testing and deployment notes (Practical 15) will be added later.
