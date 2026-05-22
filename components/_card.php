<?php $hn = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>
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
    <span class="sc-url"><?= $hn($e['url']) ?></span>
  </div>
  <div class="sc-path" title="<?= $hn($e['path']) ?>"><?= $hn($e['path']) ?></div>
  <div class="sc-actions">

    <div class="open-wrap">
      <button class="btn btn-c js-open-trigger" data-url="<?= $hn($e['url']) ?>">
        <svg><use href="#i-globe"/></svg>
        <span class="btn-lbl">Open</span>
        <svg class="btn-arr" width="10" height="10"><use href="#i-chevd"/></svg>
      </button>
      <div class="open-menu">
        <div class="om-item" data-app="tab"     data-url="<?= $hn($e['url']) ?>"><span class="om-item-ic">⊕</span> New Tab</div>
        <div class="om-sep"></div>
        <div class="om-item" data-app="chrome"  data-url="<?= $hn($e['url']) ?>"><span class="om-item-ic">🟡</span> Chrome</div>
        <div class="om-item" data-app="firefox" data-url="<?= $hn($e['url']) ?>"><span class="om-item-ic">🦊</span> Firefox</div>
        <div class="om-item" data-app="safari"  data-url="<?= $hn($e['url']) ?>"><span class="om-item-ic">🔵</span> Safari</div>
        <div class="om-item" data-app="arc"     data-url="<?= $hn($e['url']) ?>"><span class="om-item-ic">◎</span> Arc</div>
        <div class="om-item" data-app="brave"   data-url="<?= $hn($e['url']) ?>"><span class="om-item-ic">🦁</span> Brave</div>
      </div>
    </div>

    <?php if ($e['type'] === 'wordpress'): ?>
    <a class="btn btn-g" href="<?= $hn($e['admin']) ?>" target="_blank" rel="noreferrer" title="One-click WP Admin">
      <svg><use href="#i-shield"/></svg><span class="btn-lbl">Admin</span>
    </a>
    <?php endif; ?>

    <button class="btn btn-v js-ide"
      data-path="<?= $hn($e['path']) ?>"
      data-phpstorm="<?= $hn($e['phpstorm']) ?>"
      data-vscode="<?= $hn($e['vscode']) ?>"
      data-cursor="<?= $hn($e['cursor']) ?>"
      title="Open in IDE">
      <svg><use href="#i-code"/></svg><span class="btn-lbl">IDE</span>
    </button>

    <button class="btn js-finder" data-site="<?= $hn($e['name']) ?>" title="Reveal in Finder">
      <svg><use href="#i-folder"/></svg><span class="btn-lbl">Finder</span>
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
