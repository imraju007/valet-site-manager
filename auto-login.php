<?php
/**
 * WordPress Auto-Login Script
 * Automatically logs in as admin and redirects to wp-admin
 */

// Get the site parameter
$site = isset($_GET['site']) ? trim((string) $_GET['site']) : '';
$site = preg_replace('/[^a-z0-9-]/i', '', $site);

if (empty($site)) {
    die('Site parameter is required');
}

// Locate the WordPress site path
$root     = realpath(__DIR__ . '/..');
$sitePath = '';
$candidate = $root . DIRECTORY_SEPARATOR . $site;
if (is_dir($candidate) && file_exists($candidate . '/wp-config.php')) {
    $sitePath = realpath($candidate);
} else {
    $home = getenv('HOME') ?: '';
    foreach ([$home . '/.config/valet/Sites', $home . '/.valet/Sites'] as $valetDir) {
        if (!is_dir($valetDir)) continue;
        $link = $valetDir . DIRECTORY_SEPARATOR . $site;
        if (!is_link($link)) continue;
        $real = realpath($link);
        if ($real && is_dir($real) && file_exists($real . '/wp-config.php')) {
            $sitePath = $real;
            break;
        }
    }
}

if ($sitePath === '') {
    die('Invalid WordPress site');
}

// Set the correct host so WordPress sets cookies for the target domain
$targetHost = $site . '.test';
$_SERVER['HTTP_HOST'] = $targetHost;
$_SERVER['SERVER_NAME'] = $targetHost;

// Include WordPress core
define('WP_USE_THEMES', false);
require_once($sitePath . '/wp-load.php');

// Check if user is already logged in
if (is_user_logged_in()) {
    wp_redirect(admin_url());
    exit;
}

// Auto-login credentials — read from site-manager settings, fall back to admin/admin
$username = 'admin';
$password = 'admin';
require_once __DIR__ . '/app.php';
$hubDb = new Database(DB_FILE);
$wa    = $hubDb->getSettings()['wp_admin_config'] ?? [];
if (!empty($wa['user']))     $username = $wa['user'];
if (!empty($wa['password'])) $password = $wa['password'];

// Prepare credentials
$credentials = array(
    'user_login'    => $username,
    'user_password' => $password,
    'remember'      => true
);

// Attempt to sign in
$user = wp_signon($credentials, false);

if (is_wp_error($user)) {
    // If login fails, redirect to manual login
    $login_url = wp_login_url(admin_url());
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Auto-Login Failed</title>
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                background: #f7f3eb;
                color: #1f1d19;
            }
            .panel {
                padding: 24px 28px;
                border: 1px solid #e7dcc9;
                border-radius: 18px;
                background: #fffdf9;
                box-shadow: 0 16px 36px rgba(74, 53, 33, 0.12);
                text-align: center;
            }
            .error { color: #d63638; margin-bottom: 16px; }
            .button {
                display: inline-block;
                padding: 10px 16px;
                background: #0073aa;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 12px;
            }
        </style>
    </head>
    <body>
        <div class="panel">
            <div class="error">Auto-login failed: <?php echo esc_html($user->get_error_message()); ?></div>
            <p>Please log in manually:</p>
            <a href="<?php echo esc_url($login_url); ?>" class="button">Go to Login Page</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Success! User is now logged in
// Redirect to admin dashboard
wp_redirect(admin_url());
exit;
