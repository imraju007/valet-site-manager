<!-- ── Terminal Sidebar ────────────────────────────────────────────────── -->
<div class="tp" id="terminal-panel">
  <div class="tp-hdr">
    <div class="tp-site">
      <div class="tp-dot"></div>
      <span class="tp-site-name" id="tp-site-name">—</span>
    </div>
    <div class="tp-tabs">
      <button class="tp-tab active" data-mode="bash">BASH</button>
      <button class="tp-tab" data-mode="wpcli">WP-CLI</button>
    </div>
    <button class="tp-close" id="tp-close">
      <svg><use href="#i-x"/></svg>
    </button>
  </div>
  <div class="tp-quick" id="tp-quick"></div>
  <div class="tp-out" id="tp-out"></div>
  <div class="tp-input-row">
    <span class="tp-prompt-label" id="tp-prompt">$</span>
    <input class="tp-cmd" id="tp-cmd" placeholder="Type a command…" autocomplete="off" spellcheck="false">
    <button class="tp-run" id="tp-run" title="Run (Enter)">▶</button>
  </div>
</div>
