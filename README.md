# Question Paper Management System

A PHP + MySQL web application where **staff members upload previous year
question papers** (selecting the course, academic year, sub-course and subject)
and **students search and download** those papers. Every account is activated
only after the user's **email and mobile number are verified with an OTP**.

## Features

- **Two roles, clearly separated**
  - **Student** – browse question papers by Course → Sub-course → Subject and
    download them as PDF.
  - **Staff** – upload question papers by selecting Course, Academic Year,
    Sub-course and Subject; view, filter and manage papers.
- **Login page with role cards** that display and explain what each role can do.
- **Registration with role selection**, all credentials stored in the database
  (passwords hashed with `password_hash`).
- **Email verification** – OTP delivered over SMTP (PHPMailer) or PHP `mail()`.
- **Mobile verification** – OTP delivered over SMS (Textlocal) or demo mode.
- **Development mode** – when real mail/SMS are not configured, the OTP is shown
  on the verification screen so the whole flow can be demonstrated offline.
- Course / Sub-course / Subject data is maintained in the database and loaded
  with chained dropdowns (AJAX).

## Requirements

- PHP 7.4+ (PHP 8 recommended) with `pdo_mysql`, `fileinfo`, `mbstring`
- MySQL 5.7+ / MariaDB 10.3+
- A web server (Apache via XAMPP / WAMP, or PHP's built-in server)

## Installation (XAMPP)

1. Copy this folder into your web root, e.g. `C:\xampp\htdocs\questionpaper`.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Import the database:
   - Open `http://localhost/phpmyadmin`,
   - go to the **Import** tab,
   - choose `database.sql` and press **Go**
   (the script creates the `question_paper_db` database, its tables and seed data).
4. Open `http://localhost/questionpaper/` – you are ready.

> The `database.sql` file also inserts 3 **demo paper rows** and matching sample
> PDFs are already present in `uploads/`, so downloads work immediately.

## Configuration

All settings live in `config/config.php`:

| Setting | Purpose |
| --- | --- |
| `DB_HOST / DB_NAME / DB_USER / DB_PASS` | MySQL connection |
| `DEV_MODE` | `true` prints OTPs on the verification screen (ideal for demos). Set `false` in production. |
| `MAIL_*` | SMTP settings. Requires `vendor/autoload.php` (PHPMailer via Composer: `composer require phpmailer/phpmailer`) **and** `MAIL_USE_PHPMAILER = true`. Otherwise PHP's `mail()` is used. |
| `SMS_PROVIDER` | `demo` (show OTP on screen) or `textlocal` (real SMS via Textlocal API). |
| `SMS_SHOW_OTP_ON_SCREEN` | Keep `true` while testing so you can see the mobile OTP. |
| `OTP_TTL`, `OTP_RESEND_COOLDOWN` | OTP lifetime and resend limits. |

## Demo accounts

Run `http://localhost/questionpaper/seed_demo.php` once to create two
**pre-verified** accounts for quick testing, then **delete the file**:

| Role | Email | Password |
| --- | --- | --- |
| Staff | `staff@demo.com` | `Password@123` |
| Student | `student@demo.com` | `Password@123` |

To test the full OTP flow instead, register normally (leave `DEV_MODE = true`) —
the OTPs will be printed on the verification page.

## Project structure

```
questionpaper/
├── database.sql              # schema + seed data
├── config/config.php         # all settings
├── includes/                 # db, functions, mailer, sms, layout
├── staff/                    # staff dashboard, upload, manage
├── student/                  # student dashboard (browse & download)
├── uploads/                  # uploaded question paper PDFs
└── assets/                   # css/js
```

## Security notes

- Passwords are hashed with `bcrypt` (`password_hash`).
- All queries use PDO prepared statements.
- CSRF tokens protect every POST form.
- Only PDF files (validated by magic bytes) up to 10 MB can be uploaded.
- Downloads are only possible for logged-in, verified users.

## Troubleshooting

- **"Database connection failed"** – MySQL is not running or the database was
  not imported. Import `database.sql` and check `config/config.php`.
- **OTP not arriving** – enable `DEV_MODE` to read the OTP on the verification
  page, then configure real SMTP / Textlocal for production.
- **File too big** – `upload_max_filesize` and `post_max_size` in `php.ini`
  must be ≥ your `MAX_FILE_SIZE` (default 10 MB).