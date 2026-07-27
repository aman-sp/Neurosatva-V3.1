<?php

final class TutorModuleController
{
    private function guard(): void
    {
        Auth::requireRole('tutor');
        $tutor = Tutor::find(Auth::id());
        if (!$tutor || $tutor['status'] !== 'active') {
            Auth::logout();
            redirect('/tutor/login');
        }
        if (!Tutor::gmailIsComplete($tutor)) {
            redirect('/tutor/official-gmail/setup');
        }
    }

    public function index(): void
    {
        $this->guard();
        view('tutor/modules/index', [
            'title' => 'Tutor Digital Vault',
            'assignments' => ModuleAssignment::forTutor(Auth::id()),
        ]);
    }

    public function play(): void
    {
        $this->guard();
        $assignment = ModuleAssignment::playableForTutor((int) input('id'), Auth::id());
        if (!$assignment) {
            Session::flash('error', 'This module is not assigned to you.');
            redirect('/tutor/modules');
        }
        if (!$this->isPlayable($assignment)) {
            Session::flash('error', 'This module is no longer playable.');
            redirect('/tutor/modules');
        }
        view('tutor/modules/play', [
            'title' => 'Play Module',
            'assignment' => $assignment,
            'payload' => (new ModuleStorageService())->modulePayload($assignment),
        ]);
    }

    private function isPlayable(array $assignment): bool
    {
        if ((int) $assignment['remaining_plays'] < 1) {
            return false;
        }
        return empty($assignment['expiry_date']) || strtotime($assignment['expiry_date'] . ' 23:59:59') >= time();
    }
}
