# Neurosatva - Database Architecture & Schema Specification

## 1. Overview & Solution Design

The **Neurosatva** database layer is built on **MySQL (v8.0+)** utilizing the standard **InnoDB** storage engine with full support for ACID transactions, foreign key constraints, and UTF-8 multi-byte encoding (`utf8mb4_unicode_ci`).

### Architectural Highlights
- **Relational Integrity**: Strict foreign key constraints with explicit `ON DELETE CASCADE` (for child entities like assignments and session logs) and `ON DELETE SET NULL` (for audit trails referencing admin accounts).
- **Security & Masking**: Sensitive fields such as passwords (`password_hash`) and OTPs (`gmail_otp_hash`) store non-reversible cryptographic hashes (Bcrypt).
- **Index Optimization**: Explicit multi-column and single-column indexes on high-frequency query paths (`status`, `tutor_id`, `email`, `read_at`).
- **Data Access Pattern**: Access to the database is managed via PHP PDO prepared statements inside Model classes (`app/Models/*.php`).

---

## 2. Entity Relationship & Data Flow Diagram

```
 +--------------------+               +-------------------------------+
 |       admins       |               |  tutor_registration_requests  |
 +--------------------+               +-------------------------------+
 | id (PK)            |<----+         | id (PK)                       |
 | email              |     |         | email                         |
 | password_hash      |     |         | status (Pending/Appr/Rej)     |
 +---------+----------+     |         | approved_by (FK -> admins.id) |
           |                |         +-------------------------------+
           |                |
           | created_by     | approved_by
           v                |
 +--------------------+-----+         +-------------------------------+
 |       tutors       |               |            videos             |
 +--------------------+               +-------------------------------+
 | id (PK)            |<------------->| id (PK)                       |
 | email              |   tutor_id    | tutor_id (FK -> tutors.id)    |
 | official_gmail     |               | status (pending/verified/rej) |
 | gmail_verified     |               +---------------+---------------+
 +---------+----------+                               |
           |                                          | video_id
           | tutor_id                                 v
           |                          +-------------------------------+
           |                          |      video_verifications      |
           |                          +-------------------------------+
           |                          | id (PK)                       |
           |                          | video_id (FK -> videos.id)    |
           |                          | admin_id (FK -> admins.id)    |
           |                          +-------------------------------+
           |
           +----------------------------------+
           |                                  |
           v                                  v
 +--------------------+            +--------------------+
 |      modules       |            | module_assignments |
 +--------------------+            +--------------------+
 | id (PK)            |<-----------| id (PK)            |
 | name               | module_id  | tutor_id (FK)      |
 | folder_name (UNIQ) |            | module_id (FK)     |
 | config_path        |            | remaining_plays    |
 +--------------------+            | status (active)    |
                                   +---------+----------+
                                             |
                                             | assignment_id
                                             v
                                   +--------------------+
                                   |playback_session_log|
                                   +--------------------+
                                   | id (PK)            |
                                   | assignment_id (FK) |
                                   | completed (0/1)    |
                                   | error_log          |
                                   +--------------------+
```

---

## 3. Detailed Schema Specifications

