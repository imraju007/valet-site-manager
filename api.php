<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/app.php';

$root = realpath(__DIR__ . '/..');
$db   = new Database(DB_FILE);

function ok(array $extra = []): never {
    echo json_encode(['ok' => true, ...$extra]);
    exit;
}

function fail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$action = trim($_REQUEST['action'] ?? '');
$body   = ($_SERVER['REQUEST_METHOD'] === 'POST')
    ? (json_decode(file_get_contents('php://input'), true) ?? $_POST)
    : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $tok = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['_csrf'] ?? '');
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $tok)) {
        fail('Invalid CSRF token', 403);
    }
}

switch ($action) {

    // ── Sudo pre-flight check ─────────────────────────────────────────────
    case 'check_sudo': {
        [, $code] = WpExecutor::exec('sudo -n true 2>/dev/null', APP_DIR);
        ok(['needs_password' => $code !== 0]);
    }

    // ── Inline command runner ─────────────────────────────────────────────
    case 'run_cmd': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $cmd = trim($body['cmd'] ?? '');
        if ($cmd === '' || strlen($cmd) > 2000) fail('Invalid command');
        [$output, $code] = WpExecutor::exec($cmd, $path);
        ok(['output' => $output, 'exit_code' => $code]);
    }

    // ── Open URL in specific browser app (macOS) ──────────────────────────
    case 'open_browser': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $url = filter_var($body['url'] ?? '', FILTER_VALIDATE_URL);
        if (!$url) fail('Invalid URL');
        $app = preg_replace('/[^a-z]/i', '', strtolower($body['app'] ?? ''));
        $map = [
            'chrome'  => 'Google Chrome',
            'firefox' => 'Firefox',
            'safari'  => 'Safari',
            'arc'     => 'Arc',
            'brave'   => 'Brave Browser',
            'edge'    => 'Microsoft Edge',
        ];
        $u = escapeshellarg($url);
        if ($app === '' || $app === 'default') {
            exec("open {$u} > /dev/null 2>&1 &");
        } elseif (isset($map[$app])) {
            $a = escapeshellarg($map[$app]);
            exec("open -a {$a} {$u} > /dev/null 2>&1 &");
        } else {
            fail('Unknown browser');
        }
        ok();
    }

    // ── Debug log viewer ─────────────────────────────────────────────────
    case 'debug_log': {
        $path = SiteScanner::sitePath($_GET['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $log = $path . '/wp-content/debug.log';
        if (!file_exists($log)) ok(['exists' => false, 'content' => '', 'bytes' => 0, 'total_lines' => 0]);
        $bytes = filesize($log);
        $all   = file($log, FILE_IGNORE_NEW_LINES) ?: [];
        $lines = array_slice($all, -300);
        ok(['exists' => true, 'content' => implode("\n", $lines), 'bytes' => $bytes, 'total_lines' => count($all)]);
    }

    case 'clear_log': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $log = $path . '/wp-content/debug.log';
        if (file_exists($log)) file_put_contents($log, '');
        ok();
    }

    // ── Debug toggle ─────────────────────────────────────────────────────
    case 'debug_status': {
        $path = SiteScanner::sitePath($_GET['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        [$out] = WpExecutor::exec('wp config get WP_DEBUG_LOG', $path);
        $val   = strtolower(trim($out));
        ok(['enabled' => in_array($val, ['1', 'true'], true)]);
    }

    case 'toggle_debug': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path   = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $enable = !empty($body['enable']);
        $flag   = $enable ? 'true' : 'false';
        $steps  = [];
        $cmd1   = 'wp config set WP_DEBUG true --raw';
        [$out1, $c1] = WpExecutor::exec($cmd1, $path);
        $steps[] = ['label' => 'Enable WP_DEBUG', 'ok' => $c1 === 0, 'cmd' => $cmd1, 'output' => trim($out1)];
        $cmd2   = "wp config set WP_DEBUG_LOG {$flag} --raw";
        [$out2, $c2] = WpExecutor::exec($cmd2, $path);
        $steps[] = ['label' => "Set WP_DEBUG_LOG {$flag}", 'ok' => $c2 === 0, 'cmd' => $cmd2, 'output' => trim($out2)];
        ok(['enabled' => $enable, 'code' => $c2, 'steps' => $steps]);
    }

    // ── Terminal launchers (macOS native) ────────────────────────────────
    case 'open_terminal': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $safe   = str_replace(['"', '\\'], ['\\"', '\\\\'], $path);
        $script = "tell application \"Terminal\"\n\tdo script \"cd \\\"$safe\\\"\"\n\tactivate\nend tell";
        exec('osascript -e ' . escapeshellarg($script) . ' > /dev/null 2>&1 &');
        ok();
    }

    case 'open_wpcli': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $safe   = str_replace(['"', '\\'], ['\\"', '\\\\'], $path);
        $script = "tell application \"Terminal\"\n\tdo script \"cd \\\"$safe\\\" && wp shell\"\n\tactivate\nend tell";
        exec('osascript -e ' . escapeshellarg($script) . ' > /dev/null 2>&1 &');
        ok();
    }

    // ── Clean database ────────────────────────────────────────────────────
    case 'clean_db': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $results = [];

        [$msg, $c] = WpExecutor::exec('wp cache flush', $path);
        $results[] = ['label' => 'Flush object cache', 'ok' => $c === 0, 'cmd' => 'wp cache flush', 'msg' => trim($msg)];

        [$msg, $c] = WpExecutor::exec('wp transient delete --all', $path);
        $results[] = ['label' => 'Delete transients', 'ok' => $c === 0, 'cmd' => 'wp transient delete --all', 'msg' => trim($msg)];

        [$ids_str] = WpExecutor::exec('wp post list --post_type=revision --format=ids', $path);
        $ids = trim($ids_str);
        if ($ids !== '' && preg_match('/^\d[\d\s]*$/', $ids)) {
            $count = count(explode(' ', trim($ids)));
            $delCmd = "wp post delete {$ids} --force";
            [$msg, $c] = WpExecutor::exec($delCmd, $path);
            $results[] = ['label' => "Delete {$count} revision(s)", 'ok' => $c === 0, 'cmd' => $delCmd, 'msg' => trim($msg)];
        } else {
            $results[] = ['label' => 'Post revisions', 'ok' => true, 'cmd' => 'wp post list --post_type=revision --format=ids', 'msg' => 'No revisions found'];
        }

        ok(['results' => $results]);
    }

    // ── Tasks CRUD ────────────────────────────────────────────────────────
    case 'get_tasks': {
        $site = preg_replace('/[^a-z0-9_-]/i', '', $_GET['site'] ?? '');
        if ($site === '') fail('Site required');
        ok(['tasks' => $db->getTasks($site)]);
    }

    case 'save_task': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $site = preg_replace('/[^a-z0-9_-]/i', '', $body['site'] ?? '');
        $task = $body['task'] ?? null;
        if ($site === '' || !is_array($task)) fail('Invalid payload');
        try {
            $saved = $db->saveTask($site, $task);
        } catch (RuntimeException $e) {
            fail($e->getMessage(), 404);
        }
        ok(['task' => $saved]);
    }

    case 'delete_task': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $site = preg_replace('/[^a-z0-9_-]/i', '', $body['site'] ?? '');
        $tid  = $body['task_id'] ?? '';
        if ($site === '' || $tid === '') fail('Invalid payload');
        $db->deleteTask($site, $tid);
        ok();
    }

    // ── Delete site ───────────────────────────────────────────────────────
    case 'delete_site': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $siteName = basename($path);
        $steps    = [];

        $sudoPass = trim($body['sudo_pass'] ?? '');

        // Check if site lives in a parked directory (no valet unlink needed)
        $valetCfg    = json_decode(@file_get_contents(getenv('HOME') . '/.config/valet/config.json') ?: '{}', true);
        $parkedPaths = array_filter(array_map('realpath', $valetCfg['paths'] ?? []));
        $isParked    = in_array(realpath(dirname($path)), $parkedPaths, true);

        // Drop WordPress database before removing files
        if (file_exists($path . '/wp-config.php')) {
            [$dbOut, $dbCode] = WpExecutor::exec('wp db drop --yes', $path);
            $steps[] = ['label' => 'Drop database', 'ok' => $dbCode === 0, 'cmd' => 'wp db drop --yes', 'output' => trim($dbOut)];
        }
        // Valet unlink
        if ($isParked) {
            $steps[] = ['label' => 'Valet unlink', 'ok' => true, 'cmd' => 'skipped — parent directory is parked', 'output' => ''];
        } else {
            $unlinkCmd = 'valet unlink ' . escapeshellarg($siteName);
            [$uOut, $uCode] = WpExecutor::exec($unlinkCmd, $path, $sudoPass);
            $steps[] = ['label' => 'Valet unlink', 'ok' => $uCode === 0, 'cmd' => $unlinkCmd, 'output' => trim($uOut)];
        }
        // Remove directory
        function rm_rf(string $dir): void {
            if (!is_dir($dir)) { @unlink($dir); return; }
            foreach (scandir($dir) as $item) {
                if ($item === '.' || $item === '..') continue;
                rm_rf($dir . DIRECTORY_SEPARATOR . $item);
            }
            rmdir($dir);
        }
        rm_rf($path);
        $steps[] = ['label' => 'Remove site files', 'ok' => true, 'cmd' => "rm -rf {$path}", 'output' => ''];
        ok(['steps' => $steps]);
    }

    // ── Create site ───────────────────────────────────────────────────────
    case 'create_site': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $name      = preg_replace('/[^a-z0-9-]/', '', strtolower($body['name'] ?? ''));
        $type      = in_array($body['type'] ?? '', ['wordpress','static']) ? $body['type'] : 'wordpress';
        $parentDir = trim($body['parent_dir'] ?? $root);
        $plugins   = array_filter(array_map('trim', explode("\n", $body['plugins'] ?? '')));
        $themes    = array_filter(array_map('trim', explode("\n", $body['themes']  ?? '')));
        $structure = trim($body['structure'] ?? '');

        // Fall back to saved new_site_default when fields are left empty
        $nsd = $db->getSettings()['new_site_default'] ?? [];
        if (empty($plugins) && !empty($nsd['plugins']))            $plugins   = (array)$nsd['plugins'];
        if (empty($themes)  && !empty($nsd['themes']))             $themes    = (array)$nsd['themes'];
        if ($structure === '' && !empty($nsd['folder_structure'])) $structure = json_encode($nsd['folder_structure']);
        if (($body['parent_dir'] ?? '') === '') {
            $savedDir = $type === 'wordpress'
                ? trim($nsd['wp_parent_dir']     ?? '')
                : trim($nsd['static_parent_dir'] ?? '');
            if ($savedDir !== '' && is_dir($savedDir)) $parentDir = $savedDir;
        }

        if ($name === '' || strlen($name) > 60) fail('Invalid site name');
        $parentDir = realpath($parentDir) ?: $root;
        $sitePath  = $parentDir . DIRECTORY_SEPARATOR . $name;

        if (is_dir($sitePath)) fail('Site already exists', 409);

        $sudoPass = trim($body['sudo_pass'] ?? '');

        // Determine if parent is already a Valet-parked path (no sudo link needed)
        $valetCfg    = json_decode(@file_get_contents(getenv('HOME') . '/.config/valet/config.json') ?: '{}', true);
        $parkedPaths = array_filter(array_map('realpath', $valetCfg['paths'] ?? []));
        $isParked    = in_array(realpath($parentDir), $parkedPaths, true);

        $steps = [];
        $step  = function(string $label, bool $ok, string $cmd = '', string $out = '') use (&$steps): void {
            $steps[] = ['label' => $label, 'ok' => $ok, 'cmd' => $cmd, 'output' => trim($out)];
        };

        if (!mkdir($sitePath, 0755, true)) {
            $step('Create directory', false, "mkdir -p {$sitePath}", 'mkdir failed');
            ok(['ok' => false, 'error' => 'mkdir failed', 'steps' => $steps]);
        }
        $step('Create directory', true, "mkdir -p {$sitePath}");

        if ($type === 'static') {
            // Build folder structure from JSON or default
            if ($structure !== '') {
                $tree = json_decode($structure, true);
                if (!is_array($tree)) {
                    $step('Parse folder structure', false, '', 'Invalid JSON');
                    ok(['ok' => false, 'error' => 'Invalid JSON structure', 'steps' => $steps]);
                }
                function build_tree(string $base, array $tree): void {
                    foreach ($tree as $itemName => $val) {
                        $p = $base . DIRECTORY_SEPARATOR . $itemName;
                        if (is_array($val)) { @mkdir($p, 0755, true); build_tree($p, $val); }
                        else { @file_put_contents($p, ''); }
                    }
                }
                build_tree($sitePath, $tree);
                $step('Create folder structure', true, 'mkdir (from JSON)');
            } else {
                $siteName = ucwords(str_replace('-', ' ', $name));
                file_put_contents($sitePath . '/index.php', "<?php\necho 'Hello, {$siteName}!';\n");
                $step('Create index.php', true, "echo '<?php …' > index.php");
            }
            // Valet link (only needed when parent is not a parked directory)
            if ($isParked) {
                $step('Valet link', true, 'skipped — parent directory is parked', '');
            } else {
                $vlinkCmd = 'valet link ' . escapeshellarg($name);
                [$out, $code] = WpExecutor::exec($vlinkCmd, $sitePath, $sudoPass);
                $step('Valet link', $code === 0, $vlinkCmd, trim($out));
            }
            ok(['ok' => true, 'steps' => $steps, 'url' => 'http://' . $name . '.test']);
        }

        // WordPress flow — read credential/DB defaults from settings
        $sDb        = $db->getSettings();
        $wa         = $sDb['wp_admin_config'] ?? [];
        $dc         = $sDb['db_config']       ?? [];
        $adminUser  = trim($wa['user']     ?? '') ?: 'admin';
        $adminPass  = trim($wa['password'] ?? '') ?: 'admin';
        $adminEmail = trim($wa['email']    ?? '');
        $adminEmail = $adminEmail !== ''
            ? str_replace('{sitename}', $name, $adminEmail)
            : 'admin@' . $name . '.test';
        $dbHost     = trim($dc['host']     ?? '') ?: '127.0.0.1';
        $dbUser     = trim($dc['user']     ?? '') ?: 'root';
        $dbPass     = trim($dc['password'] ?? '');

        $dbName = str_replace('-', '_', $name);
        $url    = 'http://' . $name . '.test';

        [$out, $code] = WpExecutor::exec('wp core download', $sitePath);
        $step('Download WordPress', $code === 0, 'wp core download', $out);
        if ($code !== 0) ok(['ok' => false, 'error' => 'Core download failed', 'steps' => $steps]);

        $cfgCmd = sprintf(
            'wp config create --dbname=%s --dbuser=%s --dbpass=%s --dbhost=%s --skip-check',
            escapeshellarg($dbName), escapeshellarg($dbUser),
            escapeshellarg($dbPass), escapeshellarg($dbHost)
        );
        [$out, $code] = WpExecutor::exec($cfgCmd, $sitePath);
        $step('Create wp-config.php', $code === 0, $cfgCmd, $out);
        if ($code !== 0) ok(['ok' => false, 'error' => 'Config failed', 'steps' => $steps]);

        [$out, $code] = WpExecutor::exec('wp db create', $sitePath);
        $step('Create database', $code === 0, 'wp db create', $out);
        if ($code !== 0) ok(['ok' => false, 'error' => 'DB creation failed', 'steps' => $steps]);

        $installCmd = sprintf(
            'wp core install --url=%s --title=%s --admin_user=%s --admin_password=%s --admin_email=%s --skip-email',
            escapeshellarg($url), escapeshellarg(ucwords(str_replace('-',' ',$name))),
            escapeshellarg($adminUser), escapeshellarg($adminPass), escapeshellarg($adminEmail)
        );
        [$out, $code] = WpExecutor::exec($installCmd, $sitePath);
        $step('Install WordPress', $code === 0, $installCmd, $out);
        if ($code !== 0) ok(['ok' => false, 'error' => 'Install failed', 'steps' => $steps]);

        foreach ($plugins as $plugin) {
            $pCmd = 'wp plugin install ' . escapeshellarg($plugin) . ' --activate';
            [$out, $code] = WpExecutor::exec($pCmd, $sitePath);
            $step('Plugin: ' . basename($plugin), $code === 0, $pCmd, trim($out));
        }
        foreach ($themes as $theme) {
            $tCmd = 'wp theme install ' . escapeshellarg($theme);
            [$out, $code] = WpExecutor::exec($tCmd, $sitePath);
            $step('Theme: ' . basename($theme), $code === 0, $tCmd, trim($out));
        }

        // Valet link (only needed when parent is not a parked directory)
        if ($isParked) {
            $step('Valet link', true, 'skipped — parent directory is parked', '');
        } else {
            $vlinkCmd2 = 'valet link ' . escapeshellarg($name);
            [$out, $code] = WpExecutor::exec($vlinkCmd2, $sitePath, $sudoPass);
            $step('Valet link', $code === 0, $vlinkCmd2, trim($out));
        }

        ok(['ok' => true, 'steps' => $steps, 'url' => $url, 'admin_url' => APP_URL . '/login.php?site='.rawurlencode($name)]);
    }

    // ── Open in Finder (macOS) ────────────────────────────────────────────
    case 'open_finder': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        exec('open ' . escapeshellarg($path) . ' > /dev/null 2>&1 &');
        ok();
    }

    // ── Settings ──────────────────────────────────────────────────────────
    case 'get_settings': {
        ok(['settings' => $db->getSettings()]);
    }

    case 'save_settings': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $section = trim($body['section'] ?? '');
        $data    = $body['data'] ?? null;
        if ($section === '' || !is_array($data)) fail('Invalid payload');
        try {
            $db->saveSettings($section, $data);
        } catch (InvalidArgumentException $e) {
            fail($e->getMessage());
        }
        ok();
    }

    // ── Native macOS file/directory picker ────────────────────────────────
    case 'pick_path': {
        $type   = ($_GET['type'] ?? 'folder') === 'file' ? 'file' : 'folder';
        $prompt = addslashes($type === 'folder' ? 'Select a directory:' : 'Select a file:');
        $script = $type === 'folder'
            ? "POSIX path of (choose folder with prompt \"{$prompt}\")"
            : "POSIX path of (choose file with prompt \"{$prompt}\")";
        $desc = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
        $proc = @proc_open(['osascript', '-e', $script], $desc, $pipes);
        if (!is_resource($proc)) fail('osascript unavailable');
        fclose($pipes[0]);
        $out  = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]); fclose($pipes[2]);
        $code = proc_close($proc);
        if ($code !== 0 || $out === '') ok(['path' => null, 'cancelled' => true]);
        ok(['path' => rtrim($out, "/\n")]);
    }

    case 'toggle_ssl': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        set_time_limit(0);
        $name     = trim($body['name']     ?? '');
        $secure   = !empty($body['secure']);
        $sudoPass = trim($body['sudo_pass'] ?? '');
        if ($name === '') fail('Missing name');
        $cmd = $secure
            ? 'valet secure '   . escapeshellarg($name)
            : 'valet unsecure ' . escapeshellarg($name);
        [$out, $code] = WpExecutor::exec($cmd, APP_DIR, $sudoPass);
        // valet secure restarts PHP-FPM/nginx — if we get here, it completed before restart
        if ($code !== 0) fail($out ?: ($secure ? 'valet secure failed' : 'valet unsecure failed'));
        ok(['output' => $out]);
    }

    case 'toggle_archive': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $name    = trim($body['name']    ?? '');
        $archive = (bool)($body['archive'] ?? true);
        if ($name === '') fail('Missing name');
        if ($archive) $db->archiveSite($name);
        else          $db->unarchiveSite($name);
        ok();
    }

    default:
        fail('Unknown action', 404);
}
