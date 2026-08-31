# Neurosatva - Project Delivery Phases & Evolution Roadmap

This document outlines the evolutionary development phases of the **Neurosatva** platform, detailing completed features, current capabilities, and future engineering milestones.

---

## Phase 1: Core Portal Architecture & Tutor Records (Completed)

### Core Objectives
Establish the primary two-portal architecture (Admin Portal & Tutor Portal), database schema, secure authentication framework, and administrative video tracking.

### Delivered Capabilities
- **Database Architecture**: Core schema implementation (`admins`, `tutors`, `videos`, `login_logs`, `admin_actions`).
- **Authentication & Security**:
  - Role-based session access guards (`Auth::requireRole`).
  - Bcrypt password hashing (`password_hash`, `password_verify`).
  - CSRF middleware validation across all state-mutating requests (`Csrf::verify`).
- **Admin Portal**:
  - Direct Tutor Account creation by Admin.
  - Video record management (recording class video submissions received by email).
  - Admin verification workflow (approving or rejecting recorded videos).
- **Tutor Portal**:
  - Restricted login interface for approved tutors.
  - Read-only video library displaying verified class records.
  - Email submission instructions display.

---

## Phase 2: Registration Requests & Official Gmail Verification (Completed)

### Core Objectives
Expand tutor onboarding from manual admin creation to a self-service registration workflow backed by email identity verification and admin approval gates.

### Delivered Capabilities
- **Public Registration Workflow**:
  - Self-service tutor registration form at `/tutor/register`.
  - Staging registration requests in `tutor_registration_requests` table in `Pending` status.
- **Admin Approval Interface**:
  - Dedicated request review dashboard (`/admin/registration-requests`).
  - One-click approval generating full Tutor credentials or rejection with custom remarks.
- **Official Gmail Binding & OTP Verification**:
  - Addition of `official_gmail`, `gmail_verified`, and `gmail_otp_hash` fields in database schema.
  - Onboarding step for Tutors to bind their official Google Workspace / Gmail address.
  - Time-limited One-Time Password (OTP) verification mechanism (`gmail_otp_expires_at`, max attempts guard).
- **Notification System**:
  - Real-time admin notification log (`admin_notifications`) for new registration requests and verification updates.

---

## Phase 3: Digital Vault & Interactive Hardware Integration (Completed)

### Core Objectives
Transform the platform into an interactive digital module delivery system with local hardware (ESP32 WLED LED strip) timeline synchronization and controlled playback permissions.

### Delivered Capabilities
- **Digital Module Vault (`/admin/vault`)**:
  - Zip upload & auto-indexing of structured module folders containing `config.json`, video (`.mp4`), and scene audio (`.mp3`).
  - JSON timeline parsing engine for multi-scene sensory experiences.
- **Granular Assignment & Play Control**:
  - Admin assignment interface (`/admin/assign`) mapping Modules to Tutors.
  - Target ESP32 hardware IP assignment per tutor setup.
  - Play count limits (`total_plays`, `remaining_plays`) and automatic expiry date enforcing.
- **Secure Media Streaming**:
  - Protected endpoint `/storage-serve/modules` checking active tutor permissions before serving content.
  - HTTP Range header support (`206 Partial Content`) for fluid HTML5 video scrubbing and streaming.
- **Browser-to-Hardware WLED Synchronization**:
  - HTML5 video time-update listener in `runtime.js`.
  - Real-time client-side HTTP/UDP commands to tutor's local ESP32 IP address triggering predefined light states, colors, and brightness.
- **Runtime Session Logging**:
  - Logging start/end timestamps, completion state, and runtime client errors in `playback_session_logs`.

---

## Phase 4: Production Scale & Enterprise Enhancements (Upcoming Roadmap)

### Milestone 4.1: Cloud Object Storage & CDN Migration
- **Objective**: Offload local media storage (`storage/modules/`) to cloud storage.
- **Scope**:
  - AWS S3 or Cloudflare R2 integration for video and audio asset hosting.
  - Generate short-lived signed URLs in `ApiController::tutorModule` to eliminate server bandwidth bottlenecks.

### Milestone 4.2: Real-time Hardware WebSockets & Bidirectional Telemetry
- **Objective**: Establish two-way communication with classroom ESP32 hardware.
- **Scope**:
  - Replace client-side HTTP polling with WebSocket connection (`ws://` or WLED WebSockets).
  - Receive real-time telemetry from ESP32 (connection state, strip health, power drop alerts).

### Milestone 4.3: Automated Email & OTP Mailer Integration
- **Objective**: Automate transactional email delivery.
- **Scope**:
  - PHPMailer / Amazon SES / SendGrid integration.
  - Automated dispatch of Gmail OTP verification codes.
  - Email alerts to tutors when new modules or videos are assigned.

### Milestone 4.4: Advanced Analytics & Analytics Dashboard
- **Objective**: Detailed insights into tutor engagement and student sensory completion rates.
- **Scope**:
  - Admin dashboard widgets showing module usage frequency, completion rates, and active classroom hardware status.
  - Automated monthly compliance reporting for management export.