### 3.1 `admins`
Stores system administrator credentials and access profiles.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | `AUTO_INCREMENT`, `PRIMARY KEY` | Unique Admin ID |
| `name` | `VARCHAR(120)` | `NOT NULL` | Admin full name |
| `email` | `VARCHAR(190)` | `NOT NULL`, `UNIQUE` | Admin login email |
| `phone` | `VARCHAR(20)` | `NULL` | Contact phone number |
| `password_hash` | `VARCHAR(255)` | `NOT NULL` | Bcrypt password hash |
| `status` | `ENUM('active', 'deactivated')` | `NOT NULL`, `DEFAULT 'active'` | Account status |
| `created_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | Creation record |
| `updated_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP ON UPDATE` | Modification record |

---

### 3.2 `tutors`
Stores verified tutor account records and official Gmail verification state.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | `AUTO_INCREMENT`, `PRIMARY KEY` | Unique Tutor ID |
| `name` | `VARCHAR(120)` | `NOT NULL` | Tutor full name |
| `email` | `VARCHAR(190)` | `NOT NULL`, `UNIQUE` | Primary system login email |
| `personal_email` | `VARCHAR(190)` | `NULL` | Optional personal contact email |
| `password_hash` | `VARCHAR(255)` | `NOT NULL` | Bcrypt password hash |
| `status` | `ENUM('active', 'deactivated')` | `NOT NULL`, `DEFAULT 'active'` | Account state |
| `created_by` | `BIGINT UNSIGNED` | `NULL`, `FK -> admins(id)` | Admin creator reference |
| `school_name` | `VARCHAR(160)` | `NULL` | Associated educational institution |
| `gender` | `VARCHAR(40)` | `NULL` | Gender designation |
| `official_gmail` | `VARCHAR(190)` | `NULL` | Verified Google Workspace address |
| `gmail_verified` | `TINYINT(1)` | `NOT NULL`, `DEFAULT 0` | Verification flag (`1` = verified) |
| `gmail_verified_at`| `TIMESTAMP` | `NULL` | Timestamp of OTP verification |
| `gmail_verification_token` | `VARCHAR(128)` | `NULL` | Unique email token |
| `gmail_otp_hash`| `VARCHAR(255)` | `NULL` | Bcrypt hash of active 6-digit OTP |
| `gmail_otp_expires_at` | `TIMESTAMP` | `NULL` | Expiration time of active OTP |
| `gmail_otp_attempts` | `INT` | `NOT NULL`, `DEFAULT 0` | Failed OTP attempt counter |
| `first_login_completed` | `TINYINT(1)` | `NOT NULL`, `DEFAULT 0` | Onboarding complete flag |
| `created_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | Creation timestamp |
| `updated_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP ON UPDATE` | Update timestamp |

**Indexes**:
- `idx_tutors_status`: `(status)`
- `idx_tutors_official_gmail`: `(official_gmail)`

---

### 3.3 `tutor_registration_requests`
Holds self-registered tutor applications awaiting administrative approval.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | `AUTO_INCREMENT`, `PRIMARY KEY` | Request ID |
| `full_name` | `VARCHAR(120)` | `NOT NULL` | Applicant name |
| `email` | `VARCHAR(190)` | `NOT NULL` | Applicant email |
| `phone` | `VARCHAR(20)` | `NOT NULL` | Contact number |
| `school_name` | `VARCHAR(160)` | `NULL` | Institution name |
| `gender` | `VARCHAR(40)` | `NULL` | Gender |
| `password_hash` | `VARCHAR(255)` | `NOT NULL` | Staged password hash |
| `status` | `ENUM('Pending', 'Approved', 'Rejected')` | `DEFAULT 'Pending'` | Workflow stage |
| `admin_remarks` | `TEXT` | `NULL` | Rejection or approval notes |
| `approved_by` | `BIGINT UNSIGNED` | `NULL`, `FK -> admins(id)` | Reviewing admin |
| `approved_at` | `TIMESTAMP` | `NULL` | Review timestamp |

**Indexes**:
- `idx_tutor_requests_status`: `(status)`
- `idx_tutor_requests_email`: `(email)`

---

### 3.4 `videos` & `video_verifications`
Tracks raw class recording records submitted via email and verified by administrators.

#### `videos`
- `id` (PK), `tutor_id` (FK -> tutors.id ON DELETE CASCADE), `title`, `email_subject`, `source_email`, `storage_path`, `status` (`pending`, `verified`, `rejected`), `admin_remarks`, `received_at`, `verified_at`.
- **Indexes**: `idx_videos_tutor_status` `(tutor_id, status)`, `idx_videos_received_at` `(received_at)`.

#### `video_verifications`
- `id` (PK), `video_id` (FK -> videos.id ON DELETE CASCADE), `admin_id` (FK -> admins.id ON DELETE SET NULL), `status`, `remarks`, `created_at`.

---

