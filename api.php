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
        ok(['needs_password' => $platform->needsSudoPassword()]);
    }

    // ── Inline command runner ─────────────────────────────────────────────
    case 'run_cmd': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $cmd = trim($body['cmd'] ?? '');
        if ($cmd === '' || strlen($cmd) > 2000) fail('Invalid command');
        [$output, $code] = $platform->exec($cmd, $path);
        ok(['output' => $output, 'exit_code' => $code]);
    }

    // ── Open URL in browser ───────────────────────────────────────────────
    case 'open_browser': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $url = filter_var($body['url'] ?? '', FILTER_VALIDATE_URL);
        if (!$url) fail('Invalid URL');
        $app = preg_replace('/[^a-z]/i', '', strtolower($body['app'] ?? ''));
        $platform->openInBrowser($url, $app);
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

    // ── Batch log size poll ───────────────────────────────────────────────
    case 'log_sizes': {
        $names = array_filter(array_map('trim', explode(',', $_GET['sites'] ?? '')));
        $sizes = [];
        foreach ($names as $name) {
            $path = SiteScanner::sitePath($name, $root);
            if (!$path) continue;
            $log = $path . '/wp-content/debug.log';
            $sizes[$name] = file_exists($log) ? (int)filesize($log) : 0;
        }
        ok(['sizes' => $sizes]);
    }

    // ── Debug toggle ─────────────────────────────────────────────────────
    case 'debug_status': {
        $path = SiteScanner::sitePath($_GET['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        [$out] = $platform->exec('wp config get WP_DEBUG_LOG', $path);
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
        [$out1, $c1] = $platform->exec($cmd1, $path);
        $steps[] = ['label' => 'Enable WP_DEBUG', 'ok' => $c1 === 0, 'cmd' => $cmd1, 'output' => trim($out1)];
        $cmd2   = "wp config set WP_DEBUG_LOG {$flag} --raw";
        [$out2, $c2] = $platform->exec($cmd2, $path);
        $steps[] = ['label' => "Set WP_DEBUG_LOG {$flag}", 'ok' => $c2 === 0, 'cmd' => $cmd2, 'output' => trim($out2)];
        ok(['enabled' => $enable, 'code' => $c2, 'steps' => $steps]);
    }

    // ── Terminal launchers ────────────────────────────────────────────────
    case 'open_terminal': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $platform->openTerminal($path);
        ok();
    }

    case 'open_wpcli': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $platform->openWpCli($path);
        ok();
    }

    // ── Clean database ────────────────────────────────────────────────────
    case 'clean_db': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $results = [];

        [$msg, $c] = $platform->exec('wp cache flush', $path);
        $results[] = ['label' => 'Flush object cache', 'ok' => $c === 0, 'cmd' => 'wp cache flush', 'msg' => trim($msg)];

        [$msg, $c] = $platform->exec('wp transient delete --all', $path);
        $results[] = ['label' => 'Delete transients', 'ok' => $c === 0, 'cmd' => 'wp transient delete --all', 'msg' => trim($msg)];

        [$ids_str] = $platform->exec('wp post list --post_type=revision --format=ids', $path);
        $ids = trim($ids_str);
        if ($ids !== '' && preg_match('/^\d[\d\s]*$/', $ids)) {
            $count = count(explode(' ', trim($ids)));
            $delCmd = "wp post delete {$ids} --force";
            [$msg, $c] = $platform->exec($delCmd, $path);
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

        // Check if site lives in a parked directory (no unlink needed)
        $valetCfg    = json_decode(@file_get_contents($platform->valetConfigFile()) ?: '{}', true);
        $parkedPaths = array_filter(array_map('realpath', $valetCfg['paths'] ?? []));
        $isParked    = in_array(realpath(dirname($path)), $parkedPaths, true);

        // Drop WordPress database before removing files
        if (file_exists($path . '/wp-config.php')) {
            [$dbOut, $dbCode] = $platform->exec('wp db drop --yes', $path);
            $steps[] = ['label' => 'Drop database', 'ok' => $dbCode === 0, 'cmd' => 'wp db drop --yes', 'output' => trim($dbOut)];
        }
        // Valet/Herd unlink
        if ($isParked) {
            $steps[] = ['label' => 'Unlink', 'ok' => true, 'cmd' => 'skipped — parent directory is parked', 'output' => ''];
        } else {
            $unlinkCmd = $platform->valetBin() . ' unlink ' . escapeshellarg($siteName);
            [$uOut, $uCode] = $platform->exec($unlinkCmd, $path, $sudoPass);
            $steps[] = ['label' => 'Unlink', 'ok' => $uCode === 0, 'cmd' => $unlinkCmd, 'output' => trim($uOut)];
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

        // Determine if parent is already a parked path (no link step needed)
        $valetCfg    = json_decode(@file_get_contents($platform->valetConfigFile()) ?: '{}', true);
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
            // Link (only needed when parent is not a parked directory)
            if ($isParked) {
                $step('Link', true, 'skipped — parent directory is parked', '');
            } else {
                $vlinkCmd = $platform->valetBin() . ' link ' . escapeshellarg($name);
                [$out, $code] = $platform->exec($vlinkCmd, $sitePath, $sudoPass);
                $step('Link', $code === 0, $vlinkCmd, trim($out));
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

        [$out, $code] = $platform->exec('wp core download', $sitePath);
        $step('Download WordPress', $code === 0, 'wp core download', $out);
        if ($code !== 0) ok(['ok' => false, 'error' => 'Core download failed', 'steps' => $steps]);

        $cfgCmd = sprintf(
            'wp config create --dbname=%s --dbuser=%s --dbpass=%s --dbhost=%s --skip-check',
            escapeshellarg($dbName), escapeshellarg($dbUser),
            escapeshellarg($dbPass), escapeshellarg($dbHost)
        );
        [$out, $code] = $platform->exec($cfgCmd, $sitePath);
        $step('Create wp-config.php', $code === 0, $cfgCmd, $out);
        if ($code !== 0) ok(['ok' => false, 'error' => 'Config failed', 'steps' => $steps]);

        [$out, $code] = $platform->exec('wp db create', $sitePath);
        $step('Create database', $code === 0, 'wp db create', $out);
        if ($code !== 0) ok(['ok' => false, 'error' => 'DB creation failed', 'steps' => $steps]);

        $installCmd = sprintf(
            'wp core install --url=%s --title=%s --admin_user=%s --admin_password=%s --admin_email=%s --skip-email',
            escapeshellarg($url), escapeshellarg(ucwords(str_replace('-',' ',$name))),
            escapeshellarg($adminUser), escapeshellarg($adminPass), escapeshellarg($adminEmail)
        );
        [$out, $code] = $platform->exec($installCmd, $sitePath);
        $step('Install WordPress', $code === 0, $installCmd, $out);
        if ($code !== 0) ok(['ok' => false, 'error' => 'Install failed', 'steps' => $steps]);

        foreach ($plugins as $plugin) {
            $pCmd = 'wp plugin install ' . escapeshellarg($plugin) . ' --activate';
            [$out, $code] = $platform->exec($pCmd, $sitePath);
            $step('Plugin: ' . basename($plugin), $code === 0, $pCmd, trim($out));
        }
        foreach ($themes as $theme) {
            $tCmd = 'wp theme install ' . escapeshellarg($theme);
            [$out, $code] = $platform->exec($tCmd, $sitePath);
            $step('Theme: ' . basename($theme), $code === 0, $tCmd, trim($out));
        }

        // Link (only needed when parent is not a parked directory)
        if ($isParked) {
            $step('Link', true, 'skipped — parent directory is parked', '');
        } else {
            $vlinkCmd2 = $platform->valetBin() . ' link ' . escapeshellarg($name);
            [$out, $code] = $platform->exec($vlinkCmd2, $sitePath, $sudoPass);
            $step('Link', $code === 0, $vlinkCmd2, trim($out));
        }

        ok(['ok' => true, 'steps' => $steps, 'url' => $url, 'admin_url' => APP_URL . '/login.php?site='.rawurlencode($name)]);
    }

    // ── Open in file browser (Finder / Explorer) ─────────────────────────
    case 'open_finder': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $platform->openExplorer($path);
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

    // ── Native folder picker ──────────────────────────────────────────────
    case 'pick_path': {
        $result = $platform->pickFolder();
        if ($result === null) ok(['path' => null, 'cancelled' => true]);
        ok(['path' => $result]);
    }

    case 'toggle_ssl': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        set_time_limit(0);
        $name     = trim($body['name']     ?? '');
        $secure   = !empty($body['secure']);
        $sudoPass = trim($body['sudo_pass'] ?? '');
        if ($name === '') fail('Missing name');
        $verb = $secure ? 'secure' : 'unsecure';
        $cmd  = $platform->valetBin() . ' ' . $verb . ' ' . escapeshellarg($name);
        [$out, $code] = $platform->exec($cmd, APP_DIR, $sudoPass);
        // On macOS, valet/herd secure restarts PHP-FPM — if we get here it completed before restart
        if ($code !== 0) fail($out ?: ($secure ? 'secure failed' : 'unsecure failed'));
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

    // ── Site Notes ────────────────────────────────────────────────────────
    case 'save_note': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $site = preg_replace('/[^a-z0-9_-]/i', '', $body['site'] ?? '');
        if ($site === '') fail('Site required');
        $text = substr(trim($body['text'] ?? ''), 0, 2000);
        $db->saveNote($site, $text);
        ok(['text' => $text]);
    }

    case 'get_note': {
        $site = preg_replace('/[^a-z0-9_-]/i', '', $_GET['site'] ?? '');
        if ($site === '') fail('Site required');
        ok(['text' => $db->getNote($site)]);
    }

    // ── Site Info ─────────────────────────────────────────────────────────
    case 'site_info': {
        $path = SiteScanner::sitePath($_GET['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);

        [$wpVer]  = $platform->exec('wp core version', $path);
        [$phpVer] = $platform->exec('php -r "echo PHP_VERSION;"', $path);
        [$dbSize] = $platform->exec('wp db size --size_format=mb', $path);
        [$theme]  = $platform->exec('wp theme list --status=active --field=name', $path);
        [$blog]   = $platform->exec('wp option get blogname', $path);
        [$plOut]  = $platform->exec('wp plugin list --status=active --field=name', $path);

        $flags = [];
        foreach (['WP_DEBUG','WP_DEBUG_LOG','SCRIPT_DEBUG','SAVEQUERIES','DISALLOW_FILE_EDIT','WP_POST_REVISIONS'] as $flag) {
            [$val, $c] = $platform->exec("wp config get {$flag}", $path);
            $val = trim($val);
            if ($c !== 0) $flags[$flag] = null;
            elseif (in_array(strtolower($val), ['true','1'], true)) $flags[$flag] = true;
            elseif (in_array(strtolower($val), ['false','0'], true)) $flags[$flag] = false;
            else $flags[$flag] = $val;
        }

        ok([
            'wp_version'  => trim($wpVer),
            'php_version' => trim($phpVer),
            'db_size_mb'  => trim($dbSize),
            'theme'       => trim($theme),
            'blogname'    => trim($blog),
            'plugins'     => array_values(array_filter(explode("\n", trim($plOut)))),
            'flags'       => $flags,
        ]);
    }

    case 'set_config_flag': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $flag  = preg_replace('/[^A-Z_]/', '', strtoupper($body['flag'] ?? ''));
        $value = $body['value'] ?? null;
        if ($flag === '') fail('Invalid flag');

        $allowed = ['WP_DEBUG','WP_DEBUG_LOG','SCRIPT_DEBUG','SAVEQUERIES','DISALLOW_FILE_EDIT','WP_POST_REVISIONS'];
        if (!in_array($flag, $allowed, true)) fail('Flag not allowed');

        if (is_bool($value) || in_array($value, [true, false, 'true', 'false', 1, 0], true)) {
            $raw  = (filter_var($value, FILTER_VALIDATE_BOOLEAN)) ? 'true' : 'false';
            $cmd  = "wp config set {$flag} {$raw} --raw";
        } else {
            $cmd  = 'wp config set ' . $flag . ' ' . escapeshellarg((string)$value);
        }
        [$out, $code] = $platform->exec($cmd, $path);
        if ($code !== 0) fail(trim($out) ?: 'Failed to set flag');
        ok();
    }

    // ── PHP Version ───────────────────────────────────────────────────────
    case 'php_version': {
        $path = SiteScanner::sitePath($_GET['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        [$ver] = $platform->exec('php -r "echo PHP_MAJOR_VERSION.\'.\'.PHP_MINOR_VERSION;"', $path);
        ok(['version' => trim($ver)]);
    }

    case 'available_php': {
        [$list] = $platform->exec('ls /opt/homebrew/Cellar/ 2>/dev/null | grep -E "^php(@[0-9.]+)?$"', APP_DIR);
        $versions = [];
        foreach (array_filter(explode("\n", trim($list))) as $item) {
            $versions[] = preg_replace('/^php@?/', '', trim($item)) ?: 'latest';
        }
        sort($versions);
        ok(['versions' => $versions]);
    }

    case 'set_php_version': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $site = trim($body['site'] ?? '');
        $ver  = preg_replace('/[^0-9.]/', '', $body['version'] ?? '');
        if ($site === '' || $ver === '') fail('Invalid payload');
        $cmd = $platform->valetBin() . ' isolate php@' . $ver . ' --site=' . escapeshellarg($site);
        [$out, $code] = $platform->exec($cmd, APP_DIR);
        if ($code !== 0) fail(trim($out) ?: 'Failed to set PHP version');
        ok(['version' => $ver]);
    }

    // ── DB Snapshots ──────────────────────────────────────────────────────
    case 'list_snapshots': {
        $site = preg_replace('/[^a-z0-9_-]/i', '', $_GET['site'] ?? '');
        if ($site === '') fail('Site required');
        $snapDir = APP_DIR . '/Storage/snapshots/' . $site;
        $snaps   = [];
        if (is_dir($snapDir)) {
            foreach (glob($snapDir . '/*.sql') ?: [] as $f) {
                $snaps[] = [
                    'name'    => basename($f, '.sql'),
                    'size'    => filesize($f),
                    'created' => filemtime($f),
                ];
            }
            usort($snaps, fn($a, $b) => $b['created'] <=> $a['created']);
        }
        ok(['snapshots' => $snaps]);
    }

    case 'create_snapshot': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $site    = basename($path);
        $label   = preg_replace('/[^a-z0-9_-]/i', '-', trim($body['name'] ?? ''));
        if ($label === '') $label = 'snap-' . date('YmdHis');
        $snapDir = APP_DIR . '/Storage/snapshots/' . $site;
        if (!is_dir($snapDir)) mkdir($snapDir, 0755, true);
        $file    = $snapDir . '/' . $label . '.sql';
        $cmd     = 'wp db export ' . escapeshellarg($file) . ' --add-drop-table';
        [$out, $code] = $platform->exec($cmd, $path);
        if ($code !== 0) fail(trim($out) ?: 'Export failed');
        ok(['name' => $label, 'size' => filesize($file), 'created' => filemtime($file)]);
    }

    case 'restore_snapshot': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $path = SiteScanner::sitePath($body['site'] ?? '', $root);
        if (!$path) fail('Invalid site', 404);
        $site    = basename($path);
        $name    = preg_replace('/[^a-z0-9_-]/i', '-', trim($body['name'] ?? ''));
        $file    = APP_DIR . '/Storage/snapshots/' . $site . '/' . $name . '.sql';
        if (!file_exists($file)) fail('Snapshot not found', 404);
        $cmd = 'wp db import ' . escapeshellarg($file);
        [$out, $code] = $platform->exec($cmd, $path);
        if ($code !== 0) fail(trim($out) ?: 'Import failed');
        ok();
    }

    case 'delete_snapshot': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $site = preg_replace('/[^a-z0-9_-]/i', '', $body['site'] ?? '');
        $name = preg_replace('/[^a-z0-9_-]/i', '-', trim($body['name'] ?? ''));
        if ($site === '' || $name === '') fail('Invalid payload');
        $file = APP_DIR . '/Storage/snapshots/' . $site . '/' . $name . '.sql';
        if (file_exists($file)) unlink($file);
        ok();
    }

    // ── Clone Site ────────────────────────────────────────────────────────
    case 'clone_site': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required');
        $srcPath = SiteScanner::sitePath($body['source'] ?? '', $root);
        if (!$srcPath) fail('Source site not found', 404);

        $newName = preg_replace('/[^a-z0-9-]/', '', strtolower($body['name'] ?? ''));
        if ($newName === '' || strlen($newName) > 60) fail('Invalid site name');

        $parentDir = realpath(dirname($srcPath));
        $dstPath   = $parentDir . DIRECTORY_SEPARATOR . $newName;
        if (is_dir($dstPath)) fail('A site with that name already exists', 409);

        $sudoPass = trim($body['sudo_pass'] ?? '');
        $srcName  = basename($srcPath);
        $srcUrl   = 'http://' . $srcName . '.test';
        $dstUrl   = 'http://' . $newName . '.test';

        $steps = [];
        $step  = function(string $label, bool $ok, string $cmd = '', string $out = '') use (&$steps): void {
            $steps[] = ['label' => $label, 'ok' => $ok, 'cmd' => $cmd, 'output' => trim($out)];
        };

        // Copy files
        $cloneDir = function(string $src, string $dst) use (&$cloneDir): void {
            if (!is_dir($dst)) mkdir($dst, 0755, true);
            $items = array_diff(scandir($src) ?: [], ['.', '..']);
            foreach ($items as $item) {
                $s = $src . DIRECTORY_SEPARATOR . $item;
                $d = $dst . DIRECTORY_SEPARATOR . $item;
                if (is_dir($s)) $cloneDir($s, $d);
                else @copy($s, $d);
            }
        };
        $cloneDir($srcPath, $dstPath);
        $step('Copy files', is_dir($dstPath), "cp -r {$srcPath} {$dstPath}");

        // Update wp-config.php DB name
        $dbName = str_replace('-', '_', $newName);
        $wpConfig = $dstPath . '/wp-config.php';
        if (file_exists($wpConfig)) {
            $cfg = file_get_contents($wpConfig);
            $cfg = preg_replace(
                "/define\s*\(\s*'DB_NAME'\s*,\s*'[^']+'\s*\)/",
                "define( 'DB_NAME', '{$dbName}' )",
                $cfg
            );
            file_put_contents($wpConfig, $cfg);
            $step('Update wp-config DB_NAME', true, "sed DB_NAME → {$dbName}");
        }

        // Create and import DB
        $sDb    = $db->getSettings();
        $dc     = $sDb['db_config'] ?? [];
        $dbHost = trim($dc['host'] ?? '') ?: '127.0.0.1';
        $dbUser = trim($dc['user'] ?? '') ?: 'root';
        $dbPass = trim($dc['password'] ?? '');

        $tmpDump = sys_get_temp_dir() . '/vsm_clone_' . $newName . '.sql';
        $expCmd  = 'wp db export ' . escapeshellarg($tmpDump) . ' --add-drop-table';
        [$out, $code] = $platform->exec($expCmd, $srcPath);
        $step('Export source DB', $code === 0, $expCmd, $out);
        if ($code !== 0) ok(['ok' => false, 'error' => 'DB export failed', 'steps' => $steps]);

        $passArg = $dbPass !== '' ? '-p' . escapeshellarg($dbPass) : '';
        $createCmd = sprintf('mysql -h%s -u%s %s -e %s',
            escapeshellarg($dbHost), escapeshellarg($dbUser), $passArg,
            escapeshellarg("CREATE DATABASE IF NOT EXISTS `{$dbName}`")
        );
        [$out, $code] = $platform->exec($createCmd, APP_DIR);
        $step('Create target DB', $code === 0, "mysql … CREATE DATABASE {$dbName}", $out);
        if ($code !== 0) ok(['ok' => false, 'error' => 'DB create failed', 'steps' => $steps]);

        $impCmd = 'wp db import ' . escapeshellarg($tmpDump);
        [$out, $code] = $platform->exec($impCmd, $dstPath);
        $step('Import DB', $code === 0, $impCmd, $out);
        @unlink($tmpDump);
        if ($code !== 0) ok(['ok' => false, 'error' => 'DB import failed', 'steps' => $steps]);

        // Search-replace URLs
        $srCmd = "wp search-replace " . escapeshellarg($srcUrl) . " " . escapeshellarg($dstUrl);
        [$out, $code] = $platform->exec($srCmd, $dstPath);
        $step('Search-replace URLs', $code === 0, $srCmd, $out);

        // Valet link
        $valetCfg    = json_decode(@file_get_contents($platform->valetConfigFile()) ?: '{}', true);
        $parkedPaths = array_filter(array_map('realpath', $valetCfg['paths'] ?? []));
        $isParked    = in_array(realpath($parentDir), $parkedPaths, true);
        if ($isParked) {
            $step('Link', true, 'skipped — parent directory is parked', '');
        } else {
            $vlinkCmd = $platform->valetBin() . ' link ' . escapeshellarg($newName);
            [$out, $code] = $platform->exec($vlinkCmd, $dstPath, $sudoPass);
            $step('Link', $code === 0, $vlinkCmd, trim($out));
        }

        ok(['ok' => true, 'steps' => $steps, 'url' => $dstUrl]);
    }

    default:
        fail('Unknown action', 404);
}
