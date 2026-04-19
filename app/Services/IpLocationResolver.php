<?php

namespace App\Services;

use GuzzleHttp\Client;

class IpLocationResolver
{
    public function resolve($ipAddress)
    {
        $ipAddress = trim((string) $ipAddress);

        if (! $this->isPublicIp($ipAddress)) {
            return [
                'status' => 'unavailable',
                'source' => 'ip_geolocation',
                'ip_address' => $ipAddress ?: null,
                'message' => 'IP geolocation was skipped because the IP is private, reserved, or missing.',
            ];
        }

        $endpoint = trim((string) config('services.ip_location.endpoint'));

        if ($endpoint === '') {
            return [
                'status' => 'not_configured',
                'source' => 'ip_geolocation',
                'ip_address' => $ipAddress,
                'message' => 'Configure IP_LOCATION_ENDPOINT to record approximate IP-based location.',
            ];
        }

        $url = $this->endpointUrl($endpoint, $ipAddress);

        try {
            $response = $this->client()->request('GET', $url, [
                'headers' => $this->headers(),
                'timeout' => $this->timeout(),
                'connect_timeout' => $this->timeout(),
                'http_errors' => false,
            ]);
        } catch (\Throwable $exception) {
            return [
                'status' => 'failed',
                'source' => 'ip_geolocation',
                'ip_address' => $ipAddress,
                'message' => 'IP location lookup failed.',
            ];
        }

        $statusCode = (int) $response->getStatusCode();
        $payload = json_decode((string) $response->getBody(), true);

        if ($statusCode < 200 || $statusCode >= 300 || ! is_array($payload)) {
            return [
                'status' => 'failed',
                'source' => 'ip_geolocation',
                'ip_address' => $ipAddress,
                'http_status' => $statusCode,
                'message' => 'IP location lookup returned an unusable response.',
            ];
        }

        return $this->normalizePayload($payload, $ipAddress, $url);
    }

    protected function client()
    {
        return new Client();
    }

    protected function endpointUrl($endpoint, $ipAddress)
    {
        if (strpos($endpoint, '{ip}') !== false) {
            return str_replace('{ip}', rawurlencode($ipAddress), $endpoint);
        }

        return rtrim($endpoint, '/').'/'.rawurlencode($ipAddress);
    }

    protected function headers()
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        $token = trim((string) config('services.ip_location.token'));

        if ($token !== '') {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return $headers;
    }

    protected function timeout()
    {
        return max(1, min((int) config('services.ip_location.timeout', 2), 10));
    }

    protected function normalizePayload(array $payload, $fallbackIp, $url)
    {
        $statusValue = $this->firstValue($payload, ['status', 'success']);
        $status = strtolower((string) $statusValue);

        if ($statusValue === false || in_array($status, ['fail', 'failed', 'false', 'error'], true)) {
            return [
                'status' => 'failed',
                'source' => 'ip_geolocation',
                'source_host' => parse_url($url, PHP_URL_HOST),
                'ip_address' => $fallbackIp,
                'message' => $this->firstValue($payload, ['message', 'error', 'reason']) ?: 'IP location provider returned an error.',
            ];
        }

        $latitude = $this->firstValue($payload, ['latitude', 'lat']);
        $longitude = $this->firstValue($payload, ['longitude', 'lon', 'lng']);
        $country = $this->firstValue($payload, ['country_name', 'country', 'countryName']);
        $region = $this->firstValue($payload, ['region_name', 'regionName', 'region', 'state']);
        $city = $this->firstValue($payload, ['city', 'town']);

        $location = [
            'status' => ($latitude !== null || $longitude !== null || $country || $region || $city) ? 'found' : 'no_match',
            'source' => 'ip_geolocation',
            'source_host' => parse_url($url, PHP_URL_HOST),
            'accuracy' => 'approximate_ip',
            'ip_address' => $this->firstValue($payload, ['ip', 'query']) ?: $fallbackIp,
            'country' => $country,
            'region' => $region,
            'city' => $city,
            'latitude' => $latitude !== null ? round((float) $latitude, 6) : null,
            'longitude' => $longitude !== null ? round((float) $longitude, 6) : null,
            'timezone' => $this->firstValue($payload, ['timezone', 'time_zone']),
            'accuracy_radius_km' => $this->firstValue($payload, ['accuracy_radius', 'accuracyRadius', 'radius']),
        ];

        return array_filter($location, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function firstValue(array $payload, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null && $payload[$key] !== '') {
                return $payload[$key];
            }
        }

        return null;
    }

    protected function isPublicIp($ipAddress)
    {
        return (bool) filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
