<?php

trait ApiSupport
{
    private function requireApiRole(string $role): void
    {
        if (Auth::role() !== $role) {
            json_response(['ok' => false, 'message' => 'Unauthorized.'], 401);
        }
    }

    private function jsonInput(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : $_POST;
    }
}
