<?php
declare(strict_types=1);

define('APP_NAME',    'Valet Site Manager');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://site-manager.test');
define('APP_DIR',     __DIR__);
define('STORAGE_DIR', APP_DIR . '/Storage');
define('DB_FILE',     STORAGE_DIR . '/db.json');

require_once APP_DIR . '/includes/Auth.inc.php';
require_once APP_DIR . '/includes/Database.inc.php';
require_once APP_DIR . '/includes/Utils.inc.php';
