/* ═══════════════════════════════════════════════════════
   TASKS / KANBAN
═══════════════════════════════════════════════════════ */
const projectSel = $('#project-select');
const addTaskBtn = $('#add-task-btn');

if (S.project) {
  projectSel.value = S.project;
  addTaskBtn.disabled = false;
  loadTasks(S.project).then(renderKanban);
}

projectSel.addEventListener('change', async () => {
  S.project = projectSel.value;
  localStorage.setItem('sh-proj', S.project);
  addTaskBtn.disabled = !S.project;
  if (S.project) { await loadTasks(S.project); renderKanban(); }
  else { $('#kanban-wrap').innerHTML = '<div class="kanban"><div class="kanban-empty"><svg><use href="#i-tasks"/></svg><p>Select a project to see its tasks.</p></div></div>'; updateTaskStats([]); }
});

async function loadTasks(site) {
  const r = await api('get_tasks', { site }, 'GET');
  if (r.ok) S.tasks[site] = r.tasks;
}

function getTasks() { return S.tasks[S.project] || []; }
function getTask(id) { return getTasks().find(t => t.id===id); }

function updateTaskStats(tasks) {
  const todo = tasks.filter(t=>t.status==='todo').length;
  const prog = tasks.filter(t=>t.status==='inprogress').length;
  const done = tasks.filter(t=>t.status==='done').length;
  const total = tasks.length;
  const pct  = total ? Math.round((done/total)*100) : 0;
  $('#st-todo').textContent = todo;
  $('#st-prog').textContent = prog;
  $('#st-done').textContent = done;
  $('#proj-progress-fill').style.width = pct + '%';
  const show = total > 0;
  $('#proj-stats').style.display    = show ? 'flex' : 'none';
  $('#proj-progress').style.display = show ? 'block' : 'none';
  $('#nc-tasks').textContent = total;
}

function renderKanban() {
  const tasks = getTasks();
  updateTaskStats(tasks);
  if (document.getElementById('tab-analytics')?.classList.contains('active')) renderAnalytics();
  if (!S.project) { $('#kanban-wrap').innerHTML = ''; return; }

  const cols = [
    { status:'todo',       label:'TODO',        cls:'todo' },
    { status:'inprogress', label:'IN PROGRESS',  cls:'inprogress' },
    { status:'done',       label:'DONE',         cls:'done' },
  ];

  const html = `<div class="kanban">${cols.map(col => {
    const col_tasks = tasks.filter(t => t.status===col.status);
    return `
    <div class="kb-col" data-status="${col.status}" id="col-${col.status}">
      <div class="kb-col-hdr">
        <div class="kb-col-ic ${col.cls}"></div>
        <span class="kb-col-title">${col.label}</span>
        <span class="kb-count">${col_tasks.length}</span>
      </div>
      <div class="kb-col-body" id="colb-${col.status}">
        ${col_tasks.map(t => renderCard(t)).join('')}
      </div>
      <button class="kb-add js-kb-add" data-status="${col.status}">
        <svg width="13" height="13"><use href="#i-plus"/></svg> Add task
      </button>
    </div>`;
  }).join('')}</div>`;

  $('#kanban-wrap').innerHTML = html;
  initDragDrop();
}

