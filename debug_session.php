<?php
// Session调试页面
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 配置 session
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;

// 获取请求的来源
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
$parsedOrigin = parse_url($origin);
$cookieDomain = $parsedOrigin['host'] ?? '';
$cookieDomain = preg_replace('/:\d+$/', '', $cookieDomain);

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $cookieDomain,
    'secure' => false,
    'httponly' => true,
    'samesite' => 'lax'
]);

session_start();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius:5px; }
        .section h3 { margin-top: 0; color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius:5px; cursor: pointer; margin-right: 10px; }
        .button:hover { background: #0056b3; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Session Debug Page</h1>

    <div class="section">
        <h3>PHP Session Configuration</h3>
        <pre><?php echo json_encode([
            'session.save_path' => ini_get('session.save_path'),
            'session.name' => ini_get('session.name'),
            'session.gc_probability' => ini_get('session.gc_probability'),
            'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
            'session.cookie_path' => ini_get('session.cookie_path'),
            'session.cookie_domain' => ini_get('session.cookie_domain'),
            'session.cookie_secure' => ini_get('session.cookie_secure'),
            'session.cookie_httponly' => ini_get('session.cookie_httponly'),
            'session.cookie_samesite' => ini_get('session.cookie_samesite'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
    </div>

    <div class="section">
        <h3>Session ID</h3>
        <pre><?php echo session_id(); ?></pre>
    </div>

    <div class="section">
        <h3>Session Data</h3>
        <pre><?php echo json_encode($_SESSION, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
        <?php if (empty($_SESSION)): ?>
        <p class="error">⚠️ WARNING: Session is empty! This means session is not persisting.</p>
        <?php else: ?>
        <p class="success">✓ Session has data</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Cookies</h3>
        <pre><?php echo json_encode($_COOKIE, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
    </div>

    <div class="section">
        <h3>Server Info</h3>
        <pre><?php echo json_encode([
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
            'HTTPS' => $_SERVER['HTTPS'] ?? '',
            'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? '',
            'HTTP_ORIGIN' => $_SERVER['HTTP_ORIGIN'] ?? '',
            'Cookie Domain Used' => $cookieDomain,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
    </div>

    <div class="section">
        <h3>Test Actions</h3>
        <form method="post">
            <button type="submit" name="action" value="set" class="button">Set Test Session</button>
            <button type="submit" name="action" value="clear" class="button" style="background: #dc3545;">Clear Session</button>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if ($_POST['action'] === 'set') {
                $_SESSION['test'] = 'test_value_' . time();
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = 'test_user';
                error_log("DEBUG: Session set in debug_session.php");
                error_log("Session ID: " . session_id());
                error_log("Session Data: " . json_encode($_SESSION));
                echo '<p class="success">✓ Session data set! Refresh to see changes.</p>';
            } else if ($_POST['action'] === 'clear') {
                session_destroy();
                echo '<p class="error">✓ Session destroyed! Refresh to see changes.</p>';
            }
        }
        ?>
    </div>
</body>
</html>
