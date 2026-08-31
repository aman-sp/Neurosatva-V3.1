<?php

final class AdminController
{
    private function guard(): void
    {
        Auth::requireRole('admin');
    }

    public function dashboard(): void
    {
        $this->guard();
        $videoMetrics = Video::metrics();
        $tutors = Tutor::all();
        view('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'videoMetrics' => $videoMetrics,
            'totalTutors' => count($tutors),
            'pendingTutorRequests' => TutorRegistrationRequest::pendingCount(),
            'unreadNotifications' => AdminNotification::unreadCount(),
            'notifications' => AdminNotification::recent(),
            'tutors' => array_slice($tutors, 0, 8),
        ]);
    }

    public function tutors(): void
    {
        $this->guard();
        view('admin/tutors', [
            'title' => 'Manage Tutors',
            'tutors' => Tutor::all(input('search'), input('status') ?: null),
            'search' => input('search'),
            'status' => input('status'),
        ]);
    }

    public function registrationRequests(): void
    {
        $this->guard();
        $status = input('status') ?: null;
        view('admin/registration-requests', [
            'title' => 'Tutor Verification',
            'requests' => TutorRegistrationRequest::all($status),
            'status' => $status,
        ]);
    }

    public function viewRegistrationRequest(): void
    {
        $this->guard();
        $request = TutorRegistrationRequest::find((int) input('id'));
        if (!$request) {
            Session::flash('error', 'Registration request not found.');
            redirect('/admin/registration-requests');
        }
        view('admin/registration-request-view', [
            'title' => 'Registration Request',
            'request' => $request,
        ]);
    }

    public function approveRegistrationRequest(): void
    {
        $this->guard();
        Csrf::verify();

        try {
            $initialPassword = $this->generateInitialTutorPassword();
            $tutorId = TutorRegistrationRequest::approve((int) input('id'), Auth::id(), $initialPassword);
            $tutor = Tutor::find($tutorId);
            $welcomeEmailSent = $tutor ? $this->sendTutorWelcomeEmail($tutor, $initialPassword) : false;
            AdminNotification::markTutorRegistrationRead((int) input('id'));
            AuditLog::adminAction(Auth::id(), 'approved_tutor_registration', 'tutor_registration_request', (int) input('id'), [
                'tutor_id' => $tutorId,
                'welcome_email_sent' => $welcomeEmailSent,
            ]);
            $message = 'Tutor verified successfully. User ID: ' . tutor_user_id($tutorId);
            if (!$welcomeEmailSent) {
                $message .= ' Welcome email could not be sent; please check email configuration/logs.';
            }
            Session::flash($welcomeEmailSent ? 'success' : 'error', $message);
        } catch (Throwable $e) {
            Session::flash('error', 'Unable to approve this request. The email may already exist as a tutor.');
        }

        redirect('/admin/registration-requests');
    }

    public function rejectRegistrationRequest(): void
    {
        $this->guard();
        Csrf::verify();

        $id = (int) input('id');
        TutorRegistrationRequest::reject($id, Auth::id(), input('admin_remarks'));
        AdminNotification::markTutorRegistrationRead($id);
        AuditLog::adminAction(Auth::id(), 'rejected_tutor_registration', 'tutor_registration_request', $id);
        Session::flash('success', 'Tutor registration request rejected.');
        redirect('/admin/registration-requests');
    }

    public function editTutor(): void
    {
        $this->guard();
        $tutor = Tutor::find((int) input('id'));
        if (!$tutor) {
            Session::flash('error', 'Tutor not found.');
            redirect('/admin/tutors');
        }
        view('admin/edit-tutor', ['title' => 'Edit Tutor', 'tutor' => $tutor]);
    }

    public function updateTutor(): void
    {
        $this->guard();
        Csrf::verify();

        $id = (int) input('id');
        $name = input('name');
        $email = filter_var(input('email'), FILTER_VALIDATE_EMAIL);
        $phone = input('phone') ?: null;
        $status = in_array(input('status'), ['active', 'deactivated'], true) ? input('status') : 'active';
        $password = input('password') ?: null;

        if (!$id || !$name || !$email || ($password !== null && strlen($password) < 8)) {
            Session::flash('error', 'Check the tutor details and password length.');
            redirect('/admin/tutors/edit?id=' . $id);
        }

        Tutor::update($id, $name, $email, $status, $password, $phone);
        AuditLog::adminAction(Auth::id(), 'updated_tutor', 'tutor', $id, ['status' => $status]);
        Session::flash('success', 'Tutor updated successfully.');
        redirect('/admin/tutors');
    }

    public function deleteTutor(): void
    {
        $this->guard();
        Csrf::verify();
        $id = (int) input('id');
        Tutor::delete($id);
        AuditLog::adminAction(Auth::id(), 'deleted_tutor', 'tutor', $id);
        Session::flash('success', 'Tutor deleted.');
        redirect('/admin/tutors');
    }

    public function videos(): void
    {
        $this->guard();
        view('admin/videos', [
            'title' => 'Video Verification',
            'videos' => Video::all(input('status') ?: null, input('tutor_id') ? (int) input('tutor_id') : null),
            'tutors' => Tutor::active(),
            'status' => input('status'),
            'tutorId' => input('tutor_id'),
        ]);
    }

    public function storeVideo(): void
    {
        $this->guard();
        Csrf::verify();

        $tutorId = (int) input('tutor_id');
        $title = input('title');
        $status = in_array(input('status'), ['pending', 'verified', 'rejected'], true) ? input('status') : 'pending';

        if (!$tutorId || !$title) {
            Session::flash('error', 'Select a tutor and enter a video title.');
            redirect('/admin/videos');
        }

        $id = Video::create([
            'tutor_id' => $tutorId,
            'title' => $title,
            'email_subject' => input('email_subject'),
            'source_email' => input('source_email'),
            'storage_path' => input('storage_path'),
            'status' => $status,
            'admin_remarks' => input('admin_remarks'),
            'received_at' => input('received_at') ?: date('Y-m-d H:i:s'),
        ]);
        AuditLog::adminAction(Auth::id(), 'recorded_video_email', 'video', $id, ['status' => $status]);
        Session::flash('success', 'Video record assigned to tutor.');
        redirect('/admin/videos');
    }

    public function verifyVideo(): void
    {
        $this->guard();
        Csrf::verify();
        $id = (int) input('id');
        $status = in_array(input('status'), ['pending', 'verified', 'rejected'], true) ? input('status') : 'pending';

        Video::updateVerification($id, $status, input('admin_remarks'), input('storage_path') ?: null, Auth::id());
        AuditLog::adminAction(Auth::id(), 'verified_video', 'video', $id, ['status' => $status]);
        Session::flash('success', 'Video verification updated.');
        redirect('/admin/videos');
    }

    public function profile(): void
    {
        $this->guard();
        view('admin/profile', ['title' => 'Settings']);
    }

    public function updateProfile(): void
    {
        $this->guard();
        Csrf::verify();
        $name = input('name');
        $email = filter_var(input('email'), FILTER_VALIDATE_EMAIL);
        $password = input('password') ?: null;

        if (!$name || !$email || ($password !== null && strlen($password) < 8)) {
            Session::flash('error', 'Use a valid name, email, and optional password of at least 8 characters.');
            redirect('/admin/profile');
        }

        Admin::updateProfile(Auth::id(), $name, $email, $password);
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        Session::flash('success', 'Profile updated.');
        redirect('/admin/profile');
    }

    private function generateInitialTutorPassword(): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $symbols = '@#$%';
        $all = $upper . $lower . $digits . $symbols;

        $password = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        for ($i = count($password); $i < 12; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($password) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
        }

        return implode('', $password);
    }

    private function sendTutorWelcomeEmail(array $tutor, string $initialPassword): bool
    {
        $apiKey = app_config('resend_api_key');
        $from = app_config('resend_from_email');
        $to = $tutor['personal_email'] ?: $tutor['email'];
        $loginUrl = app_config('url') . '/admin/login';
        $userId = tutor_user_id($tutor['id']);

        if (!$apiKey || !$to) {
            $this->logTutorWelcomeEmail($tutor, 'Resend API key or tutor email is not configured.');
            return false;
        }

        $payload = json_encode([
            'from' => $from,
            'to' => [$to],
            'subject' => 'Welcome to Neurosatva - Your tutor login credentials',
            'html' => '<h2>Welcome to Neurosatva</h2>'
                . '<p>Hello ' . e($tutor['name']) . ',</p>'
                . '<p>Your tutor account has been approved. You can now log in using the credentials below.</p>'
                . '<p><strong>Tutor User ID:</strong> ' . e($userId) . '</p>'
                . '<p><strong>Personal Email:</strong> ' . e($tutor['personal_email'] ?: $tutor['email']) . '</p>'
                . '<p><strong>Temporary Password:</strong> ' . e($initialPassword) . '</p>'
                . '<p><a href="' . e($loginUrl) . '">Login to Neurosatva</a></p>'
                . '<p>After login, please complete your official Gmail setup and keep your credentials secure.</p>',
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
            $this->logTutorWelcomeEmail($tutor, $response ?: $statusLine);
        }

        return $sent;
    }

    private function logTutorWelcomeEmail(array $tutor, string $error): void
    {
        $logPath = dirname(__DIR__, 2) . '/storage/logs/tutor-welcome-emails.log';
        $message = '[' . date('Y-m-d H:i:s') . '] Tutor #' . ($tutor['id'] ?? 'unknown') . ' welcome email failed: ' . trim($error) . "\n";
        file_put_contents($logPath, $message, FILE_APPEND);
    }
}