### 3.5 `modules`
Metadata catalog of interactive digital modules.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | `AUTO_INCREMENT`, `PRIMARY KEY` | Module ID |
| `name` | `VARCHAR(200)` | `NOT NULL` | Human-readable title |
| `folder_name` | `VARCHAR(200)` | `NOT NULL`, `UNIQUE` | Physical directory under `storage/modules/` |
| `description` | `TEXT` | `NULL` | Course/sensory synopsis |
| `video_name` | `VARCHAR(255)` | `NULL` | Primary video filename (e.g. `main.mp4`) |
| `thumbnail` | `VARCHAR(255)` | `NULL` | Thumbnail file name |
| `config_path` | `VARCHAR(500)` | `NOT NULL` | Relative path to `config.json` |
| `version` | `INT UNSIGNED` | `DEFAULT 1` | Module schema version |
| `status` | `ENUM('active', 'archived')` | `DEFAULT 'active'` | Publication state |
| `created_by` | `BIGINT UNSIGNED` | `NULL`, `FK -> admins(id)` | Author admin |

**Indexes**: `idx_modules_status` `(status)`, `idx_modules_folder` `(folder_name)`.

---

### 3.6 `module_assignments`
Maps digital modules to authorized tutors with hardware IP configs and quota limits.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | `AUTO_INCREMENT`, `PRIMARY KEY` | Assignment ID |
| `tutor_id` | `BIGINT UNSIGNED` | `NOT NULL`, `FK -> tutors(id)` | Target tutor |
| `module_id` | `BIGINT UNSIGNED` | `NOT NULL`, `FK -> modules(id)` | Target module |
| `esp32_ip` | `VARCHAR(45)` | `NOT NULL` | Tutor's local classroom WLED ESP32 IP |
| `remaining_plays` | `INT` | `NOT NULL`, `DEFAULT 1` | Playback allowance remaining |
| `total_plays` | `INT` | `NOT NULL`, `DEFAULT 1` | Total granted plays |
| `expiry_date` | `DATE` | `NULL` | Cutoff date for module execution |
| `status` | `ENUM('active', 'expired', 'revoked')` | `DEFAULT 'active'` | Permission state |
| `assigned_by` | `BIGINT UNSIGNED` | `NULL`, `FK -> admins(id)` | Assigning admin |

**Indexes**: `idx_assignments_tutor` `(tutor_id)`, `idx_assignments_module` `(module_id)`, `idx_assignments_status` `(status)`.

---

### 3.7 `playback_session_logs`
Runtime audit log recording every playback execution.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | `AUTO_INCREMENT`, `PRIMARY KEY` | Log ID |
| `assignment_id` | `BIGINT UNSIGNED` | `NOT NULL`, `FK -> module_assignments(id)` | Parent assignment |
| `tutor_id` | `BIGINT UNSIGNED` | `NOT NULL` | Executing tutor |
| `module_id` | `BIGINT UNSIGNED` | `NOT NULL` | Module executed |
| `device_ip` | `VARCHAR(45)` | `NULL` | Client device IP |
| `started_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | Session start time |
| `ended_at` | `TIMESTAMP` | `NULL` | Session end time |
| `duration_seconds` | `INT` | `NULL` | Total runtime in seconds |
| `completed` | `TINYINT(1)` | `DEFAULT 0` | `1` if completed without error |
| `error_log` | `TEXT` | `NULL` | Error messages captured during playback |

---

### 3.8 Logging & Audit Tables (`login_logs`, `admin_actions`, `admin_notifications`)

- `login_logs`: `id` (PK), `role` (`admin`/`tutor`), `user_id`, `email`, `ip_address`, `user_agent`, `success` (0/1), `created_at`.
- `admin_actions`: `id` (PK), `admin_id` (FK -> admins.id), `action`, `entity_type`, `entity_id`, `metadata` (`JSON`), `ip_address`, `created_at`.
- `admin_notifications`: `id` (PK), `title`, `message`, `type`, `entity_type`, `entity_id`, `link`, `read_at`, `created_at`.

---

## 4. Database Operations & Model API

All database interactions use PDO prepared statements to enforce total isolation against SQL Injection attacks.

### Primary Model Query API

```php
// app/Models/ModuleAssignment.php
class ModuleAssignment
{
    // Find active assignment owned by tutor
    public static function findForTutor(int $id, int $tutorId): ?array;

    // Check playability (status === 'active' AND remaining_plays > 0 AND NOT expired)
    public static function isPlayable(array $assignment): bool;

    // Atomically decrement remaining plays
    public static function decrementPlays(int $id): bool;
}
```
