<?php

final class AssignmentController
{
    private function guard(): void
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $this->guard();
        view('admin/assign-module', [
            'title' => 'Assign Module',
            'tutors' => Tutor::active(),
            'modules' => DigitalModule::active(),
            'assignments' => ModuleAssignment::all(),
        ]);
    }

    public function store(): void
    {
        $this->guard();
        Csrf::verify();
        try {
            $this->validateAndCreate();
            Session::flash('success', 'Module assigned to tutor.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        redirect('/admin/assign-module');
    }

    public function update(): void
    {
        $this->guard();
        Csrf::verify();
        $id = (int) input('id');
        try {
            $data = $this->assignmentData();
            ModuleAssignment::update($id, $data);
            Session::flash('success', 'Assignment updated.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        redirect('/admin/assign-module');
    }

    public function delete(): void
    {
        $this->guard();
        Csrf::verify();
        ModuleAssignment::delete((int) input('id'));
        Session::flash('success', 'Assignment deleted.');
        redirect('/admin/assign-module');
    }

    public function validateAndCreate(): int
    {
        $data = $this->assignmentData();
        $data['tutor_id'] = (int) input('tutor_id');
        $data['module_id'] = (int) input('module_id');
        $data['assigned_by'] = Auth::id();
        if (!$data['tutor_id'] || !$data['module_id']) {
            throw new InvalidArgumentException('Select both tutor and module.');
        }
        return ModuleAssignment::create($data);
    }

    private function assignmentData(): array
    {
        $ip = input('esp32_ip');
        if (!WledClient::validIp($ip)) {
            throw new InvalidArgumentException('Enter a valid ESP32 IPv4 address.');
        }
        $plays = filter_var(input('remaining_plays'), FILTER_VALIDATE_INT);
        if ($plays === false || $plays < 1) {
            throw new InvalidArgumentException('Allowed plays must be at least 1.');
        }
        $expiry = input('expiry_date') ?: null;
        if ($expiry && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry)) {
            throw new InvalidArgumentException('Expiry date is invalid.');
        }
        return [
            'esp32_ip' => $ip,
            'remaining_plays' => $plays,
            'expiry_date' => $expiry,
            'status' => in_array(input('status'), ['active', 'revoked', 'expired'], true) ? input('status') : 'active',
        ];
    }
}
