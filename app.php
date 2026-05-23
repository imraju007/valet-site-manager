<?php
declare(strict_types=1);

// ── Platform detection ────────────────────────────────────────────────────────
define('IS_WINDOWS', PHP_OS_FAMILY === 'Windows');
define('IS_MACOS',   PHP_OS_FAMILY === 'Darwin');

// Unified HOME constant — getenv('HOME') is empty on Windows
define('SYS_HOME', IS_WINDOWS
    ? (getenv('USERPROFILE') ?: 'C:\\Users\\Default')
    : (getenv('HOME') ?: '/tmp')
);

// ── App constants ─────────────────────────────────────────────────────────────
define('APP_NAME',    'Valet Site Manager');
define('APP_VERSION', '1.0.0');
define('APP_URL',
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'site-manager.test')
);
define('APP_DIR',     __DIR__);
define('STORAGE_DIR', APP_DIR . '/Storage');
define('DB_FILE',     STORAGE_DIR . '/db.json');

// ── Core includes ─────────────────────────────────────────────────────────────
require_once APP_DIR . '/includes/Auth.inc.php';
require_once APP_DIR . '/includes/Database.inc.php';
require_once APP_DIR . '/includes/Utils.inc.php';

// ── Platform layer ────────────────────────────────────────────────────────────
require_once APP_DIR . '/includes/Platform.inc.php';
require_once APP_DIR . '/includes/platforms/MacosPlatform.inc.php';
require_once APP_DIR . '/includes/platforms/WindowsPlatform.inc.php';

/** @var Platform $platform */
$platform = IS_WINDOWS ? new WindowsPlatform() : new MacosPlatform();

// Wire platform into SiteScanner so it can resolve paths without knowing the OS
SiteScanner::init($platform);
