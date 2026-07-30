# Neurosatva - Error Handling & Exception Management Specification

## 1. Overview & Strategy

The **Neurosatva** error handling architecture is designed to maintain system stability, prevent sensitive stack trace leaks in production environments, provide clear diagnostics during development, and gracefully log runtime hardware and client-side playback failures.

---

## 2. Global Exception Lifecycle

All HTTP requests enter through `public/index.php` and are wrapped in a global exception handler.

```
                  +-----------------------------------+
                  |           Incoming Request        |
                  +-----------------+-----------------+
                                    |
                                    v
                  +-----------------------------------+
                  |         Router::dispatch()        |
                  +-----------------+-----------------+
                                    |
              +---------------------+---------------------+
              |                                           |
              v                                           v
    [ PDOException Caught ]                     [ Throwable Caught ]
              |                                           |
              v                                           v
  Set HTTP 500 Response                       Set HTTP 500 Response
              |                                           |
              v                                           v
Render view('errors/database')              Check app_config('debug')
                                            /                     \
                                     (true)/                       \(false)
                                          v                         v
                                Display Stack Trace       Render view('errors/404')
```

### 2.1 Exception Dispatch Implementation (`public/index.php`)

```php
try {
    $router->dispatch(request_method(), parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
} catch (PDOException $exception) {
    http_response_code(500);
    view('errors/database', ['title' => 'Database Setup Required'], 'auth');
} catch (Throwable $exception) {
    http_response_code(500);
    if (app_config('debug')) {
        exit('Application error: ' . e($exception->getMessage()));
    }
    view('errors/404', ['title' => 'Application Error'], 'auth');
}
```

---

## 3. Standard HTTP Status Code Reference

Neurosatva uses standard HTTP status codes across Web Views and REST APIs:

| Code | Status Title | Usage Context | Example Output |
|---|---|---|---|
| `200` | OK | Successful GET/POST requests | `{"success": true}` |
| `206` | Partial Content | Video/Audio Byte-Range streaming | Binary chunk payload + `Content-Range` header |
| `400` | Bad Request | Missing required query/form parameters | `exit('Bad request')` |
| `401` | Unauthorized | Session missing or role unauthenticated | `{"error": "Unauthorized"}` |
| `403` | Forbidden | Path traversal attempt or unassigned module access | `{"error": "Not authorized"}` / `exit('Forbidden')` |
| `404` | Not Found | Unknown route or non-existent file/record | `view('errors/404')` |
| `419` | Page Expired | CSRF token mismatch or stale form submission | `'Invalid security token. Refresh the page and try again.'` |
| `422` | Unprocessable Entity | Input validation failure (e.g. invalid ESP32 IP format) | `{"error": "Invalid IP address format."}` |
| `500` | Internal Server Error | Database failure or missing module config file | `view('errors/database')` |

---

## 4. Layer-Specific Error Handling

### 4.1 Form Input & Validation Errors
- Form inputs are validated in Controller endpoints before model execution.
- Validation failures set session flash errors (`$_SESSION['error']`) and redirect back to the submitting view:

```php
if (empty($email) || empty($password)) {
    Session::setFlash('error', 'Please fill in all required fields.');
    redirect('/tutor/login');
}
```

### 4.2 REST API Error Handling (`ApiController.php`)
- APIs return uniform JSON payloads with specific status codes via helper method `json()`:

```php
private function json(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
```

### 4.3 Storage & File Streaming Error Handling
- Invalid storage requests produce early termination before opening file descriptors:

```php
if (!$fullPath || !str_starts_with($fullPath, realpath($basePath))) {
    http_response_code(403);
    exit('Forbidden');
}
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('Not found');
}
```

---

## 5. Client-Side & Hardware Error Telemetry

### 5.1 ESP32 WLED Hardware Unreachable Fallback
- During module playback, `runtime.js` sends asynchronous `fetch()` commands to the tutor's local ESP32 IP address.
- Hardware errors (device powered off, network timeout, IP mismatch) are caught locally without crashing video playback.

```javascript
fetch(`http://${esp32Ip}/json/state`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(wledPayload)
}).catch(err => {
    console.warn('ESP32 WLED hardware communication error:', err);
    // Continue video playback gracefully without blocking UI
});
```

### 5.2 Playback Failure Telemetry Reporting
- If a video fails to play or stops due to a client-side media error, `runtime.js` reports the diagnostic error message to `/api/runtime/end`:

```javascript
function reportPlaybackError(logId, assignmentId, errorMessage) {
    const formData = new FormData();
    formData.append('log_id', logId);
    formData.append('assignment_id', assignmentId);
    formData.append('completed', '0');
    formData.append('error', errorMessage);

    navigator.sendBeacon('/neurosatva/api/runtime/end', formData);
}
```
- The backend stores `errorMessage` inside `playback_session_logs.error_log` for administrative inspection.
