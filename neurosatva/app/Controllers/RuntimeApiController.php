<?php

final class RuntimeApiController
{
    use ApiSupport;

    public function start(): void
    {
        $this->requireApiRole('tutor');
        $data = $this->jsonInput();
        $assignmentId = (int) ($data['assignment_id'] ?? 0);
        $assignment = ModuleAssignment::playableForTutor($assignmentId, Auth::id());
        if (!$assignment) {
            json_response(['ok' => false, 'message' => 'Assignment not found.'], 404);
        }
        if ((int) $assignment['remaining_plays'] < 1 || (!empty($assignment['expiry_date']) && strtotime($assignment['expiry_date'] . ' 23:59:59') < time())) {
            json_response(['ok' => false, 'message' => 'Playback is not allowed.'], 403);
        }
        $sessionId = ModuleSessionLog::start($assignmentId, $assignment['esp32_ip']);
        json_response(['ok' => true, 'session_id' => $sessionId]);
    }

    public function end(): void
    {
        $this->requireApiRole('tutor');
        $data = $this->jsonInput();
        $sessionId = (int) ($data['session_id'] ?? 0);
        $assignmentId = (int) ($data['assignment_id'] ?? 0);
        $completed = (bool) ($data['completed'] ?? false);
        ModuleSessionLog::end($sessionId, $completed, isset($data['duration']) ? (int) $data['duration'] : null, $data['errors'] ?? null);
        if ($completed && $assignmentId) {
            ModuleAssignment::decrementPlay($assignmentId);
        }
        json_response(['ok' => true]);
    }
}
