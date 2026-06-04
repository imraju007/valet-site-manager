<?php
$hn        = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$fmt_bytes = function(int $b): string {
    if ($b >= 1048576) return round($b / 1048576, 1) . ' MB';
    if ($b >= 1024)    return round($b / 1024, 1) . ' KB';
    return $b . ' B';
};
$note    = $notes[$e['name']] ?? '';
$pma_url = $pma_url ?? 'http://phpmyadmin.test';
?>
<div class="sc<?= $archived ? ' sc-archived' : '' ?>"
  data-site="<?= $hn($e['name']) ?>"
  data-search="<?= $hn(strtolower($e['name'])) ?>"
  data-type="<?= $hn($e['type']) ?>"
  data-debug="unknown">
  <div class="sc-accent"></div>
  <div class="sc-top">
    <div class="sc-top-name">
      <div class="sc-dot"></div>
      <span class="sc-name"><?= $hn($e['name']) ?></span>
    </div>
    <div class="sc-top-badges">
      <span class="sc-type-badge sc-type-<?= $e['type'] ?>"><?= $e['type'] === 'wordpress' ? 'WP' : 'Static' ?></span>
      <?php if ($e['ssl']): ?><span class="sc-ssl-badge" title="HTTPS enabled"><svg width="9" height="9"><use href="#i-lock"/></svg></span><?php endif; ?>
      <?php if ($e['type'] === 'wordpress'): ?><span class="sc-debug-badge" title="WP_DEBUG_LOG enabled" style="display:none"><svg width="9" height="9"><use href="#i-bug"/></svg></span><?php endif; ?>
      <?php if ($e['type'] === 'wordpress'): ?><span class="sc-php-badge js-php-badge" data-site="<?= $hn($e['name']) ?>" title="PHP version — click to switch">···</span><?php endif; ?>
      <span class="sc-note-badge" title="Has note" style="display:<?= $note !== '' ? 'inline-flex' : 'none' ?>"><svg width="9" height="9"><use href="#i-note"/></svg></span>
    </div>
    <span class="sc-url"><?= $hn($e['url']) ?></span>
  </div>
  <div class="sc-path" title="<?= $hn($e['path']) ?>"><?= $hn($e['path']) ?></div>
  <div class="sc-note-preview" style="display:<?= $note !== '' ? 'block' : 'none' ?>"><?= $hn($note) ?></div>
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

    <?php if ($e['type'] === 'wordpress'): ?>
    <button class="btn js-term" data-site="<?= $hn($e['name']) ?>" data-mode="bash" title="Open inline terminal">
      <svg><use href="#i-terminal"/></svg><span class="btn-lbl">Terminal</span>
    </button>
    <?php endif; ?>

    <!-- 3-dot menu -->
    <div class="sc-dots-wrap">
      <button class="btn sc-dots js-sc-dots" data-site="<?= $hn($e['name']) ?>" title="More actions">
        <svg width="14" height="14"><use href="#i-dots"/></svg>
      </button>
      <div class="sc-menu">

        <?php if ($e['type'] === 'wordpress'): ?>
        <div class="sc-menu-hdr">Dev Tools</div>
        <button class="sc-menu-item js-term" data-site="<?= $hn($e['name']) ?>" data-mode="wpcli" title="Open WP-CLI shell">
          <svg width="12" height="12"><use href="#i-cli"/></svg> WP-CLI
        </button>
        <button class="sc-menu-item js-debug-log" data-site="<?= $hn($e['name']) ?>" title="View debug.log">
          <svg width="12" height="12"><use href="#i-doc"/></svg> Debug Log
          <?php if ($e['log_size'] > 0): ?><span class="log-sz"><?= $fmt_bytes($e['log_size']) ?></span><?php endif; ?>
        </button>
        <button class="sc-menu-item js-debug-toggle" data-site="<?= $hn($e['name']) ?>" data-enabled="unknown" title="Toggle WP_DEBUG_LOG">
          <svg width="12" height="12"><use href="#i-bug"/></svg> <span class="dl">Debug…</span><span class="debug-dot"></span>
        </button>
        <button class="sc-menu-item sc-menu-item-r js-clean-db" data-site="<?= $hn($e['name']) ?>" title="Flush cache, transients &amp; revisions">
          <svg width="12" height="12"><use href="#i-db"/></svg> Clean DB
        </button>
        <div class="sc-menu-div"></div>

        <div class="sc-menu-hdr">Site Tools</div>
        <a class="sc-menu-item" href="<?= $hn($pma_url) ?>" target="_blank" rel="noreferrer" title="Open phpMyAdmin">
          <svg width="12" height="12"><use href="#i-table"/></svg> phpMyAdmin
        </a>
        <button class="sc-menu-item js-site-info" data-site="<?= $hn($e['name']) ?>" title="Site information &amp; wp-config flags">
          <svg width="12" height="12"><use href="#i-info"/></svg> Site Info
        </button>
        <button class="sc-menu-item js-snapshots" data-site="<?= $hn($e['name']) ?>" title="Database snapshots">
          <svg width="12" height="12"><use href="#i-snapshot"/></svg> Snapshots
        </button>
        <button class="sc-menu-item js-clone-site" data-site="<?= $hn($e['name']) ?>" title="Clone this site">
          <svg width="12" height="12"><use href="#i-copy"/></svg> Clone
        </button>
        <div class="sc-menu-div"></div>
        <?php endif; ?>

        <div class="sc-menu-hdr">Manage</div>
        <button class="sc-menu-item js-ssl"
          data-site="<?= $hn($e['name']) ?>"
          data-secure="<?= $e['ssl'] ? 'true' : 'false' ?>"
          title="<?= $e['ssl'] ? 'HTTPS enabled — click to disable (valet unsecure)' : 'Enable HTTPS (valet secure)' ?>">
          <svg width="12" height="12"><use href="<?= $e['ssl'] ? '#i-lock' : '#i-lock-open' ?>"/></svg>
          <?= $e['ssl'] ? 'Disable SSL' : 'Enable SSL' ?>
        </button>
        <button class="sc-menu-item js-site-note" data-site="<?= $hn($e['name']) ?>" title="Add/edit site note">
          <svg width="12" height="12"><use href="#i-note"/></svg> Note
        </button>
        <button class="sc-menu-item js-site-tasks" data-site="<?= $hn($e['name']) ?>" title="View site tasks">
          <svg width="12" height="12"><use href="#i-tasks"/></svg> Tasks
        </button>
        <?php if (!$archived): ?>
        <button class="sc-menu-item js-archive" data-site="<?= $hn($e['name']) ?>" title="Archive site">
          <svg width="12" height="12"><use href="#i-archive"/></svg> Archive
        </button>
        <?php else: ?>
        <button class="sc-menu-item js-unarchive" data-site="<?= $hn($e['name']) ?>" title="Restore to active sites">
          <svg width="12" height="12"><use href="#i-unarchive"/></svg> Unarchive
        </button>
        <?php endif; ?>
        <div class="sc-menu-div"></div>

        <button class="sc-menu-item sc-menu-item-danger js-site-delete" data-site="<?= $hn($e['name']) ?>" title="Delete site &amp; unlink from Valet">
          <svg width="12" height="12"><use href="#i-trash"/></svg> Delete Site
        </button>

      </div><!-- /.sc-menu -->
    </div><!-- /.sc-dots-wrap -->

  </div>
</div>
