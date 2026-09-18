<?php
/**
 * includes/security.php - light helpers used by API endpoints.
 *
 * Provides:
 *   - api_session_boot()   : start a session if none is running.
 *   - api_require_login()  : 401 if not logged in (used once auth is wired).
 *   - api_assert_safe_url(): SSRF guard. Blocks schemes other than http/https
 *                            and private/loopback/link-local IP ranges.
 *                            Returns ['ok'=>true, 'ip'=>...] or
 *                            ['ok'=>false, 'reason'=>...].
 *
 * Drop into any endpoint like:
 *   require_once __DIR__ . '/../includes/security.php';
 *   api_session_boot();
 *   // api_require_login();   // uncomment when auth is wired
 *   $chk = api_assert_safe_url($_POST['url'] ?? '');
 *   if (!$chk['ok']) { echo json_encode(['success'=>false,'message'=>$chk['reason']]); exit; }
 */

if (!function_exists('api_session_boot')) {
    function api_session_boot(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Match Auth.php: cookies only, strict mode
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1');
            session_start();
        }
    }
}

if (!function_exists('api_require_login')) {
    function api_require_login(): void {
        api_session_boot();
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit;
        }
    }
}

if (!function_exists('api_assert_safe_url')) {
    function api_assert_safe_url(string $url): array {
        $url = trim($url);
        if ($url === '') {
            return ['ok' => false, 'reason' => 'URL is required'];
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return ['ok' => false, 'reason' => 'Invalid URL format'];
        }
        $parts = parse_url($url);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return ['ok' => false, 'reason' => 'Invalid URL'];
        }
        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['ok' => false, 'reason' => 'Only http(s) URLs are allowed'];
        }
        $host = $parts['host'];
        // Resolve host → IP; reject if it lands in a private/loopback range.
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['ok' => false, 'reason' => 'Cannot resolve host'];
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['ok' => false, 'reason' => 'URL points to a private or reserved IP range'];
        }
        return ['ok' => true, 'ip' => $ip];
    }
}
