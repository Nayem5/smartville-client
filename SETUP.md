# SmartVille Community Hub — Setup Guide

## Requirements
- XAMPP (with Apache + MySQL) — download from https://www.apachefriends.org

---

## Step-by-Step Setup

### 1. Place Files
The project folder must be located at:
```
C:\xampp\htdocs\fyp_soop\smartville
```

### 2. Start XAMPP
- Open XAMPP Control Panel
- Click **Start** on **Apache**
- Click **Start** on **MySQL**

### 3. Import Database
- Open browser → go to: http://localhost/phpmyadmin
- Click **New** → create database named: `smartville_db`
- Click the `smartville_db` database
- Click **Import** tab
- Choose file: `C:\xampp\htdocs\fyp_soop\smartville\database.sql`
- Click **Go**

### 4. Open the Website
Go to: http://localhost/fyp_soop/smartville/index.html

From the landing page, use the **Log In** or **Sign Up** buttons to navigate.

---

## Demo Login Credentials

| Role       | Username  | Password |
|------------|-----------|----------|
| Admin      | admin     | password |
| Organizer  | shahidah  | password |
| Resident   | ahmad     | password |

> On the login page, select the correct **role tab** (Admin / Organizer / Resident) before clicking Log In, or click the demo account buttons to auto-fill everything.

---

## Features by Role

### Admin
- Dashboard with stats & pending events
- Approve / Reject events with notes
- Manage users (promote, deactivate, delete)
- View all registrations

### Organizer
- Dashboard with personal event stats
- Create events (Free / Paid / Private) with 4-step form
- Upload event poster
- Choose venue
- View registrations and ratings per event
- Cancel events

### Resident
- Dashboard with personalized event feed
- Browse & search all approved events
- Filter by Free / Paid / Private
- Join events (instant for free, payment form for paid, invitation check for private)
- View event details, program flow
- Leave star ratings + comments after events
- View all registered events

---

## File Structure
```
fyp_soop/smartville/
├── index.html              ← Landing page
├── login.php               ← Login (PHP + MySQL)
├── signup.php              ← Registration (PHP)
├── logout.php              ← Clears session and redirects to login
├── profile.php             ← Edit profile + change password
├── mark_read.php           ← Mark notifications as read
├── auth.css                ← Styles for login/signup pages
├── auth.js                 ← JS for login/signup pages
├── dashboard.css           ← Shared dashboard styles
├── style.css               ← Landing page styles
├── script.js               ← Landing page scripts
├── database.sql            ← Import this into phpMyAdmin
├── includes/
│   ├── db.php              ← Database connection + BASE_PATH constant
│   ├── functions.php       ← Helper functions (redirect, auth, notifications)
│   ├── header.php          ← Dashboard sidebar + topbar (included in all role pages)
│   └── footer.php          ← Dashboard footer + scripts
├── admin/
│   ├── index.php           ← Admin dashboard
│   ├── events.php          ← Approve/reject events
│   └── users.php           ← Manage users
├── organizer/
│   ├── index.php           ← Organizer dashboard
│   ├── create_event.php    ← Create event (4-step form)
│   └── my_events.php       ← View and manage own events
├── resident/
│   ├── index.php           ← Resident dashboard
│   ├── browse.php          ← Browse all approved events
│   ├── event_detail.php    ← Event detail, join, and feedback
│   └── my_events.php       ← View registered events
└── uploads/
    └── posters/            ← Uploaded event poster images
```

---

## Important Notes

- **BASE_PATH** is defined in `includes/db.php` as `/fyp_soop/smartville`. If you rename or move the project folder, update this value to match.
- All role dashboards require login. Accessing them directly without a session will redirect to the login page.
- The `uploads/posters/` folder must be writable by Apache for event poster uploads to work.
