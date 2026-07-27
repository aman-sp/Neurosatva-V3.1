<?php

final class TutorController
{
    private function guard(bool $allowIncompleteGmail = false): array
    {
        Auth::requireRole('tutor');
        $tutor = Tutor::find(Auth::id());

        if (!$tutor || $tutor['status'] !== 'active') {
            Auth::logout();
            Session::flash('error', 'Your registration is still under Admin review. Please wait until your account has been approved.');
            redirect('/tutor/login');
        }

        if (!$allowIncompleteGmail && !Tutor::gmailIsComplete($tutor)) {
            redirect('/tutor/official-gmail/setup');
        }

        return $tutor;
    }

    public function dashboard(): void
    {
        $this->guard();
        $videos = Video::verifiedForTutor(Auth::id());
        view('tutor/dashboard', [
            'title' => 'Tutor Dashboard',
            'videos' => array_slice($videos, 0, 6),
            'totalVerified' => count($videos),
        ]);
    }

    public function videos(): void
    {
        $this->guard();
        view('tutor/videos', [
            'title' => 'My Verified Videos',
            'videos' => Video::verifiedForTutor(Auth::id()),
        ]);
    }

    public function instructions(): void
    {
        $this->guard();
        view('tutor/instructions', [
            'title' => 'Submit Video Link',
        ]);
    }

    public function submitVideoLink(): void
    {
        $tutor = $this->guard();
        Csrf::verify();

        $title = input('title');
        $description = input('description');
        $folderLink = input('folder_link');

        if (!$title || !$description || !filter_var($folderLink, FILTER_VALIDATE_URL)) {
            Session::flash('error', 'Enter the video title, description, and a valid folder link.');
            redirect('/tutor/instructions');
        }

        Video::create([
            'tutor_id' => Auth::id(),
            'title' => $title,
            'email_subject' => 'Tutor upload link submission',
            'source_email' => $tutor['official_gmail'] ?: $tutor['email'],
            'storage_path' => $folderLink,
            'status' => 'pending',
            'admin_remarks' => $description,
            'received_at' => date('Y-m-d H:i:s'),
        ]);

        Session::flash('success', 'Your video link has been sent to the admin for verification.');
        redirect('/tutor/instructions');
    }

    public function profile(): void
    {
        $tutor = $this->guard();
        view('tutor/profile', [
            'title' => 'Profile',
            'tutor' => $tutor,
        ]);
    }

    public function officialGmailSetup(): void
    {
        $tutor = $this->guard(true);
        if (Tutor::gmailIsComplete($tutor)) {
            redirect('/tutor/dashboard');
        }

        view('tutor/official-gmail-setup', [
            'title' => 'Official Gmail Setup',
            'tutor' => $tutor,
            'gmailStatus' => Tutor::gmailStatus($tutor),
        ]);
    }

    public function saveOfficialGmail(): void
    {
        $this->guard(true);
        Csrf::verify();

        $gmail = strtolower(input('official_gmail'));
        $confirm = strtolower(input('confirm_official_gmail'));
        $confirmed = input('gmail_confirmed') === '1';

        if ($gmail !== $confirm) {
            Session::flash('error', 'Official Gmail and confirmation must match.');
            redirect('/tutor/official-gmail/setup');
        }

        if (!$confirmed) {
            Session::flash('error', 'Please confirm that you have created and verified this Gmail account.');
            redirect('/tutor/official-gmail/setup');
        }

        if (!filter_var($gmail, FILTER_VALIDATE_EMAIL) || !str_ends_with($gmail, '@gmail.com') || !str_contains($gmail, '_neuro')) {
            Session::flash('error', 'Official Gmail must end with @gmail.com and contain "_neuro". Example: rahul_neuro@gmail.com');
            redirect('/tutor/official-gmail/setup');
        }

        try {
            $otp = Tutor::startOfficialGmailVerification(Auth::id(), $gmail);
        } catch (Throwable $e) {
            Session::flash('error', 'This Gmail address is already assigned to another tutor.');
            redirect('/tutor/official-gmail/setup');
        }

        $sent = $this->sendGmailOtp($gmail, $otp);
        if ($sent) {
            Session::flash('success', 'We have sent an OTP to your official Gmail.');
        } else {
            Session::flash('success', 'Resend is not configured or failed locally. OTP was saved in storage/logs/gmail-otps.log.');
        }
        redirect('/tutor/official-gmail/setup');
    }

    public function verifyOfficialGmailOtp(): void
    {
        $this->guard(true);
        Csrf::verify();

        $otp = input('otp');
        if (!preg_match('/^\d{6}$/', $otp)) {
            Session::flash('error', 'Enter the 6-digit OTP sent to your official Gmail.');
            redirect('/tutor/official-gmail/setup');
        }

        if (!Tutor::verifyOfficialGmailOtp(Auth::id(), $otp)) {
            Session::flash('error', 'Invalid or expired OTP. Please check the code or resend OTP.');
            redirect('/tutor/official-gmail/setup');
        }

        view('tutor/official-gmail-verified', [
            'title' => 'Gmail Verified',
        ]);
    }

    private function sendGmailOtp(string $gmail, string $otp): bool
    {
        $apiKey = app_config('resend_api_key');
        $from = app_config('resend_from_email');

        if (!$apiKey) {
            $this->logOtp($gmail, $otp);
            return false;
        }

        $payload = json_encode([
            'from' => $from,
            'to' => [$gmail],
            'subject' => 'Your Neurosatva official Gmail OTP',
            'html' => '<p>Your Neurosatva Gmail verification OTP is:</p><h2>' . e($otp) . '</h2><p>This OTP expires in 10 minutes.</p>',
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
            $this->logOtp($gmail, $otp, $response ?: $statusLine);
        }

        return $sent;
    }

    private function logOtp(string $gmail, string $otp, string $error = ''): void
    {
        $logPath = dirname(__DIR__, 2) . '/storage/logs/gmail-otps.log';
        $suffix = $error ? ' error: ' . trim($error) : '';
        file_put_contents($logPath, '[' . date('Y-m-d H:i:s') . "] {$gmail} OTP: {$otp}{$suffix}\n", FILE_APPEND);
    }
}
