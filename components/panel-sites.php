<!-- ── Sites Panel ─────────────────────────────────────────────────── -->
<div class="panel" id="panel-sites">

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="toolbar-left">
      <span class="sites-count">
        <strong id="vis-count"><?php echo count($entries); ?></strong>
        <span id="total-count"> / <?php echo count($entries); ?> sites</span>
      </span>

      <!-- Filter dropdown -->
      <div class="tb-dropdown">
        <button class="tb-btn" id="filter-btn">
          <svg><use href="#i-filter"/></svg>
          Filter
          <svg><use href="#i-chevd"/></svg>
        </button>
        <div class="tb-menu" id="filter-menu">
          <div class="tb-menu-label">Filter</div>
          <div class="tb-menu-item active" data-filter="all">All Sites</div>
          <div class="tb-menu-item" data-filter="debug-on">Debug Log ON</div>
          <div class="tb-menu-item" data-filter="debug-off">Debug Log OFF</div>
          <div class="tb-menu-sep"></div>
          <div class="tb-menu-label">Sort</div>
          <div class="tb-menu-item active" data-sort="az">Name A → Z</div>
          <div class="tb-menu-item" data-sort="za">Name Z → A</div>
        </div>
      </div>

      <!-- Inline search -->
      <div class="tb-search" id="search-wrap">
        <span class="tb-search-ic"><svg><use href="#i-search"/></svg></span>
        <input type="search" id="search" placeholder="Search sites…" autocomplete="off">
      </div>

      <!-- Add Site -->
      <button class="btn btn-c" id="create-site-btn" title="Create a new site">
        <svg><use href="#i-plus"/></svg>
        <span class="btn-lbl">Add Site</span>
      </button>
    </div>

    <div class="toolbar-right">
      <!-- View toggle -->
      <div class="view-toggle">
        <button class="vt-btn active" id="vt-grid" title="Grid view">
          <svg><use href="#i-grid"/></svg>
        </button>
        <button class="vt-btn" id="vt-list" title="List view">
          <svg><use href="#i-list"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Site Type Tabs -->
  <?php if (!empty($entries)):
    $wp_count     = count(array_filter($entries, fn($e) => $e['type'] === 'wordpress'));
    $static_count = count(array_filter($entries, fn($e) => $e['type'] === 'static'));
  ?>
  <div class="type-tabs" id="type-tabs">
    <button class="type-tab active" data-type="all">All <span class="type-tab-ct"><?= count($entries) ?></span></button>
    <?php if ($wp_count > 0): ?>
    <button class="type-tab" data-type="wordpress">WordPress <span class="type-tab-ct"><?= $wp_count ?></span></button>
    <?php endif; ?>
    <?php if ($static_count > 0): ?>
    <button class="type-tab" data-type="static">Static <span class="type-tab-ct"><?= $static_count ?></span></button>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Site grid/list container -->
  <?php if (empty($entries)): ?>
  <div style="text-align:center;padding:80px 20px;color:var(--txtm)">
    <p>No sites found. Add a directory with <code>wp-config.php</code>, <code>index.php</code>, or <code>index.html</code> to the workspace.</p>
  </div>
  <?php else: ?>
  <div id="sites-container" class="sites-grid">
    <?php
    $wp_sites     = array_filter($entries, fn($e) => $e['type'] === 'wordpress');
    $static_sites = array_filter($entries, fn($e) => $e['type'] === 'static');
    foreach ([['WordPress', 'wordpress', $wp_sites], ['Static', 'static', $static_sites]] as [$label, $typeKey, $group]):
      if (empty($group)) continue;
    ?>
    <div class="site-group">
      <div class="site-group-hdr">
        <span class="site-group-label"><?= $label ?></span>
        <span class="site-group-count"><?= count($group) ?></span>
      </div>
      <?php foreach ($group as $e): ?>
      <div class="sc"
        data-site="<?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>"
        data-search="<?php echo htmlspecialchars(strtolower($e['name']),ENT_QUOTES,'UTF-8'); ?>"
        data-type="<?php echo htmlspecialchars($e['type'],ENT_QUOTES,'UTF-8'); ?>"
        data-debug="unknown">
        <div class="sc-accent"></div>
        <div class="sc-top">
          <div class="sc-dot"></div>
          <span class="sc-name"><?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?></span>
          <span class="sc-type-badge sc-type-<?= $e['type'] ?>"><?= $e['type'] === 'wordpress' ? 'WP' : 'Static' ?></span>
          <span class="sc-url"><?php echo htmlspecialchars($e['url'],ENT_QUOTES,'UTF-8'); ?></span>
        </div>
        <div class="sc-path" title="<?php echo htmlspecialchars($e['path'],ENT_QUOTES,'UTF-8'); ?>"><?php echo htmlspecialchars($e['path'],ENT_QUOTES,'UTF-8'); ?></div>
        <div class="sc-actions">

          <!-- Open in browser dropdown -->
          <div class="open-wrap">
            <button class="btn btn-c js-open-trigger"
              data-url="<?php echo htmlspecialchars($e['url'],ENT_QUOTES,'UTF-8'); ?>">
              <svg><use href="#i-globe"/></svg>
              <span class="btn-lbl">Open</span>
              <svg class="btn-arr" width="10" height="10"><use href="#i-chevd"/></svg>
            </button>
            <div class="open-menu">
              <div class="om-item" data-app="tab" data-url="<?php echo htmlspecialchars($e['url'],ENT_QUOTES,'UTF-8'); ?>">
                <span class="om-item-ic">⊕</span> New Tab
              </div>
              <div class="om-sep"></div>
              <div class="om-item" data-app="chrome" data-url="<?php echo htmlspecialchars($e['url'],ENT_QUOTES,'UTF-8'); ?>">
                <span class="om-item-ic">🟡</span> Chrome
              </div>
              <div class="om-item" data-app="firefox" data-url="<?php echo htmlspecialchars($e['url'],ENT_QUOTES,'UTF-8'); ?>">
                <span class="om-item-ic">🦊</span> Firefox
              </div>
              <div class="om-item" data-app="safari" data-url="<?php echo htmlspecialchars($e['url'],ENT_QUOTES,'UTF-8'); ?>">
                <span class="om-item-ic">🔵</span> Safari
              </div>
              <div class="om-item" data-app="arc" data-url="<?php echo htmlspecialchars($e['url'],ENT_QUOTES,'UTF-8'); ?>">
                <span class="om-item-ic">◎</span> Arc
              </div>
              <div class="om-item" data-app="brave" data-url="<?php echo htmlspecialchars($e['url'],ENT_QUOTES,'UTF-8'); ?>">
                <span class="om-item-ic">🦁</span> Brave
              </div>
            </div>
          </div>

          <?php if ($e['type'] === 'wordpress'): ?>
          <!-- Admin (one-click login) -->
          <a class="btn btn-g"
            href="<?php echo htmlspecialchars($e['admin'],ENT_QUOTES,'UTF-8'); ?>"
            target="_blank" rel="noreferrer" title="One-click WP Admin">
            <svg><use href="#i-shield"/></svg><span class="btn-lbl">Admin</span>
          </a>
          <?php endif; ?>

          <!-- IDE -->
          <button class="btn btn-v js-ide"
            data-path="<?php echo htmlspecialchars($e['path'],ENT_QUOTES,'UTF-8'); ?>"
            data-phpstorm="<?php echo htmlspecialchars($e['phpstorm'],ENT_QUOTES,'UTF-8'); ?>"
            data-vscode="<?php echo htmlspecialchars($e['vscode'],ENT_QUOTES,'UTF-8'); ?>"
            data-cursor="<?php echo htmlspecialchars($e['cursor'],ENT_QUOTES,'UTF-8'); ?>"
            title="Open in IDE">
            <svg><use href="#i-code"/></svg><span class="btn-lbl">IDE</span>
          </button>

          <!-- Finder -->
          <button class="btn js-finder"
            data-site="<?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>"
            title="Reveal in Finder">
            <svg><use href="#i-folder"/></svg><span class="btn-lbl">Finder</span>
          </button>

          <?php if ($e['type'] === 'wordpress'): ?>
          <div class="sc-sep"></div>

          <!-- Terminal (inline sidebar) -->
          <button class="btn js-term"
            data-site="<?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>"
            data-mode="bash" title="Open inline terminal">
            <svg><use href="#i-terminal"/></svg><span class="btn-lbl">Terminal</span>
          </button>

          <!-- WP-CLI (inline sidebar) -->
          <button class="btn js-term"
            data-site="<?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>"
            data-mode="wpcli" title="Open WP-CLI shell">
            <svg><use href="#i-cli"/></svg><span class="btn-lbl">WP-CLI</span>
          </button>

          <div class="sc-sep"></div>

          <!-- Debug Log -->
          <button class="btn btn-a js-debug-log"
            data-site="<?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>"
            title="View debug.log">
            <svg><use href="#i-doc"/></svg><span class="btn-lbl">Log</span>
          </button>

          <!-- Debug Toggle -->
          <button class="btn btn-a js-debug-toggle"
            data-site="<?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>"
            data-enabled="unknown" title="Toggle WP_DEBUG_LOG">
            <svg><use href="#i-bug"/></svg>
            <span class="btn-lbl dl">Debug…</span>
          </button>

          <div class="sc-sep"></div>

          <!-- Clean DB -->
          <button class="btn btn-r js-clean-db"
            data-site="<?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>"
            title="Flush cache, transients & revisions">
            <svg><use href="#i-db"/></svg><span class="btn-lbl">Clean</span>
          </button>
          <?php endif; ?>

          <!-- Tasks -->
          <button class="btn btn-v js-site-tasks" data-site="<?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>" title="View site tasks"><svg><use href="#i-tasks"/></svg><span class="btn-lbl">Tasks</span></button>

          <!-- Delete -->
          <button class="btn btn-r js-site-delete" data-site="<?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>" title="Delete site &amp; unlink from Valet"><svg><use href="#i-trash"/></svg><span class="btn-lbl">Delete</span></button>

        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
