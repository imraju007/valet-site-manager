<!-- ── Archive Panel ────────────────────────────────────────────────── -->
<div class="panel hidden" id="panel-archive">

  <div class="toolbar">
    <div class="toolbar-left">
      <span class="sites-count">
        <strong><?= count($archived_entries) ?></strong>
        <span> archived site<?= count($archived_entries) !== 1 ? 's' : '' ?></span>
      </span>
    </div>
  </div>

  <?php if (empty($archived_entries)): ?>
  <div style="text-align:center;padding:80px 20px;color:var(--txtm)">
    <svg width="32" height="32" style="opacity:.3;margin-bottom:12px;display:block;margin-left:auto;margin-right:auto"><use href="#i-archive"/></svg>
    <p>No archived sites. Use the Archive button on a site card to hide it from the main view.</p>
  </div>
  <?php else: ?>
  <div id="archive-container" class="sites-grid">
    <?php
    $wp_sites     = array_filter($archived_entries, fn($e) => $e['type'] === 'wordpress');
    $static_sites = array_filter($archived_entries, fn($e) => $e['type'] === 'static');
    foreach ([['WordPress', 'wordpress', $wp_sites], ['Static', 'static', $static_sites]] as [$label, $typeKey, $group]):
      if (empty($group)) continue;
    ?>
    <div class="site-group">
      <div class="site-group-hdr">
        <span class="site-group-label"><?= $label ?></span>
        <span class="site-group-count"><?= count($group) ?></span>
      </div>
      <?php foreach ($group as $e):
        $archived = true;
        include __DIR__ . '/_card.php';
      endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
