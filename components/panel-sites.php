<!-- ── Sites Panel ─────────────────────────────────────────────────── -->
<div class="panel" id="panel-sites">

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="toolbar-left">
      <span class="sites-count">
        <strong id="vis-count"><?php echo count($active_entries); ?></strong>
        <span id="total-count"> / <?php echo count($active_entries); ?> sites</span>
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
    </div>

    <div class="toolbar-right">
      <div id="search-wrap" class="tb-search">
        <span class="tb-search-ic"><svg width="12" height="12"><use href="#i-search"/></svg></span>
        <input type="text" id="search" placeholder="Search sites…" autocomplete="off">
      </div>
      <div class="view-toggle">
        <button class="vt-btn active" id="vt-grid" title="Grid view"><svg width="13" height="13"><use href="#i-grid"/></svg></button>
        <button class="vt-btn" id="vt-list" title="List view"><svg width="13" height="13"><use href="#i-list"/></svg></button>
      </div>
      <button class="btn btn-c" id="create-site-btn" title="Create a new site">
        <svg width="12" height="12"><use href="#i-plus"/></svg>
        <span class="btn-lbl">Add Site</span>
      </button>
    </div>
  </div>

  <!-- Site Type Tabs -->
  <?php if (!empty($active_entries)):
    $wp_count     = count(array_filter($active_entries, fn($e) => $e['type'] === 'wordpress'));
    $static_count = count(array_filter($active_entries, fn($e) => $e['type'] === 'static'));
  ?>
  <div class="type-tabs" id="type-tabs">
    <button class="type-tab active" data-type="all">All <span class="type-tab-ct"><?= count($active_entries) ?></span></button>
    <?php if ($wp_count > 0): ?>
    <button class="type-tab" data-type="wordpress">WordPress <span class="type-tab-ct"><?= $wp_count ?></span></button>
    <?php endif; ?>
    <?php if ($static_count > 0): ?>
    <button class="type-tab" data-type="static">Static <span class="type-tab-ct"><?= $static_count ?></span></button>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Site grid/list container -->
  <?php if (empty($active_entries)): ?>
  <div style="text-align:center;padding:80px 20px;color:var(--txtm)">
    <p>No sites found. Add a directory with <code>wp-config.php</code>, <code>index.php</code>, or <code>index.html</code> to the workspace.</p>
  </div>
  <?php else: ?>
  <div id="sites-container" class="sites-grid">
    <?php
    $wp_sites     = array_filter($active_entries, fn($e) => $e['type'] === 'wordpress');
    $static_sites = array_filter($active_entries, fn($e) => $e['type'] === 'static');
    foreach ([['WordPress', 'wordpress', $wp_sites], ['Static', 'static', $static_sites]] as [$label, $typeKey, $group]):
      if (empty($group)) continue;
    ?>
    <div class="site-group">
      <div class="site-group-hdr">
        <span class="site-group-label"><?= $label ?></span>
        <span class="site-group-count"><?= count($group) ?></span>
      </div>
      <?php foreach ($group as $e):
        $archived = false;
        include __DIR__ . '/_card.php';
      endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
