<?php
$hn        = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$fmt_bytes = function(int $b): string {
    if ($b >= 1048576) return round($b / 1048576, 1) . ' MB';
    if ($b >= 1024)    return round($b / 1024, 1) . ' KB';
    return $b . ' B';
};
?>
<div class="sc<?= $archived ? ' sc-archived' : '' ?>"
  data-site="<?= $hn($e['name']) ?>"
  data-search="<?= $hn(strtolower($e['name'])) ?>"
  data-type="<?= $hn($e['type']) ?>"
  data-debug="unknown">
  <div class="sc-accent"></div>
  <div class="sc-top">
    <div class="sc-dot"></div>
    <span class="sc-name"><?= $hn($e['name']) ?></span>
    <span class="sc-type-badge sc-type-<?= $e['type'] ?>"><?= $e['type'] === 'wordpress' ? 'WP' : 'Static' ?></span>
    <?php if ($e['ssl']): ?><span class="sc-ssl-badge" title="HTTPS enabled"><svg width="9" height="9"><use href="#i-lock"/></svg></span><?php endif; ?>
    <span class="sc-url"><?= $hn($e['url']) ?></span>
  </div>
  <div class="sc-path" title="<?= $hn($e['path']) ?>"><?= $hn($e['path']) ?></div>
  <div class="sc-actions">

    <?php
    $browser_list = [
        ['chrome',  '🟡', 'Chrome'],
        ['firefox', '🦊', 'Firefox'],
        ['safari',  '🔵', 'Safari'],
        ['arc',     '◎',  'Arc'],
        ['brave',   '🦁', 'Brave'],
    ];
    $avail_browsers = array_filter($browser_list, fn($b) => in_array($b[0], $installed['browsers']));
    ?>
    <div class="open-wrap">
      <button class="btn btn-c js-open-trigger" data-url="<?= $hn($e['url']) ?>">
        <svg><use href="#i-globe"/></svg>
        <span class="btn-lbl">Open</span>
      </button>
      <div class="open-menu">
        <div class="om-item" data-app="tab" data-url="<?= $hn($e['url']) ?>"><span class="om-item-ic">⊕</span> New Tab</div>
        <?php if ($avail_browsers): ?>
        <div class="om-sep"></div>
        <?php foreach ($avail_browsers as [$app, $ic, $label]): ?>
        <div class="om-item" data-app="<?= $app ?>" data-url="<?= $hn($e['url']) ?>"><span class="om-item-ic"><?= $ic ?></span> <?= $label ?></div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($e['type'] === 'wordpress'): ?>
    <a class="btn btn-g" href="<?= $hn($e['admin']) ?>" target="_blank" rel="noreferrer" title="One-click WP Admin">
      <svg><use href="#i-shield"/></svg><span class="btn-lbl">Admin</span>
    </a>
    <?php endif; ?>

    <?php if ($installed['ides']): ?>
    <div class="ide-wrap">
      <button class="btn btn-v" title="Open in IDE">
        <svg><use href="#i-code"/></svg><span class="btn-lbl">IDE</span>
      </button>
      <div class="ide-menu">
        <?php if (in_array('phpstorm', $installed['ides'])): ?>
        <a class="ide-item ide-phpstorm" href="<?= $hn($e['phpstorm']) ?>">
          <span class="ide-item-ic">PS</span><span>PhpStorm</span>
        </a>
        <?php endif; ?>
        <?php if (in_array('vscode', $installed['ides'])): ?>
        <a class="ide-item ide-vscode" href="<?= $hn($e['vscode']) ?>">
          <span class="ide-item-ic">&lt;/&gt;</span><span>VS Code</span>
        </a>
        <?php endif; ?>
        <?php if (in_array('cursor', $installed['ides'])): ?>
        <a class="ide-item ide-cursor" href="<?= $hn($e['cursor']) ?>">
          <span class="ide-item-ic">✦</span><span>Cursor</span>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>


    <button class="btn js-finder" data-site="<?= $hn($e['name']) ?>" title="Reveal in Finder">
      <svg><use href="#i-folder"/></svg><span class="btn-lbl">Finder</span>
    </button>

    <button class="btn <?= $e['ssl'] ? 'btn-g btn-g-on' : '' ?> js-ssl"
      data-site="<?= $hn($e['name']) ?>"
      data-secure="<?= $e['ssl'] ? 'true' : 'false' ?>"
      title="<?= $e['ssl'] ? 'HTTPS enabled — click to disable (valet unsecure)' : 'Enable HTTPS (valet secure)' ?>">
      <svg><use href="<?= $e['ssl'] ? '#i-lock' : '#i-lock-open' ?>"/></svg><span class="btn-lbl"><?= $e['ssl'] ? 'Disable SSL' : 'Enable SSL' ?></span>
    </button>

    <?php if ($e['type'] === 'wordpress'): ?>
    <div class="sc-sep"></div>

    <button class="btn js-term" data-site="<?= $hn($e['name']) ?>" data-mode="bash" title="Open inline terminal">
      <svg><use href="#i-terminal"/></svg><span class="btn-lbl">Terminal</span>
    </button>

    <button class="btn js-term" data-site="<?= $hn($e['name']) ?>" data-mode="wpcli" title="Open WP-CLI shell">
      <svg><use href="#i-cli"/></svg><span class="btn-lbl">WP-CLI</span>
    </button>

    <div class="sc-sep"></div>

    <button class="btn btn-a js-debug-log" data-site="<?= $hn($e['name']) ?>" title="View debug.log">
      <svg><use href="#i-doc"/></svg><span class="btn-lbl">Log</span>
      <?php if ($e['log_size'] > 0): ?><span class="log-sz"><?= $fmt_bytes($e['log_size']) ?></span><?php endif; ?>
    </button>

    <button class="btn btn-a js-debug-toggle" data-site="<?= $hn($e['name']) ?>" data-enabled="unknown" title="Toggle WP_DEBUG_LOG">
      <svg><use href="#i-bug"/></svg><span class="btn-lbl dl">Debug…</span>
    </button>

    <div class="sc-sep"></div>

    <button class="btn btn-r js-clean-db" data-site="<?= $hn($e['name']) ?>" title="Flush cache, transients & revisions">
      <svg><use href="#i-db"/></svg><span class="btn-lbl">Clean</span>
    </button>
    <?php endif; ?>

    <button class="btn btn-v js-site-tasks" data-site="<?= $hn($e['name']) ?>" title="View site tasks">
      <svg><use href="#i-tasks"/></svg><span class="btn-lbl">Tasks</span>
    </button>

    <?php if (!$archived): ?>
    <button class="btn js-archive" data-site="<?= $hn($e['name']) ?>" title="Archive site">
      <svg><use href="#i-archive"/></svg><span class="btn-lbl">Archive</span>
    </button>
    <button class="btn btn-r js-site-delete" data-site="<?= $hn($e['name']) ?>" title="Delete site &amp; unlink from Valet">
      <svg><use href="#i-trash"/></svg><span class="btn-lbl">Delete</span>
    </button>
    <?php else: ?>
    <button class="btn btn-v js-unarchive" data-site="<?= $hn($e['name']) ?>" title="Restore to active sites">
      <svg><use href="#i-archive"/></svg><span class="btn-lbl">Unarchive</span>
    </button>
    <button class="btn btn-r js-site-delete" data-site="<?= $hn($e['name']) ?>" title="Delete site &amp; unlink from Valet">
      <svg><use href="#i-trash"/></svg><span class="btn-lbl">Delete</span>
    </button>
    <?php endif; ?>

  </div>
</div>
