<!-- ── Header ──────────────────────────────────────────────────────────── -->
<header class="hdr">
  <div class="logo">
    <div class="logo-mark">S</div>
    VALET SITE MANAGER
  </div>

  <nav class="hdr-nav">
    <button class="nav-btn active" data-tab="sites">
      <svg width="14" height="14"><use href="#i-globe"/></svg>
      Sites
      <span class="nav-count" id="nc-sites"><?php echo count($active_entries); ?></span>
    </button>
    <button class="nav-btn" data-tab="tasks">
      <svg width="14" height="14"><use href="#i-tasks"/></svg>
      Tasks
      <span class="nav-count" id="nc-tasks">0</span>
    </button>
  </nav>

  <div class="hdr-right">
    <button class="icb" id="theme-btn" title="Toggle theme">
      <svg><use href="#i-moon"/></svg>
    </button>
    <div class="hdr-more">
      <button class="icb hdr-more-btn" title="More">
        <svg><use href="#i-dots"/></svg>
      </button>
      <div class="hdr-more-menu" id="hdr-more-menu">
        <button class="hdr-more-item" data-tab="settings">
          <svg width="14" height="14"><use href="#i-gear"/></svg>
          Settings
        </button>
        <div class="hdr-more-sep"></div>
        <button class="hdr-more-item" data-tab="archive">
          <svg width="14" height="14"><use href="#i-archive"/></svg>
          Archive
          <span class="nav-count" id="nc-archive"><?php echo count($archived_entries); ?></span>
        </button>
      </div>
    </div>
  </div>
</header>
