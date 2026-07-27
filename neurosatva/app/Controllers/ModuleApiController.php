<?php

final class ModuleApiController
{
    use ApiSupport;

    public function index(): void
    {
        $this->requireApiRole('admin');
        json_response(['ok' => true, 'modules' => DigitalModule::all(input('search'), input('sort'))]);
    }

    public function store(): void
    {
        $this->requireApiRole('admin');
        try {
            $data = (new ModuleStorageService())->saveFromRequest();
            $data['created_by'] = Auth::id();
            $id = DigitalModule::create($data);
            json_response(['ok' => true, 'id' => $id], 201);
        } catch (Throwable $e) {
            json_response(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(string $id): void
    {
        $this->requireApiRole('admin');
        $module = DigitalModule::find((int) $id);
        if (!$module) {
            json_response(['ok' => false, 'message' => 'Module not found.'], 404);
        }
        try {
            DigitalModule::update((int) $id, (new ModuleStorageService())->saveFromRequest($module));
            json_response(['ok' => true]);
        } catch (Throwable $e) {
            json_response(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function delete(string $id): void
    {
        $this->requireApiRole('admin');
        $module = DigitalModule::find((int) $id);
        if (!$module) {
            json_response(['ok' => false, 'message' => 'Module not found.'], 404);
        }
        if (DigitalModule::activeAssignmentCount((int) $id) > 0 && input('confirm') !== '1') {
            json_response(['ok' => false, 'message' => 'Module is assigned. Send confirm=1 to delete.'], 409);
        }
        Database::connection()->prepare('DELETE FROM module_assignments WHERE module_id = :id')->execute(['id' => $id]);
        DigitalModule::delete((int) $id);
        (new ModuleStorageService())->deleteModuleFolder($module);
        json_response(['ok' => true]);
    }

    public function test(): void
    {
        $this->requireApiRole('admin');
        $data = $this->jsonInput();
        $ip = trim((string) ($data['esp32_ip'] ?? input('esp32_ip')));
        $probe = WledClient::probe($ip);
        json_response($probe, $probe['ok'] ? 200 : 422);
    }
}
