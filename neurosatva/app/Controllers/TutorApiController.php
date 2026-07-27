<?php

final class TutorApiController
{
    use ApiSupport;

    public function modules(): void
    {
        $this->requireApiRole('tutor');
        json_response(['ok' => true, 'modules' => ModuleAssignment::forTutor(Auth::id())]);
    }

    public function module(string $id): void
    {
        $this->requireApiRole('tutor');
        $assignment = ModuleAssignment::playableForTutor((int) $id, Auth::id());
        if (!$assignment) {
            json_response(['ok' => false, 'message' => 'Module is not assigned to this tutor.'], 404);
        }
        if ((int) $assignment['remaining_plays'] < 1 || (!empty($assignment['expiry_date']) && strtotime($assignment['expiry_date'] . ' 23:59:59') < time())) {
            json_response(['ok' => false, 'message' => 'No remaining plays or assignment expired.'], 403);
        }
        json_response(['ok' => true, 'assignment' => $assignment, 'payload' => (new ModuleStorageService())->modulePayload($assignment)]);
    }
}
