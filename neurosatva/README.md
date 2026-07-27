# Neurosatva

Neurosatva is a two-portal PHP/MySQL web application for verified tutor class video records.

## Stack

- Frontend: HTML, CSS, vanilla JavaScript
- Backend: PHP 8+
- Database: MySQL
- Auth: secure PHP sessions, CSRF protection, password hashing, role guards
- Storage: secure server path or cloud object URL recorded after admin verification

## Portals

- Admin Portal: create tutor IDs, manage tutors, record videos received by email, verify videos, assign verified videos tutor-wise.
- Tutor Portal: login only after admin creation or approval, read only their own verified videos, view email submission instructions.
- Tutor Registration: tutors can submit a registration request, but the account is created only after admin approval.

There is no student portal or direct tutor video upload flow.

## Setup

1. Open phpMyAdmin or MySQL CLI.
2. Import schema and seed:

```sql
SOURCE database/schema.sql;
SOURCE database/seed.sql;
```

For an existing Neurosatva database, import this migration instead of recreating the database:

```sql
SOURCE database/migration_registration_workflow.sql;
```

3. Copy `.env.example` to `.env` and update database credentials:

```ini
APP_URL=http://localhost/neurosatva
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=neurosatva
DB_USER=root
DB_PASS=
ADMIN_VIDEO_EMAIL=videos@your-domain.com
```

4. Serve the `public` directory from Apache/XAMPP.

Default admin seed:

- Email: `admin@neurosatva.local`
- Password: `password`

Change the seeded password immediately from Admin Settings after first login.

## Routes

Admin:

- `/admin/login`
- `/admin/dashboard`
- `/admin/tutors/create`
- `/admin/tutors`
- `/admin/registration-requests`
- `/admin/videos`
- `/admin/profile`

Tutor:

- `/tutor/login`
- `/tutor/register`
- `/tutor/dashboard`
- `/tutor/videos`
- `/tutor/instructions`
- `/tutor/profile`

## Security Notes

- Passwords use PHP `password_hash` / `password_verify`.
- All writes require CSRF tokens.
- SQL uses PDO prepared statements.
- Admin and tutor routes are protected by session role checks.
- Tutor video reads are filtered by the authenticated tutor ID and verified status.
- Credentials belong in `.env`, not source control.

For production, serve over HTTPS, move storage outside the public web root when using server storage, configure strict file permissions, and use short-lived signed cloud URLs for private videos.
