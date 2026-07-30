# Neurosatva MVP V3.2 🚀

**Neurosatva** is an enterprise-grade, two-portal PHP/MySQL web application designed for verified tutor class video management, interactive digital learning modules, and hardware-synchronized sensory experiences (ESP32 WLED lighting integration).

---

## 🌟 Key Features

### 🛡️ Admin Portal
- **Tutor Onboarding & Account Management**: Create and manage verified tutor accounts.
- **Registration Request Review**: Review, approve, or reject self-registered tutor applications with administrative remarks.
- **Class Video Verification**: Record and verify class video submissions received by email.
- **Digital Vault Management**: Upload, index, and organize interactive digital modules (`config.json`, `.mp4`, `.mp3`).
- **Module Assignment**: Assign modules to tutors with customizable hardware IP targets (ESP32 WLED), play count allowances, and expiration dates.

### 🎓 Tutor Portal & Digital Vault
- **Self-Service Registration & Login**: Secure account creation with optional personal email and mandatory admin approval.
- **Official Gmail & OTP Verification**: Bind Google Workspace email with time-limited OTP verification.
- **Interactive Digital Module Player**: Play assigned sensory modules with HTML5 video streaming and automatic background hardware synchronization.
- **Class Records & Instructions**: Access read-only verified class video archives and view email submission guidelines.

### 💡 Hardware Sensory Integration (ESP32 WLED)
- Real-time client-side timeline synchronization with ESP32 WLED LED controllers.
- Triggers predefined LED lighting effects, colors, and brightness dynamically as the video timeline progresses.

---

## 🛠️ Technology Stack

| Layer | Technology |
|---|---|
| **Frontend** | HTML5, CSS3 (Vanilla), JavaScript (ES6+ HTML5 Video API) |
| **Backend** | PHP 8.0+ (Custom MVC Architecture) |
| **Database** | MySQL 8.0+ (InnoDB, UTF8MB4, PDO Prepared Statements) |
| **Hardware Control** | ESP32 WLED Controller (HTTP / JSON / UDP Protocols) |
| **Security** | Session Fixation Protection, CSRF Tokens, Bcrypt Hashing, Range-based Media Server |

---

## 📚 Technical Documentation

Comprehensive system documentation is located in the [`neurosatva/`](neurosatva/) directory:

- 🏗️ **[Architecture.md](neurosatva/Architecture.md)**: High-level solution design, MVC patterns, data flow diagrams, and complete API specifications.
- 🗄️ **[Database.md](neurosatva/Database.md)**: ER diagrams, schema definitions, indexes, constraints, and data access query layers.
- 🚀 **[Phases.md](neurosatva/Phases.md)**: Development phase breakdown, completed milestones, and upcoming technical roadmap.
- 🔐 **[Security.md](neurosatva/Security.md)**: Authentication model, CSRF guards, SQLi/XSS mitigation, path traversal defenses, and audit logging.
- ⚠️ **[Error_Handling.md](neurosatva/Error_Handling.md)**: Global exception lifecycle, HTTP status code matrix, and client/hardware telemetry error reporting.

---

## ⚡ Quick Setup Guide

### 1. Prerequisites
- **PHP** >= 8.0 with PDO extension enabled
- **MySQL** >= 8.0 (or MariaDB equivalent)
- Web Server: Apache / Nginx / XAMPP

### 2. Database Initialization
Import the database schema and default seeds via MySQL CLI or phpMyAdmin:

```bash
mysql -u root -p neurosatva < neurosatva/database/schema.sql
mysql -u root -p neurosatva < neurosatva/database/seed.sql
mysql -u root -p neurosatva < neurosatva/database/migration_modules.sql
```

### 3. Environment Configuration
Copy `.env.example` to `.env` inside the `neurosatva/` directory and configure credentials:

```ini
APP_URL=http://localhost/neurosatva
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=neurosatva
DB_USER=root
DB_PASS=
ADMIN_VIDEO_EMAIL=videos@your-domain.com
```

### 4. Serve Application
Configure your web server to serve the `neurosatva/public` directory.

**Default Admin Credentials:**
- **Email**: `admin@neurosatva.local`
- **Password**: `password` *(Change immediately upon first login)*

---

## 🛣️ Core Application Routes

| Role | Route | Description |
|---|---|---|
| **Public** | `/admin/login` | Administrator authentication |
| **Public** | `/tutor/login` | Tutor authentication |
| **Public** | `/tutor/register` | Self-service tutor registration request |
| **Admin** | `/admin/dashboard` | Administrative overview dashboard |
| **Admin** | `/admin/registration-requests` | Tutor application review queue |
| **Admin** | `/admin/vault` | Digital module management & upload |
| **Admin** | `/admin/assign` | Module & ESP32 IP assignment interface |
| **Tutor** | `/tutor/dashboard` | Tutor portal home |
| **Tutor** | `/tutor/vault` | Assigned digital modules library |
| **Tutor** | `/tutor/vault/play` | Interactive module player with WLED sync |

---

## 🔒 Security Summary

- Passwords are encrypted using native PHP `password_hash` with Bcrypt.
- State-changing requests enforce CSRF tokens checked via timing-safe `hash_equals`.
- Media files in `storage/modules/` are protected from direct URL traversal and served exclusively via authenticated byte-range endpoints (`/storage-serve/modules`).
