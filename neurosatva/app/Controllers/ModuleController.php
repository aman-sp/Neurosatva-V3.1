<?php

final class ModuleController
{
    private ModuleStorageService $storage;

    public function __construct()
    {
        $this->storage = new ModuleStorageService();
    }

    private function guard(): void
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $this->guard();
        view('admin/modules/index', [
            'title' => 'Digital Vault',
            'modules' => DigitalModule::all(input('search'), input('sort')),
            'search' => input('search'),
            'sort' => input('sort'),
        ]);
    }

    public function create(): void
    {
        $this->guard();
        view('admin/modules/form', [
            'title' => 'Create Module',
            'module' => null,
            'config' => ['timeline' => []],
            'audioFiles' => [],
        ]);
    }

    public function store(): void
    {
        $this->guard();
        Csrf::verify();
        try {
            $data = $this->storage->saveFromRequest();
            $data['created_by'] = Auth::id();
            $id = DigitalModule::create($data);
            AuditLog::adminAction(Auth::id(), 'created_module', 'module', $id);
            Session::flash('success', 'Module saved in the Digital Vault.');
            redirect('/admin/modules');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect('/admin/modules/create');
        }
    }

    public function view(): void
    {
        $this->guard();
        $module = $this->findOrRedirect();
        view('admin/modules/view', [
            'title' => 'View Module',
            'module' => $module,
            'payload' => $this->storage->modulePayload($module),
        ]);
    }

    public function edit(): void
    {
        $this->guard();
        $module = $this->findOrRedirect();
        $payload = $this->storage->modulePayload($module);
        $folder = dirname(__DIR__, 2) . '/storage/modules/' . $module['folder_name'];
        view('admin/modules/form', [
            'title' => 'Edit Module',
            'module' => $module,
            'config' => $payload['config'],
            'audioFiles' => $this->storage->listAudioFiles($folder),
        ]);
    }

    public function update(): void
    {
        $this->guard();
        Csrf::verify();
        $module = $this->findOrRedirect();
        try {
            $data = $this->storage->saveFromRequest($module);
            DigitalModule::update((int) $module['id'], $data);
            AuditLog::adminAction(Auth::id(), 'updated_module', 'module', (int) $module['id']);
            Session::flash('success', 'Module updated and config.json regenerated.');
            redirect('/admin/modules');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect('/admin/modules/edit?id=' . (int) $module['id']);
        }
    }

    public function delete(): void
    {
        $this->guard();
        Csrf::verify();
        $module = $this->findOrRedirect();
        $activeAssignments = DigitalModule::activeAssignmentCount((int) $module['id']);
        if ($activeAssignments > 0 && input('confirm_assigned_delete') !== '1') {
            Session::flash('error', 'This module is assigned to tutors. Confirm assigned deletion to remove it.');
            redirect('/admin/modules');
        }

        try {
            Database::connection()->beginTransaction();
            Database::connection()->prepare('DELETE FROM module_assignments WHERE module_id = :id')->execute(['id' => $module['id']]);
            DigitalModule::delete((int) $module['id']);
            Database::connection()->commit();
            $this->storage->deleteModuleFolder($module);
            AuditLog::adminAction(Auth::id(), 'deleted_module', 'module', (int) $module['id']);
            Session::flash('success', 'Module deleted from the Digital Vault.');
        } catch (Throwable $e) {
            if (Database::connection()->inTransaction()) {
                Database::connection()->rollBack();
            }
            Session::flash('error', 'Unable to delete module.');
        }
        redirect('/admin/modules');
    }

    public function test(): void
    {
        $this->guard();
        $module = $this->findOrRedirect();
        view('admin/modules/test', [
            'title' => 'Test Module',
            'module' => $module,
            'payload' => $this->storage->modulePayload($module),
        ]);
    }

    private function findOrRedirect(): array
    {
        $module = DigitalModule::find((int) input('id'));
        if (!$module) {
            Session::flash('error', 'Module not found.');
            redirect('/admin/modules');
        }
        return $module;
    }
}
