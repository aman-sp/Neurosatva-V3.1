<?php

final class AuthController
{
    public function adminLogin(): void
    {
        view('admin/login', ['title' => 'Admin Login'], 'auth');
    }

    public function tutorLogin(): void
    {
        view('tutor/login', ['title' => 'Tutor Login'], 'auth');
    }

    public function tutorRegister(): void
    {
        redirect('/admin/login?register=1');
    }

    public function submitTutorRegistration(): void
    {
        Csrf::verify();

        $name = input('full_name');
        $email = filter_var(input('email'), FILTER_VALIDATE_EMAIL);
        $phone = input('phone');
        $school = input('school_name') ?: null;
        $gender = input('gender') ?: null;
        $validGenders = ['Female', 'Male', 'Non-binary', 'Prefer not to say'];

        if (!$name || !$email || !$phone || ($gender !== null && !in_array($gender, $validGenders, true))) {
            Session::flash('error', 'Please complete the required registration fields.');
            redirect('/admin/login?register=1');
        }

        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            Session::flash('error', 'Enter a valid phone number.');
            redirect('/admin/login?register=1');
        }

        if (Tutor::findByEmail($email)) {
            Session::flash('error', 'A tutor account already exists for this email.');
            redirect('/admin/login?register=1');
        }

        $existingRequest = TutorRegistrationRequest::findLatestByEmail($email);
        if ($existingRequest && $existingRequest['status'] === 'Pending') {
            Session::flash('error', 'Your registration is already awaiting Admin approval.');
            redirect('/tutor/login');
        }

        $requestId = TutorRegistrationRequest::create($name, $email, $phone, $school, $gender);
        $request = TutorRegistrationRequest::find($requestId);
        if ($request) {
            AdminNotification::createTutorRegistration($request);
            $this->sendAdminRegistrationEmail($request);
        }
        redirect('/admin/login?registered=1&request_id=' . $requestId);
    }

    private function sendAdminRegistrationEmail(array $request): bool
    {
        $apiKey = app_config('resend_api_key');
        $to = app_config('admin_notification_email');
        $from = app_config('resend_from_email');
        $approvalUrl = app_config('url') . '/admin/registration-requests?status=Pending';

        if (!$apiKey || !$to) {
            $this->logAdminNotificationEmail($request, 'Resend API key or admin email is not configured.');
            return false;
        }

        $payload = json_encode([
            'from' => $from,
            'to' => [$to],
            'subject' => 'New tutor registration approval required',
            'html' => '<h2>New tutor registration</h2>'
                . '<p>A new tutor has registered and is waiting for admin approval.</p>'
                . '<p><strong>Name:</strong> ' . e($request['full_name']) . '</p>'
                . '<p><strong>Email:</strong> ' . e($request['email']) . '</p>'
                . '<p><strong>Phone:</strong> ' . e($request['phone'] ?? 'Not provided') . '</p>'
                . '<p><strong>School:</strong> ' . e($request['school_name'] ?: 'Not provided') . '</p>'
                . '<p><strong>Gender:</strong> ' . e($request['gender'] ?: 'Not provided') . '</p>'
                . '<p><strong>Application ID:</strong> NS-TUTOR-' . e(str_pad((string) $request['id'], 5, '0', STR_PAD_LEFT)) . '</p>'
                . '<p><a href="' . e($approvalUrl) . '">Open Tutor Verification</a></p>',
        ], JSON_THROW_ON_ERROR);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\n",
                'content' => $payload,
                'ignore_errors' => true,
                'timeout' => 12,
            ],
        ]);

        $response = @file_get_contents('https://api.resend.com/emails', false, $context);
        $statusLine = $http_response_header[0] ?? '';
        $sent = str_contains($statusLine, '200') || str_contains($statusLine, '202');

        if (!$sent) {
            $this->logAdminNotificationEmail($request, $response ?: $statusLine);
        }

        return $sent;
    }

    private function logAdminNotificationEmail(array $request, string $error): void
    {
        $logPath = dirname(__DIR__, 2) . '/storage/logs/admin-notification-emails.log';
        $message = '[' . date('Y-m-d H:i:s') . '] Tutor request #' . ($request['id'] ?? 'unknown') . ' email failed: ' . trim($error) . "\n";
        file_put_contents($logPath, $message, FILE_APPEND);
    }

    public function tutorRegistrationSuccess(): void
    {
        redirect('/admin/login?registered=1');
    }

    public function authenticateAdmin(): void
    {
        $this->authenticateUnified();
    }

    public function authenticateTutor(): void
    {
        $this->authenticateUnified();
    }

    private function authenticateUnified(): void
    {
        Csrf::verify();
        $login = input('email');
        $email = filter_var($login, FILTER_VALIDATE_EMAIL);
        $password = input('password');

        $admin = $email ? Admin::findByEmail($email) : null;
        $adminOk = $admin && password_verify($password, $admin['password_hash']) && $admin['status'] === 'active';
        if ($adminOk) {
            AuditLog::login('admin', (int) $admin['id'], (string) $email, true);
            Auth::login(['id' => $admin['id'], 'name' => $admin['name'], 'email' => $admin['email'], 'role' => 'admin']);
            redirect('/admin/dashboard');
        }

        $tutor = $login ? Tutor::findByLogin($login) : null;
        $ok = $tutor && password_verify($password, $tutor['password_hash']) && $tutor['status'] === 'active';
        AuditLog::login('tutor', $tutor['id'] ?? null, $login, (bool) $ok);

        if (!$ok) {
            if ($tutor && $tutor['status'] !== 'active') {
                Session::flash('error', 'Your registration is still under Admin review. Please wait until your account has been approved.');
                redirect('/tutor/login');
            }
            if ($email && !$tutor) {
                $request = TutorRegistrationRequest::findLatestByEmail($email);
                if ($request && $request['status'] === 'Pending') {
                    Session::flash('error', 'Your registration is still under Admin review. Please wait until your account has been approved.');
                    redirect('/tutor/login');
                }
                if ($request && $request['status'] === 'Rejected') {
                    Session::flash('error', 'Your registration has been rejected. Please contact the Administrator.');
                    redirect('/tutor/login');
                }
            }
            if ($admin) {
                AuditLog::login('admin', (int) $admin['id'], (string) $email, false);
            }
            Session::flash('error', 'Invalid credentials or inactive account.');
            redirect('/admin/login');
        }

        Auth::login(['id' => $tutor['id'], 'name' => $tutor['name'], 'email' => $tutor['email'], 'role' => 'tutor']);
        if (!Tutor::gmailIsComplete($tutor)) {
            redirect('/tutor/official-gmail/setup');
        }
        redirect('/tutor/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/');
    }
}
