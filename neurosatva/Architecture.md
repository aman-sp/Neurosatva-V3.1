# Neurosatva - System Architecture & Solution Design

## 1. Overview & Solution Design

**Neurosatva** is a dual-portal PHP/MySQL enterprise web application designed for verified tutor class video playback, interactive learning digital modules, and hardware-synchronized sensory experiences (ESP32 WLED lighting integration).

The platform enforces strict administrative control, verified identity tracking, and access-controlled streaming playback for tutors. Tutors cannot directly publish content; all content is created, verified, and assigned by System Administrators.

### Architectural Pattern
Neurosatva follows a custom lightweight Model-View-Controller (MVC) architecture without heavy external dependencies.

```
                  +-----------------------------------+
                  |            Browser Client         |
                  |  (Admin Dashboard / Tutor Vault)  |
                  +-----------------+-----------------+
                                    |
                                    | HTTP / HTTPS Requests
                                    v
+-------------------------------------------------------------------+
|                        Public Web Root                            |
|                          public/index.php                         |
+-----------------------------------+-------------------------------+
                                    |
            +-----------------------+-----------------------+
            |                       |                       |
            v                       v                       v
    +---------------+       +---------------+       +---------------+
    |  Router Core  |       | Auth & Session|       |  CSRF Guard   |
    +-------+-------+       +-------+-------+       +-------+-------+
            |                       |                       |
            +-----------------------+-----------------------+
                                    |
                                    v
                  +-----------------------------------+
                  |            Controllers            |
                  | Admin / Tutor / Auth / Module / Api|
                  +-----------------+-----------------+
                                    |
                  +-----------------+-----------------+
                  |              Models               |
                  | Module, Assignment, Tutor, Video |
                  +-----------------+-----------------+
                                    |
            +-----------------------+-----------------------+
            |                                               |
            v                                               v
+-----------------------+                       +-----------------------+
|  MySQL Database (PDO) |                       | Storage / Media Engine|
| Tables: users, modules|                       | /storage/modules/     |
+-----------------------+                       +-----------+-----------+
                                                            |
                                                            | HTTP WLED Commands
                                                            v
                                                +-----------------------+
                                                |   ESP32 LED Hardware  |
                                                |    (Local Network)    |
                                                +-----------------------+
```

### Core Architecture Components
1. **Core Layer (`app/Core/`)**:
   - `Router.php`: Lightweight URI dispatcher matching `GET` and `POST` routes to Controller methods.
   - `Auth.php`: Session-based authentication manager maintaining user credentials, roles (`admin` or `tutor`), and permission guards (`requireRole`).
   - `Csrf.php`: Cryptographic token generation (`bin2hex(random_bytes(32))`) and timing-safe validation (`hash_equals`).
   - `Database.php`: Singleton PDO wrapper configuring `utf8mb4` character set and strict error modes.
   - `Env.php`: Custom `.env` environment configuration parser.
   - `Session.php`: Secure session initiator setting HTTP-only cookie parameters and session renewal rules.

2. **Controller Layer (`app/Controllers/`)**:
   - `AuthController.php`: Handles Admin & Tutor login, Tutor registration, and session termination.
   - `AdminController.php`: Admin operations: tutor management, registration request reviews, video records, profile updates.
   - `ModuleController.php`: Admin module management: uploading/editing digital modules, JSON timeline configs, and tutor assignments.
   - `TutorController.php`: Tutor user interactions: viewing assignments, submitting video evidence links, Gmail verification setup.
   - `TutorVaultController.php`: Tutor Digital Vault player rendering, loading module JSON configs, and playing synchronized media.
   - `ApiController.php`: Authenticated REST API endpoints for module catalog querying, session logging, and secure media file streaming.

3. **Model Layer (`app/Models/`)**:
   - Encapsulates database queries using prepared statements.
   - Models: `Admin`, `Tutor`, `TutorRegistrationRequest`, `Video`, `Module`, `ModuleAssignment`, `SessionLog`, `AdminNotification`, `AuditLog`.

