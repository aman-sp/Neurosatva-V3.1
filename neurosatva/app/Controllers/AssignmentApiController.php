<?php

final class AssignmentApiController
{
    use ApiSupport;

    public function store(): void
    {
        $this->requireApiRole('admin');
        $_POST = array_merge($_POST, $this->jsonInput());
        try {
            $id = (new AssignmentController())->validateAndCreate();
            json_response(['ok' => true, 'id' => $id], 201);
        } catch (Throwable $e) {
            json_response(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(string $id): void
    {
        $this->requireApiRole('admin');
        $_POST = array_merge($_POST, $this->jsonInput());
        try {
            $data = [
                'esp32_ip' => input('esp32_ip'),
                'remaining_plays' => (int) input('remaining_plays'),
                'expiry_date' => input('expiry_date') ?: null,
                'status' => in_array(input('status'), ['active', 'revoked', 'expired'], true) ? input('status') : 'active',
            ];
            if (!WledClient::validIp($data['esp32_ip']) || $data['remaining_plays'] < 0) {
                throw new InvalidArgumentException('Assignment data is invalid.');
            }
            ModuleAssignment::update((int) $id, $data);
            json_response(['ok' => true]);
        } catch (Throwable $e) {
            json_response(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function delete(string $id): void
    {
        $this->requireApiRole('admin');
        ModuleAssignment::delete((int) $id);
        json_response(['ok' => true]);
    }
}
