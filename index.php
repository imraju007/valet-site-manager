<?php
require_once __DIR__ . '/app.php';

Auth::startSession();
$csrf    = Auth::csrfToken();
$root    = realpath(__DIR__ . '/..');
$entries = SiteScanner::allSites($root ?: '');
$db      = new Database(DB_FILE);
$archived         = $db->getArchived();
$active_entries   = array_values(array_filter($entries, fn($e) => !in_array($e['name'], $archived)));
$archived_entries = array_values(array_filter($entries, fn($e) =>  in_array($e['name'], $archived)));
$installed        = SiteScanner::detectInstalledApps();
$notes            = $db->getNotes();
$pma_url          = $db->getSettings()['db_config']['pma_url'] ?? 'http://phpmyadmin.test';
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
<link rel="shortcut icon" href="assets/favicon.svg">
<link rel="apple-touch-icon" href="assets/favicon.svg">
<link rel="stylesheet" href="dist/styles/css/styles.css">
</head>
<body>

<?php require_once __DIR__ . '/components/svg-sprite.php'; ?>
<?php require_once __DIR__ . '/components/header.php'; ?>

<div class="app">
  <?php require_once __DIR__ . '/components/panel-sites.php'; ?>
  <?php require_once __DIR__ . '/components/panel-tasks.php'; ?>
  <?php require_once __DIR__ . '/components/panel-settings.php'; ?>
  <?php require_once __DIR__ . '/components/panel-archive.php'; ?>
</div>

<?php require_once __DIR__ . '/components/terminal.php'; ?>
<?php require_once __DIR__ . '/components/modals.php'; ?>
<?php require_once __DIR__ . '/components/tooltips.php'; ?>
<?php require_once __DIR__ . '/components/activity-log.php'; ?>

<div id="toasts"></div>

<script>
const SITES = <?php echo json_encode($active_entries, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
const CSRF_TOKEN = <?php echo json_encode($csrf); ?>;
</script>
<script src="dist/styles/js/scripts.js"></script>
<script src="dist/styles/js/components/api.js"></script>
<script src="dist/styles/js/components/activity-log.js"></script>
<script src="dist/styles/js/components/theme.js"></script>
<script src="dist/styles/js/components/terminal.js"></script>
<script src="dist/styles/js/components/sites.js"></script>
<script src="dist/styles/js/components/tasks.js"></script>
<script src="dist/styles/js/components/settings.js"></script>
<script src="dist/styles/js/components/modals.js"></script>
</body>
</html>
