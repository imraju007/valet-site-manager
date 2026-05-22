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
      <span class="nav-count" id="nc-sites"><?php echo count($entries); ?></span>
    </button>
    <button class="nav-btn" data-tab="tasks">
      <svg width="14" height="14"><use href="#i-tasks"/></svg>
      Tasks
      <span class="nav-count" id="nc-tasks">0</span>
    </button>
    <button class="nav-btn" data-tab="settings">
      <svg width="14" height="14"><use href="#i-gear"/></svg>
      Settings
    </button>
  </nav>

  <div class="hdr-right">
    <button class="icb" id="theme-btn" title="Toggle theme">
      <svg><use href="#i-moon"/></svg>
    </button>
  </div>
</header>