function renderCard(task) {
  const hasSubs  = task.subtasks && task.subtasks.length > 0;
  const doneSubs = hasSubs ? task.subtasks.filter(s=>s.status==='done').length : 0;
  const pct      = hasSubs ? Math.round((doneSubs/task.subtasks.length)*100) : 0;
  const subsHtml = hasSubs ? task.subtasks.map(s => {
    const hasDesc = !!s.description;
    const pri     = s.priority || 'medium';
    return `
    <div class="kc-sub-item${hasDesc?' js-sub-acc':''}" data-task="${task.id}" data-sub="${s.id}">
      <div class="sub-item-hdr">
        <div class="sub-check ${s.status==='done'?'done':''} js-sub-chk"
             data-task="${task.id}" data-sub="${s.id}">
          <svg width="8" height="8"><use href="#i-check"/></svg>
        </div>
        <span class="sub-title ${s.status==='done'?'done':''}">${esc(s.title)}</span>
        <span class="pri pri-${pri}" style="font-size:9px;padding:1px 5px">${pri}</span>
        ${hasDesc ? `<span class="sub-chevron"><svg><use href="#i-chevd"/></svg></span>` : ''}
        <button class="sub-del js-sub-del" data-task="${task.id}" data-sub="${s.id}">
          <svg><use href="#i-x"/></svg>
        </button>
      </div>
      ${hasDesc ? `
      <div class="sub-item-body">
        <div class="sub-body-desc">${esc(s.description)}</div>
      </div>` : ''}
    </div>`;
  }).join('') : '';

  const dueTxt = task.due_date ? (() => {
    const d = new Date(task.due_date + 'T00:00:00');
    const now = new Date(); now.setHours(0,0,0,0);
    const overdue = d < now && task.status !== 'done';
    const label = d.toLocaleDateString('en-US',{month:'short',day:'numeric'});
    return `<span class="kc-due${overdue?' overdue':''}">${overdue?'⚠ ':''}${label}</span>`;
  })() : '';
  const taskPri = task.priority || 'medium';

  return `
  <div class="kc" data-task-id="${task.id}" data-status="${task.status}" draggable="true">
    <div class="kc-title ${task.status==='done'?'done':''}">${esc(task.title)}</div>
    <div class="kc-meta">
      <span class="pri pri-${taskPri}">${taskPri}</span>
      ${dueTxt}
    </div>
    ${task.description ? `<div class="kc-desc">${esc(task.description)}</div>` : ''}
    <div class="kc-footer">
      ${hasSubs ? `
        <div class="kc-sub-progress"><div class="kc-sub-progress-fill" style="width:${pct}%"></div></div>
        <span class="kc-sub-count">${doneSubs}/${task.subtasks.length}</span>
      ` : '<span style="font-size:11px;color:var(--txtm)">No subtasks</span>'}
      <div class="kc-actions">
        <button class="kc-btn js-kc-expand" data-task="${task.id}" title="Subtasks">
          <svg><use href="#i-chevd"/></svg>
        </button>
        <button class="kc-btn js-kc-edit" data-task="${task.id}" title="Edit">
          <svg><use href="#i-edit"/></svg>
        </button>
        <button class="kc-btn del js-kc-del" data-task="${task.id}" title="Delete">
          <svg><use href="#i-trash"/></svg>
        </button>
      </div>
    </div>
    <div class="kc-subs">
      ${subsHtml}
      <div class="kc-sub-add">
        <button class="kc-sub-add-btn js-sub-add-btn" data-task="${task.id}">+ Add Subtask</button>
        <div class="kc-sub-form" id="sf-${task.id}">
          <input class="kc-sub-input" placeholder="Subtask title…" data-task="${task.id}" autocomplete="off">
          <textarea class="kc-sub-desc-inp" placeholder="Description (optional)…" data-task="${task.id}" rows="2"></textarea>
          <div class="kc-sub-form-row">
            <select class="kc-sub-pri-sel" data-task="${task.id}">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
            <div class="kc-sub-form-btns">
              <button class="btn btn-c js-sub-save" style="font-size:11px;padding:3px 10px" data-task="${task.id}">Add</button>
              <button class="btn js-sub-cancel" style="font-size:11px;padding:3px 10px" data-task="${task.id}">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>`;
}

