# Neurosatva - Security Architecture & Hardening Specifications

## 1. Executive Summary

Security in **Neurosatva** is designed around a multi-layered defense-in-depth model. The application protects sensitive educational records, verified class videos, and proprietary interactive digital modules from unauthorized access, tampering, data exfiltration, and path traversal vulnerabilities.

---

## 2. Authentication & Session Security

### 2.1 Password Security & Hashing
- All user passwords (Admins, Tutors, and staged registration requests) are hashed using PHP's native `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt with cost factor 10+).
- Plaintext passwords are never logged, stored in memory beyond request scope, or transmitted in response payloads.

```php
// Password creation
$passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);

// Password verification
if (!password_verify($inputPassword, $user['password_hash'])) {
    // Authenticate failure logic
}
```

### 2.2 Session Management & Fixation Protection
- Sessions are initialized via `Session::start()` using strict security settings:
  - `httponly = true`: Prevents client-side JavaScript access (`document.cookie`) to session cookies, mitigating XSS session hijacking.
  - `samesite = Strict`: Mitigates Cross-Site Request Forgery (CSRF).
  - `use_strict_mode = 1`: Rejects uninitialized user-supplied session IDs.
- **Session Regeneration**: Upon successful login (`Auth::login()`), `session_regenerate_id(true)` is immediately invoked to prevent Session Fixation attacks.
- **Session Termination**: `Auth::logout()` clears `$_SESSION`, invalidates session cookies with past expiration timestamps, and destroys the server-side session payload.

### 2.3 Role-Based Access Control (RBAC)
- Enforced at both controller entry points and API router level.
- Routes are explicitly partitioned:
  - `/admin/*` requires `Auth::role() === 'admin'`.
  - `/tutor/*` requires `Auth::role() === 'tutor'`.
- Unauthenticated or unauthorized role attempts trigger immediate HTTP `401 Unauthorized` or redirect to `/admin/login` / `/tutor/login`.

---

## 3. Request Security & Input Validation

### 3.1 CSRF (Cross-Site Request Forgery) Guard
- Every state-altering request (`POST`, `PUT`, `DELETE`) requires a CSRF token generated via `Csrf::token()`.
- Tokens are produced using cryptographically secure pseudo-random bytes (`bin2hex(random_bytes(32))`).
- Validation via `Csrf::verify()` uses timing-safe string comparison (`hash_equals()`) to eliminate timing side-channel attacks:

```php
public static function verify(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid security token. Refresh the page and try again.');
    }
}
```

### 3.2 SQL Injection Prevention
- Direct SQL string concatenation is strictly forbidden.
- All database operations in `app/Models/*.php` utilize PDO prepared statements with parameter binding:

```php
$stmt = Database::pdo()->prepare(
    "SELECT * FROM tutors WHERE email = :email AND status = 'active' LIMIT 1"
);
$stmt->execute([':email' => $email]);
```

### 3.3 Cross-Site Scripting (XSS) Mitigation
- All user-supplied data rendered in views is sanitized using the global `e()` helper function:
```php
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
```

---

## 4. Storage & Path Traversal Safeguards

### 4.1 Storage Architecture & Indirect Media Delivery
- Digital Module assets (`/storage/modules/`) are placed outside public document root access where feasible or restricted via `.htaccess`.
- Assets are never served directly via static web URLs. Instead, requests route through `ApiController::serveModuleFile`.

### 4.2 Strict Path Traversal Defense
To prevent Directory Traversal (`../`) attacks targeting arbitrary system files (e.g. `/etc/passwd` or `.env`), `serveModuleFile` enforces three validation checkpoints:

1. **Parameter Sanitization**: Input strings pass through `basename()` to strip path separators.
2. **Canonical Resolution**: Canonicalized absolute path is resolved using `realpath()`.
3. **Prefix Guard**: The resolved path is verified to ensure it begins with the authorized base directory path:

```php
$folder = basename(input('folder') ?? ''); 
$file = basename(input('file') ?? '');

$basePath = dirname(__DIR__, 2) . '/storage/modules/';
$fullPath = realpath($basePath . $folder . '/' . $file);

if (!$fullPath || !str_starts_with($fullPath, realpath($basePath))) {
    http_response_code(403);
    exit('Forbidden');
}
```

### 4.3 Content Ownership Check
- For tutor roles, `serveModuleFile` queries `module_assignments` to verify that the authenticated tutor currently possesses an active assignment for the specific requested folder. Unassigned asset requests return `403 Forbidden`.

---

## 5. Official Gmail & OTP Verification Security

### 5.1 One-Time Password (OTP) Protection
- OTP codes are generated using random numeric digits.
- Plaintext OTPs are never stored in the database. Only the Bcrypt hash (`gmail_otp_hash`) is persisted.
- **Expiration Window**: OTPs expire after a short window (e.g. 10–15 minutes).
- **Brute-force Throttling**: The system increments `gmail_otp_attempts` on every failed attempt. Exceeding maximum allowed attempts invalidates the active OTP.

---

## 6. Audit Trail & Logging Compliance

Neurosatva maintains immutable audit trails across critical security events:
1. **`login_logs`**: Logs all successful and failed authentication attempts, storing role, user ID, email, IP address (`$_SERVER['REMOTE_ADDR']`), and User-Agent string.
2. **`admin_actions`**: Captures administrative operations (tutor approvals, account deletions, video verifications, module assignments) along with IP address and JSON metadata payloads.
3. **`playback_session_logs`**: Logs module playback executions, hardware IP targets, completion flags, and client error diagnostics.
