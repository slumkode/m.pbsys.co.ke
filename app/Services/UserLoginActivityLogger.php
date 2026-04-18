<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\UserLoginActivity;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class UserLoginActivityLogger
{
    const SESSION_KEY = 'auth.login_activity_id';
    const LAST_URL_KEY = 'auth.last_url';

    public function recordLogin(Request $request, User $user, $remembered = false)
    {
        if (! $this->activityTableReady()) {
            return null;
        }

        $now = now();
        $previousUrl = $this->latestSafeUrlForUser($user, $request);
        $activity = UserLoginActivity::create(array_merge(
            $this->requestProfile($request),
            [
                'user_id' => $user->id,
                'session_id' => $this->sessionId($request),
                'remembered' => (bool) $remembered,
                'login_at' => $now,
                'last_seen_at' => $now,
                'previous_url' => $previousUrl,
            ]
        ));

        $this->storeActivityInSession($request, $activity);

        $this->writeAudit($request, $user, 'logged_in', [], [
            'login_activity_id' => $activity->id,
            'remembered' => (bool) $remembered,
            'ip_address' => $activity->ip_address,
            'browser' => $activity->browser,
            'platform' => $activity->platform,
            'device_type' => $activity->device_type,
            'previous_page' => $this->safeDisplayUrl($previousUrl),
        ], $activity);

        return $activity;
    }

    public function recordLogout(Request $request, User $user = null)
    {
        if (! $user || ! $this->activityTableReady()) {
            return;
        }

        $activity = $this->currentActivity($request, $user);
        $now = now();

        if ($activity) {
            $activity->logout_at = $now;
            $activity->last_seen_at = $now;
            $activity->save();
        }

        $this->writeAudit($request, $user, 'logged_out', [
            'login_activity_id' => $activity ? $activity->id : null,
            'last_page' => $activity ? $this->safeDisplayUrl($activity->last_url) : null,
            'ip_address' => $activity ? $activity->ip_address : $request->ip(),
        ], [
            'logout_at' => $now->toDateTimeString(),
        ], $activity);

        if ($request->hasSession()) {
            $request->session()->forget(self::SESSION_KEY);
        }
    }

    public function ensureActivity(Request $request, User $user)
    {
        if (! $this->activityTableReady()) {
            return null;
        }

        $activity = $this->currentActivity($request, $user);

        if ($activity) {
            $this->recordIpIfChanged($request, $user, $activity);
            return $activity;
        }

        $now = now();
        $activity = UserLoginActivity::create(array_merge(
            $this->requestProfile($request),
            [
                'user_id' => $user->id,
                'session_id' => $this->sessionId($request),
                'remembered' => true,
                'login_at' => $now,
                'last_seen_at' => $now,
                'previous_url' => $this->latestSafeUrlForUser($user, $request),
            ]
        ));

        $this->storeActivityInSession($request, $activity);

        $this->writeAudit($request, $user, 'session_resumed', [], [
            'login_activity_id' => $activity->id,
            'ip_address' => $activity->ip_address,
            'browser' => $activity->browser,
            'platform' => $activity->platform,
            'device_type' => $activity->device_type,
        ], $activity);

        return $activity;
    }

    public function recordPageVisit(Request $request, User $user)
    {
        $activity = $this->ensureActivity($request, $user);

        if (! $activity || ! $this->shouldRememberUrl($request)) {
            return;
        }

        $url = $request->fullUrl();
        $previousUrl = $activity->last_url;
        $now = now();

        $activity->last_seen_at = $now;
        $activity->last_route_name = $this->routeName($request);

        if ($previousUrl !== $url) {
            $activity->previous_url = $previousUrl;
            $activity->last_url = $url;
        }

        $activity->save();

        if ($request->hasSession()) {
            $request->session()->put(self::LAST_URL_KEY, $url);
        }
    }

    public function recordBrowserLocation(Request $request, User $user, $latitude, $longitude, $accuracy = null)
    {
        $activity = $this->ensureActivity($request, $user);

        if (! $activity) {
            return null;
        }

        $latitude = round((float) $latitude, 7);
        $longitude = round((float) $longitude, 7);
        $accuracy = $accuracy !== null ? round((float) $accuracy, 2) : null;
        $oldLatitude = $activity->latitude !== null ? (float) $activity->latitude : null;
        $oldLongitude = $activity->longitude !== null ? (float) $activity->longitude : null;
        $distanceKm = $oldLatitude !== null && $oldLongitude !== null
            ? $this->distanceKm($oldLatitude, $oldLongitude, $latitude, $longitude)
            : null;
        $hasChanged = $distanceKm === null || $distanceKm >= 0.2;

        $activity->last_seen_at = now();

        if ($hasChanged) {
            $activity->previous_latitude = $activity->latitude;
            $activity->previous_longitude = $activity->longitude;
            $activity->previous_location_captured_at = $activity->location_captured_at;
            $activity->latitude = $latitude;
            $activity->longitude = $longitude;
            $activity->location_accuracy = $accuracy;
            $activity->location_captured_at = now();
            $activity->location_changed_at = now();
            $activity->save();

            $this->writeAudit($request, $user, $distanceKm === null ? 'location_recorded' : 'location_changed', [
                'latitude' => $oldLatitude,
                'longitude' => $oldLongitude,
                'captured_at' => optional($activity->previous_location_captured_at)->toDateTimeString(),
            ], [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy_meters' => $accuracy,
                'distance_km' => $distanceKm !== null ? round($distanceKm, 3) : null,
                'captured_at' => optional($activity->location_captured_at)->toDateTimeString(),
            ], $activity);
        } else {
            $activity->location_accuracy = $accuracy;
            $activity->location_captured_at = now();
            $activity->save();
        }

        return $activity;
    }

    public function preferredRedirectUrl(User $user, Request $request, $fallback = '/')
    {
        $sessionUrl = $request->hasSession() ? $request->session()->get(self::LAST_URL_KEY) : null;
        $safeSessionUrl = $this->safeInternalRedirectUrl($sessionUrl, $request);

        if ($safeSessionUrl) {
            return $safeSessionUrl;
        }

        $latestUrl = $this->latestSafeUrlForUser($user, $request);

        return $latestUrl ?: $fallback;
    }

    protected function currentActivity(Request $request, User $user)
    {
        if (! $this->activityTableReady()) {
            return null;
        }

        $activityId = $request->hasSession() ? (int) $request->session()->get(self::SESSION_KEY) : 0;
        $sessionId = $this->sessionId($request);

        if ($activityId > 0) {
            $activity = UserLoginActivity::where('user_id', $user->id)->whereKey($activityId)->first();

            if ($activity) {
                return $activity;
            }
        }

        if ($sessionId) {
            $activity = UserLoginActivity::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->whereNull('logout_at')
                ->orderBy('last_seen_at', 'desc')
                ->first();

            if ($activity) {
                $this->storeActivityInSession($request, $activity);
                return $activity;
            }
        }

        return null;
    }

    protected function recordIpIfChanged(Request $request, User $user, UserLoginActivity $activity)
    {
        $currentIp = $request->ip();

        if (! $currentIp || $activity->ip_address === $currentIp) {
            return;
        }

        $previousIp = $activity->ip_address;
        $activity->previous_ip_address = $previousIp;
        $activity->ip_address = $currentIp;
        $activity->ip_changed_at = now();
        $activity->last_seen_at = now();
        $activity->save();

        if ($previousIp) {
            $this->writeAudit($request, $user, 'ip_changed', [
                'ip_address' => $previousIp,
            ], [
                'ip_address' => $currentIp,
            ], $activity);
        }
    }

    protected function writeAudit(Request $request, User $user, $action, array $oldValues, array $newValues, UserLoginActivity $activity = null)
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $payload = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => $action,
            'auditable_type' => 'Login Activity',
            'auditable_id' => $activity ? $activity->id : null,
            'auditable_label' => $user->name ?: $user->email,
            'page_name' => 'Authentication',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
        ];

        if ($activity && Schema::hasColumn('audit_logs', 'login_activity_id')) {
            $payload['login_activity_id'] = $activity->id;
        }

        AuditLog::create($payload);
    }

    protected function latestSafeUrlForUser(User $user, Request $request)
    {
        if (! $this->activityTableReady()) {
            return null;
        }

        $currentActivityId = $request->hasSession() ? (int) $request->session()->get(self::SESSION_KEY) : 0;
        $query = UserLoginActivity::where('user_id', $user->id)
            ->whereNotNull('last_url')
            ->orderBy('last_seen_at', 'desc');

        if ($currentActivityId > 0) {
            $query->where('id', '!=', $currentActivityId);
        }

        foreach ($query->limit(5)->get() as $activity) {
            $safeUrl = $this->safeInternalRedirectUrl($activity->last_url, $request);

            if ($safeUrl) {
                return $safeUrl;
            }
        }

        return null;
    }

    protected function safeInternalRedirectUrl($url, Request $request)
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $path = ltrim(parse_url($url, PHP_URL_PATH) ?: '', '/');
            return $this->isAuthUtilityPath($path) ? null : $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host']) || strcasecmp($parts['host'], $request->getHost()) !== 0) {
            return null;
        }

        $path = ltrim($parts['path'] ?? '', '/');

        return $this->isAuthUtilityPath($path) ? null : $url;
    }

    protected function shouldRememberUrl(Request $request)
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return false;
        }

        return ! $this->isAuthUtilityPath(trim($request->path(), '/'));
    }

    protected function isAuthUtilityPath($path)
    {
        $path = trim((string) $path, '/');

        if ($path === '') {
            return false;
        }

        foreach (['login', 'logout', 'password', 'user-location'] as $blockedPath) {
            if ($path === trim($blockedPath, '/') || strpos($path, trim($blockedPath, '/').'/') === 0) {
                return true;
            }
        }

        return false;
    }

    protected function requestProfile(Request $request)
    {
        $userAgent = (string) $request->userAgent();

        return [
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'browser' => $this->browserName($userAgent),
            'platform' => $this->platformName($userAgent),
            'device_type' => $this->deviceType($userAgent),
        ];
    }

    protected function browserName($userAgent)
    {
        if (stripos($userAgent, 'Edg/') !== false || stripos($userAgent, 'Edge/') !== false) {
            return 'Microsoft Edge';
        }

        if (stripos($userAgent, 'OPR/') !== false || stripos($userAgent, 'Opera') !== false) {
            return 'Opera';
        }

        if (stripos($userAgent, 'Chrome/') !== false) {
            return 'Chrome';
        }

        if (stripos($userAgent, 'Firefox/') !== false) {
            return 'Firefox';
        }

        if (stripos($userAgent, 'Safari/') !== false) {
            return 'Safari';
        }

        if (stripos($userAgent, 'MSIE') !== false || stripos($userAgent, 'Trident/') !== false) {
            return 'Internet Explorer';
        }

        return 'Unknown';
    }

    protected function platformName($userAgent)
    {
        if (stripos($userAgent, 'Windows') !== false) {
            return 'Windows';
        }

        if (stripos($userAgent, 'Android') !== false) {
            return 'Android';
        }

        if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
            return 'iOS';
        }

        if (stripos($userAgent, 'Macintosh') !== false || stripos($userAgent, 'Mac OS') !== false) {
            return 'macOS';
        }

        if (stripos($userAgent, 'Linux') !== false) {
            return 'Linux';
        }

        return 'Unknown';
    }

    protected function deviceType($userAgent)
    {
        if (stripos($userAgent, 'iPad') !== false || stripos($userAgent, 'Tablet') !== false) {
            return 'Tablet';
        }

        if (stripos($userAgent, 'Mobile') !== false || stripos($userAgent, 'Android') !== false || stripos($userAgent, 'iPhone') !== false) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    protected function routeName(Request $request)
    {
        $route = $request->route();

        return $route ? $route->getName() : null;
    }

    protected function sessionId(Request $request)
    {
        return $request->hasSession() ? $request->session()->getId() : null;
    }

    protected function storeActivityInSession(Request $request, UserLoginActivity $activity)
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->put(self::SESSION_KEY, $activity->id);

        if ($activity->last_url) {
            $request->session()->put(self::LAST_URL_KEY, $activity->last_url);
        }
    }

    protected function activityTableReady()
    {
        return Schema::hasTable('user_login_activities');
    }

    protected function safeDisplayUrl($url)
    {
        return $url ?: null;
    }

    protected function distanceKm($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
