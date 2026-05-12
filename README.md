# HRMS – HR Management System
## PHP + MySQL | Full-Stack Web Application

---

## 🚀 Quick Start (5 minutes)

### Option A – Auto Setup (Recommended)
1. Copy the `hrms/` folder into your web server root (e.g. `htdocs/` for XAMPP, `www/` for WAMP)
2. Start Apache + MySQL
3. Open browser: `http://localhost/hrms/setup.php`
4. Enter your DB credentials and click **Install**
5. Login at `http://localhost/hrms/` with `admin@hrms.com` / `password`

### Option B – Manual Setup
1. Create a MySQL database named `hrms`
2. Import `config/database.sql` into it
3. Edit `config/db.php` — set `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
4. Open `http://localhost/hrms/`

---

## 👤 Default Login

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@hrms.com | password |

> After login, go to **Employees → Add Employee** to create other users.

---

## 📁 Project Structure

```
hrms/
├── index.php          # Login page
├── logout.php         # Logout + auto clock-out
├── profile.php        # User profile (all roles)
├── setup.php          # One-click installer
├── cron.php           # Scheduled tasks (run daily)
│
├── config/
│   ├── db.php         # Database connection
│   └── database.sql   # Full schema + seed data
│
├── includes/
│   ├── auth.php       # Session, helpers, notifications
│   ├── header.php     # Sidebar + topbar
│   ├── footer.php     # Scripts
│   └── mark_read.php  # Mark notifications read
│
├── admin/
│   ├── dashboard.php  # Admin overview + charts
│   ├── employees.php  # CRUD employees
│   ├── tasks.php      # Assign & manage tasks
│   ├── attendance.php # View & modify attendance (with log)
│   ├── leaves.php     # Approve/reject leave requests
│   ├── announcements.php  # Post announcements
│   ├── reports.php    # Generate + export CSV/PDF reports
│   └── settings.php   # Company settings + logo upload
│
├── hr/
│   ├── dashboard.php
│   ├── leaves.php
│   ├── attendance.php
│   ├── reports.php
│   └── my_leaves.php
│
├── employee/
│   ├── dashboard.php  # Clock widget, stats, tasks
│   ├── tasks.php      # View tasks, complete/delay
│   ├── attendance.php # Personal attendance history
│   ├── leaves.php     # Request + view leaves
│   └── announcements.php
│
├── executive/
│   ├── dashboard.php
│   └── attendance.php
│
└── uploads/
    └── logo/          # Company logo uploads
```

---

## ✅ Features Implemented

### Authentication
- Login/logout with role-based redirect
- Secure session handling
- Role-based access: Admin, HR, Executive, Employee

### Attendance
- Auto clock-in on login, clock-out on logout
- IP/location capture on clock-in
- Late detection (after 08:30)
- Break start/end registration (max 1hr, before 15:00)
- Admin can modify attendance with reason (logged with original & new times)

### Leave Management
- 22 Local + 15 Sick leaves per year
- Auto-switch to unpaid when both are exhausted
- HR/Admin approve or reject with email-ready notifications
- Annual reset (via cron.php on Jan 1)
- Leave days marked in attendance automatically on approval

### Task Management
- Admin assigns tasks with priority & due date
- Employee can Complete or Delay (with mandatory reason)
- Notifications sent to admin on completion/delay
- Notifications sent to employee on assignment

### Reports
- Attendance & Task reports with date + employee filters
- Pagination
- Export as **CSV** and **PDF** (print-ready)
- Monthly auto-archiving (via cron.php on 1st of month)

### Notifications
- In-app notification bell with unread count
- Notifications for: leave requests, task events, account creation

### Admin Settings
- Company name
- Work hours (start/end time)
- Break max duration & deadline
- Annual leave quotas
- **Company logo upload** (replaces sidebar logo)
- Reset all employee leave balances

---

## ⏰ Cron Job Setup

Run `cron.php` daily (recommended: midnight):

**Linux/Mac:**
```bash
crontab -e
# Add:
0 0 * * * php /var/www/html/hrms/cron.php >> /var/log/hrms_cron.log 2>&1
```

**Windows (Task Scheduler):**
- Program: `php`
- Arguments: `C:\xampp\htdocs\hrms\cron.php`
- Schedule: Daily at midnight

---

## 🔧 Requirements
- PHP 7.4+ (8.x recommended)
- MySQL 5.7+ / MariaDB 10.3+
- Apache/Nginx with mod_rewrite (optional)
- XAMPP / WAMP / LAMP stack works perfectly