4. **Storage & Media Engine (`storage/modules/`)**:
   - Houses video assets (`.mp4`), audio track files (`.mp3`), thumbnails, and module configuration files (`config.json`).
   - Protected from direct HTTP traversal. Files are served exclusively through `ApiController::serveModuleFile` with HTTP Byte-Range support (`206 Partial Content`).

5. **Hardware Integration (ESP32 WLED)**:
   - Synchronizes light effects with video timelines.
   - The browser player parses `config.json` timelines and triggers JSON/UDP API requests directly to the tutor's local ESP32 IP address during playback.

---

## 2. Data Flow Architecture

### 2.1 User Authentication & Access Control Flow
```
User (Admin / Tutor)                 Router / Auth Core                     Database
        |                                     |                                 |
        |--- 1. POST /admin/login ----------->|                                 |
        |       (email, password, _csrf)      |--- 2. Fetch User Record ------->|
        |                                     |<-- 3. Return User Data ---------|
        |                                     |                                 |
        |                                     |--- 4. Verify password_verify()  |
        |                                     |--- 5. Session::regenerate()     |
        |                                     |--- 6. Log in login_logs -------->|
        |<-- 7. Redirect to /admin/dashboard -|                                 |
```

### 2.2 Digital Module Upload & Deployment Flow
```
Admin                             ModuleController / Storage                Database
  |                                           |                                 |
  |--- 1. POST /admin/vault (Files & Meta) ->|                                 |
  |                                           |--- 2. Validate zip/folder ------>|
  |                                           |--- 3. Extract to storage/modules|
  |                                           |--- 4. Validate config.json ---->|
  |                                           |--- 5. Insert module record ---->|
  |                                           |--- 6. Log admin action -------->|
  |<-- 7. Redirect to /admin/vault ----------|                                 |
```

### 2.3 Interactive Module Playback & Hardware Sync Flow
```
Tutor Browser               ApiController                 Storage / File System        ESP32 Controller
      |                           |                                 |                         |
      |-- 1. GET /tutor/vault/play|                                 |                         |
      |   (assignment_id)         |                                 |                         |
      |                           |                                 |                         |
      |-- 2. GET /api/tutor/module|                                 |                         |
      |------------->             |-- 3. Validate assignment ------>|                         |
      |                           |-- 4. Parse config.json -------->|                         |
      |<-------------             |-- 5. Return JSON with signed URL|                         |
      |                           |                                 |                         |
      |-- 6. POST /api/runtime/start ------------------------------>|                         |
      |<-- 7. Return log_id ----------------------------------------|                         |
      |                                                             |                         |
      |-- 8. GET /storage-serve/modules?folder=X&file=Y ---------->|                         |
      |   (Range: bytes=0-)       |<-- 9. Stream media (206) -------|                         |
      |                                                                                       |
      |-- 10. Timeline Scene Event (Trigger Light Preset) ----------------------------------->|
      |    (HTTP POST http://<ESP32_IP>/json/state)                                           |
      |                                                                                       |
      |-- 11. POST /api/runtime/end (log_id, completed) --------->|                         |
      |                           |-- 12. Decrement play count ---->|                         |
      |<-- 13. Return success ----|                                 |                         |
```

---

## 3. API Reference

All API requests expecting JSON responses return standard HTTP status codes (`200`, `401`, `403`, `404`, `422`, `500`).

### 3.1 Module Management APIs

#### 1. Fetch All Modules (Admin)
- **Endpoint**: `GET /api/modules`
- **Auth**: Required (`admin` role)
- **Query Parameters**:
  - `search` *(optional, string)*: Filter by module name or folder name.
  - `status` *(optional, string)*: `active` | `archived`
