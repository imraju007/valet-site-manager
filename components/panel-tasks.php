<!-- ── Tasks Panel ──────────────────────────────────────────────────── -->
<div class="panel hidden" id="panel-tasks">

  <!-- Header / project selector -->
  <div class="tasks-hdr">
    <div style="display:flex;flex-direction:column;gap:8px;flex:1">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <div class="project-select-wrap">
          <select class="project-select" id="project-select">
            <option value="">— Select project —</option>
            <?php foreach ($entries as $e): ?>
            <option value="<?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>">
              <?php echo htmlspecialchars($e['name'],ENT_QUOTES,'UTF-8'); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <span class="ps-arrow"><svg><use href="#i-chevd"/></svg></span>
        </div>
        <div class="proj-stats" id="proj-stats" style="display:none">
          <div class="stat-item">
            <div class="stat-dot" style="background:var(--txt)"></div>
            <span class="stat-val" id="st-todo">0</span> Todo
          </div>
          <div class="stat-item">
            <div class="stat-dot" style="background:var(--cyan);box-shadow:0 0 5px var(--cyan)"></div>
            <span class="stat-val" id="st-prog">0</span> In Progress
          </div>
          <div class="stat-item">
            <div class="stat-dot" style="background:var(--grn)"></div>
            <span class="stat-val" id="st-done">0</span> Done
          </div>
        </div>
        <button class="btn btn-c" id="add-task-btn" disabled>
          <svg><use href="#i-plus"/></svg> New Task
        </button>
      </div>
      <div class="proj-progress" id="proj-progress" style="display:none">
        <div class="proj-progress-fill" id="proj-progress-fill" style="width:0%"></div>
      </div>
    </div>
  </div>

  <!-- Tab bar -->
  <div class="tasks-tabs" id="tasks-tabs">
    <button class="tasks-tab active" data-tab="kanban">Kanban</button>
    <button class="tasks-tab" data-tab="analytics">Analytics</button>
  </div>

  <!-- Tab panes -->
  <div class="tasks-tab-body">
    <div class="tasks-tab-pane active" id="tab-kanban">
      <div id="kanban-wrap"></div>
    </div>
    <div class="tasks-tab-pane" id="tab-analytics">
      <div class="an-wrap" id="analytics-wrap">
        <div style="text-align:center;padding:60px 20px;color:var(--txtm);font-size:13px">Select a project to view analytics.</div>
      </div>
    </div>
  </div>
</div>
