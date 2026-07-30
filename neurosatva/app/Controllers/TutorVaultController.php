<?php

final class TutorVaultController
{
    private function guard(): array
    {
        Auth::requireRole('tutor');
        $tutor = Tutor::find(Auth::id());
        if (!$tutor || $tutor['status'] !== 'active') {
            Auth::logout();
            Session::flash('error', 'Your account is not active.');
            redirect('/tutor/login');
        }
        return $tutor;
    }

    public function vault(): void
    {
        $tutor = $this->guard();
        $assignments = ModuleAssignment::allForTutor(Auth::id());
        // For each assignment, add is_playable flag
        foreach ($assignments as &$a) {
            $a['is_playable'] = ModuleAssignment::isPlayable($a);
        }
        view('tutor/vault', [
            'title' => 'My Modules',
            'assignments' => $assignments,
        ]);
    }

    public function play(): void
    {
        $tutor = $this->guard();
        $assignmentId = (int) input('id');
        $assignment = ModuleAssignment::findForTutor($assignmentId, Auth::id());

        if (!$assignment) {
            Session::flash('error', 'Module not found or not assigned to you.');
            redirect('/tutor/vault');
        }

        if (!ModuleAssignment::isPlayable($assignment)) {
            Session::flash('error', 'This module cannot be played. It may have expired or exhausted its play count.');
            redirect('/tutor/vault');
        }

        $config = Module::getConfig((int) $assignment['module_id']);
        if (!$config) {
            Session::flash('error', 'Module configuration is missing or corrupt. Please contact the administrator.');
            redirect('/tutor/vault');
        }

        view('tutor/vault-play', [
            'title' => 'Play: ' . e($assignment['module_name']),
            'assignment' => $assignment,
            'config' => $config,
        ]);
    }
}
