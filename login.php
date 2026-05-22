<?php

$root = realpath(__DIR__ . '/..');
$site = isset($_GET['site']) ? trim((string) $_GET['site']) : '';
$site = preg_replace('/[^a-z0-9-]/i', '', $site);

$validSite = false;
$sitePath = '';

if ($root !== false && $site !== '') {
    // Check parent root first
    $candidate = $root . DIRECTORY_SEPARATOR . $site;
    if (is_dir($candidate) && file_exists($candidate . '/wp-config.php')) {
        $sitePath  = realpath($candidate);
        $validSite = true;
    } else {
        // Fallback: search valet symlinks directly
        $home = getenv('HOME') ?: '';
        foreach ([$home . '/.config/valet/Sites', $home . '/.valet/Sites'] as $valetDir) {
            if (!is_dir($valetDir)) continue;
            $link = $valetDir . DIRECTORY_SEPARATOR . $site;
            if (!is_link($link)) continue;
            $real = realpath($link);
            if ($real && is_dir($real) && file_exists($real . '/wp-config.php')) {
                $sitePath  = $real;
                $validSite = true;
                break;
            }
        }
    }
}

if (! $validSite) {
    http_response_code(404);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Site Not Found</title>
    </head>
    <body>
        <p>Site not found.</p>
    </body>
    </html>
    <?php
    exit;
}

$siteUrl = 'http://' . $site . '.test';
$autoLoginUrl = 'http://site-manager.test/auto-login.php?site=' . rawurlencode($site);
$fallbackUrl = $siteUrl . '/wp-login.php?redirect_to=' . urlencode($siteUrl . '/wp-admin/');
$target = $autoLoginUrl;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logging In</title>
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
        }
    </style>
</head>
<body>
    <div class="panel">
        <div id="status">Auto-logging into <?php echo htmlspecialchars($site, ENT_QUOTES, 'UTF-8'); ?>...</div>
        <div id="manual-login" style="display: none; margin-top: 16px;">
            <p style="margin: 0 0 12px; color: #666; font-size: 14px;">Auto-login failed. Click below to login manually:</p>
            <a href="<?php echo htmlspecialchars($fallbackUrl, ENT_QUOTES, 'UTF-8'); ?>" style="display: inline-block; padding: 8px 16px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px;">Go to Login Page</a>
        </div>
    </div>
    <script>
        // Wait for the page to fully load before auto-logging in
        window.addEventListener('load', function() {
            // Add a brief delay to show the loading message
            setTimeout(function() {
                try {
                    // Redirect to auto-login script
                    window.location.href = '<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>';
                } catch (error) {
                    console.error('Auto-login failed:', error);
                    showManualLogin();
                }
            }, 1000); // 1 second delay to show loading message
        });

        // Show manual login option if auto-login fails
        function showManualLogin() {
            document.getElementById('status').textContent = 'Auto-login encountered an issue.';
            document.getElementById('manual-login').style.display = 'block';
        }

        // Fallback: if nothing happens after 5 seconds, show manual option
        setTimeout(function() {
            if (document.getElementById('status').textContent.includes('Auto-logging')) {
                showManualLogin();
            }
        }, 5000);
    </script>
</body>
</html>
