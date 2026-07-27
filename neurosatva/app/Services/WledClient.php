<?php

final class WledClient
{
    public static function validIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    public static function probe(string $ip): array
    {
        if (!self::validIp($ip)) {
            return ['ok' => false, 'message' => 'Enter a valid IPv4 address.'];
        }

        $context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
        $response = @file_get_contents('http://' . $ip . '/json/info', false, $context);
        $status = $http_response_header[0] ?? '';
        if ($response === false || !str_contains($status, '200')) {
            return ['ok' => false, 'message' => 'WLED device is not reachable at this IP address.'];
        }

        $json = json_decode($response, true);
        return is_array($json)
            ? ['ok' => true, 'message' => 'WLED device responded.', 'device' => $json]
            : ['ok' => false, 'message' => 'Device responded but did not return WLED JSON.'];
    }
}