- **Response `200 OK`**:
```json
[
  {
    "id": 1,
    "name": "Sensory Module 01",
    "folder_name": "sensory_mod_01",
    "description": "Interactive tactile and visual simulation",
    "video_name": "main.mp4",
    "thumbnail": "thumb.jpg",
    "config_path": "storage/modules/sensory_mod_01/config.json",
    "version": 1,
    "status": "active",
    "created_at": "2026-07-30 09:00:00"
  }
]
```

#### 2. Fetch Assigned Modules (Tutor)
- **Endpoint**: `GET /api/tutor/modules`
- **Auth**: Required (`tutor` role)
- **Response `200 OK`**:
```json
[
  {
    "assignment_id": 5,
    "module_id": 1,
    "name": "Sensory Module 01",
    "folder_name": "sensory_mod_01",
    "esp32_ip": "192.168.1.105",
    "remaining_plays": 3,
    "total_plays": 5,
    "expiry_date": "2026-12-31",
    "status": "active",
    "is_playable": true
  }
]
```

#### 3. Fetch Module Configuration & Media URLs
- **Endpoint**: `GET /api/tutor/module`
- **Auth**: Required (`tutor` role)
- **Query Parameters**:
  - `id` *(required, int)*: Assignment ID
- **Response `200 OK`**:
```json
{
  "name": "Sensory Module 01",
  "version": 1,
  "video": "main.mp4",
  "_video_url": "http://localhost/neurosatva/storage-serve/modules?folder=sensory_mod_01&file=main.mp4",
  "_esp32_ip": "192.168.1.105",
  "_assignment_id": 5,
  "timeline": [
    {
      "start": 0,
      "end": 15,
      "light_effect": { "preset": 1, "bri": 255 },
      "audio": "intro.mp3",
      "_audio_url": "http://localhost/neurosatva/storage-serve/modules?folder=sensory_mod_01&file=intro.mp3"
    }
  ]
}
```

---

### 3.2 Runtime & Session Logging APIs

#### 1. Start Playback Session
- **Endpoint**: `POST /api/runtime/start`
- **Auth**: Required (`tutor` role)
- **Payload (`application/x-www-form-urlencoded`)**:
  - `assignment_id` *(required, int)*
  - `device_ip` *(optional, string)*
- **Response `200 OK`**:
```json
{
  "success": true,
  "log_id": 42
}
```

#### 2. End Playback Session
- **Endpoint**: `POST /api/runtime/end`
- **Auth**: Required (`tutor` role)
- **Payload (`application/x-www-form-urlencoded`)**:
  - `log_id` *(required, int)*
  - `assignment_id` *(required, int)*
  - `completed` *(optional, int/boolean)*: `1` or `0`
  - `error` *(optional, string)*: Error diagnostics if playback crashed
- **Response `200 OK`**:
```json
{
  "success": true
}
```

---

### 3.3 Hardware Diagnostic Test API

#### Test Module Hardware IP Configuration (Admin)
- **Endpoint**: `POST /api/modules/test`
- **Auth**: Required (`admin` role)
- **Payload**:
  - `module_id` *(required, int)*
  - `esp32_ip` *(required, string)*: e.g. `192.168.1.105`
- **Response `200 OK`**:
```json
{
  "success": true,
  "config": {
    "name": "Sensory Module 01",
    "_video_url": "http://localhost/neurosatva/storage-serve/modules?folder=sensory_mod_01&file=main.mp4",
    "_esp32_ip": "192.168.1.105",
    "_module_id": 1,
    "_test_mode": true
  }
}
```

---

### 3.4 Media Streaming Endpoint

#### Stream Protected Module Asset
- **Endpoint**: `GET /storage-serve/modules`
- **Auth**: Required (Valid Session)
- **Query Parameters**:
  - `folder` *(required, string)*: Module folder name
  - `file` *(required, string)*: Asset filename (`.mp4`, `.mp3`, `.png`, `.jpg`)
- **Headers Supported**: `Range: bytes=start-end`
- **Response**: `206 Partial Content` (for byte range requests) or `200 OK`
- **Content Types**: `video/mp4`, `audio/mpeg`, `application/json`, `image/png`, `image/jpeg`