/* Kanban event delegation */
document.getElementById('kanban-wrap').addEventListener('click', async e => {
  const site = S.project;

  const expandBtn = e.target.closest('.js-kc-expand');
  if (expandBtn) {
    const card = expandBtn.closest('.kc');
    card.classList.toggle('expanded');
    return;
  }

  const editBtn = e.target.closest('.js-kc-edit');
  if (editBtn) {
    const task = getTask(editBtn.dataset.task);
    if (!task) return;
    $('#task-form-title').textContent = 'Edit Task';
    $('#tf-id').value       = task.id;
    $('#tf-title').value    = task.title;
    $('#tf-desc').value     = task.description || '';
    $('#tf-status').value   = task.status;
    $('#tf-priority').value = task.priority || 'medium';
    $('#tf-due').value      = task.due_date || '';
    openModal('modal-task');
    setTimeout(() => $('#tf-title').focus(), 50);
    return;
  }

  const delBtn = e.target.closest('.js-kc-del');
  if (delBtn) {
    if (!confirm('Delete this task?')) return;
    const r = await api('delete_task', { site, task_id: delBtn.dataset.task }, 'POST');
    if (r.ok) {
      S.tasks[site] = getTasks().filter(t => t.id!==delBtn.dataset.task);
      renderKanban();
      toast('Task deleted');
    }
    return;
  }

  const subChk = e.target.closest('.js-sub-chk');
  if (subChk) {
    const task = getTask(subChk.dataset.task);
    if (!task) return;
    const sub = task.subtasks?.find(s=>s.id===subChk.dataset.sub);
    if (!sub) return;
    sub.status = sub.status==='done' ? 'todo' : 'done';
    await api('save_task', { site, task }, 'POST');
    renderKanban();
    return;
  }

  const subDel = e.target.closest('.js-sub-del');
  if (subDel) {
    const task = getTask(subDel.dataset.task);
    if (!task) return;
    task.subtasks = (task.subtasks||[]).filter(s=>s.id!==subDel.dataset.sub);
    await api('save_task', { site, task }, 'POST');
    renderKanban();
    return;
  }

  const addBtn = e.target.closest('.js-kb-add');
  if (addBtn) {
    $('#task-form-title').textContent = 'New Task';
    $('#tf-id').value       = '';
    $('#tf-title').value    = '';
    $('#tf-desc').value     = '';
    $('#tf-status').value   = addBtn.dataset.status;
    $('#tf-priority').value = 'medium';
    $('#tf-due').value      = '';
    openModal('modal-task');
    setTimeout(() => $('#tf-title').focus(), 50);
    return;
  }

  // Subtask accordion toggle (click header, not checkbox/delete)
  const subAcc = e.target.closest('.js-sub-acc');
  if (subAcc && !e.target.closest('.js-sub-chk') && !e.target.closest('.js-sub-del')) {
    subAcc.classList.toggle('sub-open');
    return;
  }

  // Show subtask add form
  const subAddBtn = e.target.closest('.js-sub-add-btn');
  if (subAddBtn) {
    const form = document.getElementById('sf-' + subAddBtn.dataset.task);
    if (form) { form.classList.toggle('open'); form.querySelector('.kc-sub-input')?.focus(); }
    return;
  }

  // Save new subtask from inline form
  const subSave = e.target.closest('.js-sub-save');
  if (subSave) {
    const tid   = subSave.dataset.task;
    const form  = document.getElementById('sf-' + tid);
    const title = form.querySelector('.kc-sub-input').value.trim();
    if (!title) { form.querySelector('.kc-sub-input').focus(); return; }
    const desc = form.querySelector('.kc-sub-desc-inp').value.trim();
    const pri  = form.querySelector('.kc-sub-pri-sel').value;
    await addSubtask(tid, title, desc, pri);
    form.classList.remove('open');
    form.querySelector('.kc-sub-input').value    = '';
    form.querySelector('.kc-sub-desc-inp').value = '';
    form.querySelector('.kc-sub-pri-sel').value  = 'medium';
    return;
  }

  // Cancel subtask form
  const subCancel = e.target.closest('.js-sub-cancel');
  if (subCancel) {
    const form = document.getElementById('sf-' + subCancel.dataset.task);
    if (form) form.classList.remove('open');
    return;
  }
});

async function addSubtask(taskId, title, description='', priority='medium') {
  if (!title) return;
  const task = getTask(taskId);
  if (!task) return;
  task.subtasks = task.subtasks || [];
  task.subtasks.push({
    id: crypto.randomUUID?.() || Math.random().toString(36).slice(2),
    title,
    description: description || null,
    priority,
    status: 'todo',
  });
  await api('save_task', { site: S.project, task }, 'POST');
  renderKanban();
  const card = $(`.kc[data-task-id="${taskId}"]`);
  card?.classList.add('expanded');
}

/* Add task button */
addTaskBtn.addEventListener('click', () => {
  $('#task-form-title').textContent = 'New Task';
  $('#tf-id').value       = '';
  $('#tf-title').value    = '';
  $('#tf-desc').value     = '';
  $('#tf-status').value   = 'todo';
  $('#tf-priority').value = 'medium';
  $('#tf-due').value      = '';
  openModal('modal-task');
  setTimeout(() => $('#tf-title').focus(), 50);
});

/* Save task */
$('#tf-save').addEventListener('click', async () => {
  const site        = S.project;
  const title       = $('#tf-title').value.trim();
  const description = $('#tf-desc').value.trim();
  const status      = $('#tf-status').value;
  const priority    = $('#tf-priority').value;
  const due_date    = $('#tf-due').value || null;
  const id          = $('#tf-id').value;
  if (!title) { $('#tf-title').focus(); return; }

  let task;
  if (id) {
    task = getTask(id);
    if (!task) return;
    task.title       = title;
    task.description = description || null;
    task.status      = status;
    task.priority    = priority;
    task.due_date    = due_date;
  } else {
    task = { title, description: description || null, status, priority, due_date, subtasks: [] };
  }

  const r = await api('save_task', { site, task }, 'POST');
  if (r.ok) {
    if (!id) {
      S.tasks[site] = S.tasks[site] || [];
      S.tasks[site].push(r.task);
    }
    renderKanban();
    closeAllModals();
    toast(id ? 'Task updated' : 'Task created');
  } else toast(`Error: ${r.error}`, 'err');
});

$('#tf-title').addEventListener('keydown', e => {
  if (e.key==='Enter') $('#tf-save').click();
});

/* ═══════════════════════════════════════════════════════
   DRAG & DROP (kanban)
═══════════════════════════════════════════════════════ */
function initDragDrop() {
  $$('.kc').forEach(card => {
    card.addEventListener('dragstart', e => {
      S.dragging = card.dataset.taskId;
      card.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    card.addEventListener('dragend', () => {
      card.classList.remove('dragging');
      S.dragging = null;
    });
  });

  $$('.kb-col').forEach(col => {
    col.addEventListener('dragover', e => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      col.classList.add('drag-over');
    });
    col.addEventListener('dragleave', e => {
      if (!col.contains(e.relatedTarget)) col.classList.remove('drag-over');
    });
    col.addEventListener('drop', async e => {
      e.preventDefault();
      col.classList.remove('drag-over');
      if (!S.dragging) return;
      const newStatus = col.dataset.status;
      const task = getTask(S.dragging);
      if (!task || task.status===newStatus) return;
      task.status = newStatus;
      await api('save_task', { site: S.project, task }, 'POST');
      renderKanban();
    });
  });
}

/* ═══════════════════════════════════════════════════════
   TASKS TABS (Kanban / Analytics)
═══════════════════════════════════════════════════════ */
document.getElementById('tasks-tabs').addEventListener('click', e => {
  const tab = e.target.closest('.tasks-tab');
  if (!tab) return;
  $$('.tasks-tab').forEach(t => t.classList.remove('active'));
  $$('.tasks-tab-pane').forEach(p => p.classList.remove('active'));
  tab.classList.add('active');
  const pane = document.getElementById('tab-' + tab.dataset.tab);
  if (pane) {
    pane.classList.add('active');
    if (tab.dataset.tab === 'analytics') renderAnalytics();
  }
});

/* ═══════════════════════════════════════════════════════
   ANALYTICS
═══════════════════════════════════════════════════════ */
function renderAnalytics() {
  const wrap = document.getElementById('analytics-wrap');
  if (!wrap) return;
  const tasks = getTasks();
  if (!tasks.length) {
    wrap.innerHTML = '<div style="text-align:center;padding:60px 20px;color:var(--txtm);font-size:13px">No tasks yet. Create tasks to see analytics.</div>';
    return;
  }

  const total    = tasks.length;
  const todo     = tasks.filter(t => t.status === 'todo').length;
  const inprog   = tasks.filter(t => t.status === 'inprogress').length;
  const done     = tasks.filter(t => t.status === 'done').length;
  const pct      = total > 0 ? Math.round((done / total) * 100) : 0;
  const overdue  = tasks.filter(t => {
    if (!t.due_date || t.status === 'done') return false;
    return new Date(t.due_date + 'T00:00:00') < new Date();
  }).length;

  // Donut chart SVG
  const r = 42, cx = 52, cy = 52, circ = 2 * Math.PI * r;
  function arc(val, total, offset) {
    if (!total) return '';
    const dash = (val / total) * circ;
    return `stroke-dasharray="${dash.toFixed(1)} ${(circ - dash).toFixed(1)}" stroke-dashoffset="${(-offset * circ / total).toFixed(1)}"`;
  }
  let offset = 0;
  const todoArc   = arc(todo, total, offset);   offset += todo;
  const progArc   = arc(inprog, total, offset);  offset += inprog;
  const doneArc   = arc(done, total, offset);

  const donutSvg = `
  <svg class="an-donut-svg" width="104" height="104" viewBox="0 0 104 104">
    <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="var(--sur3)" stroke-width="16"/>
    ${todo  ? `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="var(--txt)"  stroke-width="16" ${todoArc} transform="rotate(-90 ${cx} ${cy})"/>` : ''}
    ${inprog? `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="var(--cyan)" stroke-width="16" ${progArc} transform="rotate(-90 ${cx} ${cy})"/>` : ''}
    ${done  ? `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="var(--grn)"  stroke-width="16" ${doneArc} transform="rotate(-90 ${cx} ${cy})"/>` : ''}
    <text x="${cx}" y="${cy}" text-anchor="middle" dominant-baseline="middle" fill="var(--txtb)" font-size="16" font-weight="700" font-family="Space Grotesk,sans-serif">${pct}%</text>
    <text x="${cx}" y="${cy+16}" text-anchor="middle" fill="var(--txt)" font-size="8" font-family="Space Grotesk,sans-serif">done</text>
  </svg>`;

  // Completion ring
  const ringR = 38, ringCirc = 2 * Math.PI * ringR;
  const ringDash = (pct / 100) * ringCirc;
  const ringSvg = `
  <svg width="96" height="96" viewBox="0 0 96 96">
    <circle cx="48" cy="48" r="${ringR}" fill="none" stroke="var(--sur3)" stroke-width="10"/>
    <circle cx="48" cy="48" r="${ringR}" fill="none" stroke="var(--cyan)" stroke-width="10"
      stroke-dasharray="${ringDash.toFixed(1)} ${(ringCirc-ringDash).toFixed(1)}"
      stroke-dashoffset="${(ringCirc/4).toFixed(1)}" transform="rotate(-90 48 48)"
      style="transition:stroke-dasharray .6s ease"/>
    <text x="48" y="48" text-anchor="middle" dominant-baseline="middle" fill="var(--txtb)" font-size="18" font-weight="700" font-family="Space Grotesk,sans-serif">${pct}%</text>
    <text x="48" y="62" text-anchor="middle" fill="var(--txt)" font-size="8" font-family="Space Grotesk,sans-serif">complete</text>
  </svg>`;

  // Heatmap: last 12 weeks × 7 days
  const today     = new Date(); today.setHours(0,0,0,0);
  const WEEKS     = 15;
  const startDay  = new Date(today);
  startDay.setDate(today.getDate() - (WEEKS * 7 - 1));

  // Build date → count map from created_at
  const countMap = {};
  tasks.forEach(t => {
    const d = t.created_at || '';
    if (d) countMap[d] = (countMap[d] || 0) + 1;
  });

  // Build grid: 7 rows (Mon–Sun), WEEKS cols
  const DAYS = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  let hmRows = '';
  for (let dow = 0; dow < 7; dow++) {
    let cells = '';
    for (let w = 0; w < WEEKS; w++) {
      const d = new Date(startDay);
      d.setDate(startDay.getDate() + w * 7 + dow);
      const key = d.toISOString().slice(0,10);
      const cnt = countMap[key] || 0;
      const lv  = cnt === 0 ? 0 : cnt === 1 ? 1 : cnt === 2 ? 2 : cnt <= 4 ? 3 : 4;
      cells += `<div class="an-hm-cell" data-v="${lv}" title="${key}: ${cnt} task${cnt!==1?'s':''}"></div>`;
    }
    hmRows += `<div class="an-hm-row"><span class="an-hm-label">${dow===0||dow===3||dow===6 ? DAYS[dow] : ''}</span>${cells}</div>`;
  }

  wrap.innerHTML = `
  <div class="an-stats">
    <div class="an-stat"><div class="an-stat-val">${total}</div><div class="an-stat-lbl">Total Tasks</div></div>
    <div class="an-stat"><div class="an-stat-val" style="color:var(--grn)">${done}</div><div class="an-stat-lbl">Completed</div></div>
    <div class="an-stat"><div class="an-stat-val" style="color:var(--cyan)">${inprog}</div><div class="an-stat-lbl">In Progress</div></div>
    <div class="an-stat"><div class="an-stat-val" style="color:var(--red)">${overdue}</div><div class="an-stat-lbl">Overdue</div></div>
  </div>

  <div class="an-charts">
    <div class="an-chart-box">
      <div class="an-chart-ttl">Status Breakdown</div>
      <div class="an-donut-wrap">
        ${donutSvg}
        <div class="an-legend">
          <div class="an-leg-row"><div class="an-leg-dot" style="background:var(--txt)"></div>Todo — ${todo}</div>
          <div class="an-leg-row"><div class="an-leg-dot" style="background:var(--cyan)"></div>In Progress — ${inprog}</div>
          <div class="an-leg-row"><div class="an-leg-dot" style="background:var(--grn)"></div>Done — ${done}</div>
        </div>
      </div>
    </div>
    <div class="an-chart-box">
      <div class="an-chart-ttl">Completion Rate</div>
      <div class="an-ring-outer">
        ${ringSvg}
        <div class="an-ring-label">${done} of ${total} tasks done</div>
      </div>
    </div>
  </div>

  <div class="an-chart-box">
    <div class="an-heatmap-ttl">Task Creation Activity — Last ${WEEKS} Weeks</div>
    <div class="an-heatmap-grid">${hmRows}</div>
  </div>`;
}
