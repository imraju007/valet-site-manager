/* ═══════════════════════════════════════════════════════
   TABS
═══════════════════════════════════════════════════════ */
$$('.nav-btn[data-tab]').forEach(b => b.addEventListener('click', ()=> switchTab(b.dataset.tab)));
$$('.hdr-more-item[data-tab]').forEach(b => b.addEventListener('click', ()=> { closeMoreMenu(); switchTab(b.dataset.tab); }));

const _moreMenu = $('#hdr-more-menu');
const _moreBtn  = $('.hdr-more-btn');
function closeMoreMenu() { _moreMenu?.classList.remove('open'); }
_moreBtn?.addEventListener('click', e => { e.stopPropagation(); _moreMenu?.classList.toggle('open'); });
document.addEventListener('click', e => { if (!e.target.closest('.hdr-more')) closeMoreMenu(); });

const VALID_TABS = new Set(['sites', 'tasks', 'settings', 'archive']);

function switchTab(tab, pushHash = true) {
  if (!VALID_TABS.has(tab)) tab = 'sites';
  if (pushHash) location.hash = tab;
  $$('.nav-btn[data-tab]').forEach(b => b.classList.toggle('active', b.dataset.tab===tab));
  $$('.hdr-more-item[data-tab]').forEach(b => b.classList.toggle('active', b.dataset.tab===tab));
  $('#panel-sites').classList.toggle('hidden', tab!=='sites');
  $('#panel-tasks').classList.toggle('hidden', tab!=='tasks');
  $('#panel-settings').classList.toggle('hidden', tab!=='settings');
  $('#panel-archive').classList.toggle('hidden', tab!=='archive');
  $('#search-wrap').style.display = tab==='sites' ? '' : 'none';
  if (tab==='tasks' && S.project) renderKanban();
  if (tab==='settings') loadSettingsDefaults();
}

function _tabFromHash() {
  const h = location.hash.replace('#', '');
  return VALID_TABS.has(h) ? h : 'sites';
}

window.addEventListener('hashchange', () => switchTab(_tabFromHash(), false));
switchTab(_tabFromHash(), false);

async function loadSettingsDefaults() {
  const r = await api('get_settings', null, 'GET');
  if (r.ok && r.settings) {
    const gs = r.settings;
    const nd = gs.new_site_default || {};
    const wa = gs.wp_admin_config  || {};
    const dc = gs.db_config        || {};

    if ($('#gs-plugins'))     $('#gs-plugins').value     = (nd.plugins || []).join('\n');
    if ($('#gs-themes'))      $('#gs-themes').value      = (nd.themes  || []).join('\n');
    if (document.getElementById('gs-tree')) folderTree.fromJson(nd.folder_structure || {});
    if ($('#gs-wp-dir'))     $('#gs-wp-dir').value     = nd.wp_parent_dir     || '';
    if ($('#gs-static-dir')) $('#gs-static-dir').value = nd.static_parent_dir || '';
    if ($('#gs-admin-user'))  $('#gs-admin-user').value  = wa.user     || '';
    if ($('#gs-admin-pass'))  $('#gs-admin-pass').value  = wa.password || '';
    if ($('#gs-admin-email')) $('#gs-admin-email').value = wa.email    || '';
    if ($('#gs-db-host'))     $('#gs-db-host').value     = dc.host     || '';
    if ($('#gs-db-user'))     $('#gs-db-user').value     = dc.user     || '';
    if ($('#gs-db-pass'))     $('#gs-db-pass').value     = dc.password || '';
  }
  const saved = localStorage.getItem('sh-preset');
  $$('.gs-preset-btn').forEach(b => b.classList.toggle('active', b.dataset.preset === saved));
}

/* ═══════════════════════════════════════════════════════
   SITE TYPE TABS
═══════════════════════════════════════════════════════ */
$$('.type-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    S.typeFilter = tab.dataset.type;
    $$('.type-tab').forEach(t => t.classList.toggle('active', t === tab));
    applyFilters();
  });
});

/* ═══════════════════════════════════════════════════════
   SEARCH
═══════════════════════════════════════════════════════ */
$('#search').addEventListener('input', applyFilters);
document.addEventListener('keydown', e => {
  if (e.key==='Escape') {
    closeAllModals();
    if (S.tp.open) closeTerminal();
  }
  if (e.key==='/' && e.target!==$('#search') && !e.target.closest('.modal,.tp')) {
    e.preventDefault(); $('#search').focus();
  }
});

/* ═══════════════════════════════════════════════════════
   VIEW MODE
═══════════════════════════════════════════════════════ */
$('#vt-grid').addEventListener('click', ()=> setView('grid'));
$('#vt-list').addEventListener('click', ()=> setView('list'));

function setView(v) {
  S.view = v;
  localStorage.setItem('sh-view', v);
  const c = $('#sites-container');
  if (!c) return;
  c.className = v==='grid' ? 'sites-grid' : 'sites-list';
  $('#vt-grid').classList.toggle('active', v==='grid');
  $('#vt-list').classList.toggle('active', v==='list');
}
setView(S.view);

/* ═══════════════════════════════════════════════════════
   FILTER / SORT
═══════════════════════════════════════════════════════ */
function initFilterMenu() {
  const btn  = $('#filter-btn');
  const menu = $('#filter-menu');
  btn.addEventListener('click', e => { e.stopPropagation(); menu.classList.toggle('open'); });
  document.addEventListener('click', () => menu.classList.remove('open'));

  $$('[data-filter]', menu).forEach(item => {
    item.addEventListener('click', () => {
      $$('[data-filter]', menu).forEach(i => i.classList.remove('active'));
      item.classList.add('active');
      S.filter = item.dataset.filter;
      btn.classList.toggle('has-filter', S.filter!=='all');
      menu.classList.remove('open');
      applyFilters();
    });
  });

  $$('[data-sort]', menu).forEach(item => {
    item.addEventListener('click', () => {
      $$('[data-sort]', menu).forEach(i => i.classList.remove('active'));
      item.classList.add('active');
      S.sort = item.dataset.sort;
      menu.classList.remove('open');
      applyFilters();
    });
  });
}
initFilterMenu();

function applyFilters() {
  const q    = $('#search').value.toLowerCase().trim();
  const cards = $$('.sc');
  let vis = 0;

  // Sort within each group to preserve grouping structure
  const container = $('#sites-container');
  if (container) {
    $$('.site-group', container).forEach(group => {
      const groupCards = [...$$('.sc', group)];
      groupCards.sort((a, b) => {
        const na = a.dataset.search, nb = b.dataset.search;
        return S.sort==='az' ? na.localeCompare(nb) : nb.localeCompare(na);
      });
      groupCards.forEach(c => group.appendChild(c));
    });
  }

  cards.forEach(card => {
    const nameMatch = !q || card.dataset.search.includes(q);
    let filterMatch = true;
    if (S.filter==='debug-on')  filterMatch = card.dataset.debug==='on';
    if (S.filter==='debug-off') filterMatch = card.dataset.debug==='off';
    const typeMatch = S.typeFilter === 'all' || card.dataset.type === S.typeFilter;
    const show = nameMatch && filterMatch && typeMatch;
    card.classList.toggle('site-hidden', !show);
    if (show) vis++;
  });

  // Hide groups that have no visible cards; hide group header when a type is selected
  if (container) {
    $$('.site-group', container).forEach(group => {
      const hasVisible = $$('.sc:not(.site-hidden)', group).length > 0;
      group.classList.toggle('site-hidden', !hasVisible);
    });
    const showHdr = S.typeFilter === 'all';
    $$('.site-group-hdr', container).forEach(hdr => hdr.classList.toggle('site-hidden', !showHdr));
  }

  $('#vis-count').textContent = vis;
}

/* ═══════════════════════════════════════════════════════
   HOVER MENUS — Open browser + IDE
═══════════════════════════════════════════════════════ */
let _hmTimer = null;

function _openHoverMenu(wrap) {
  clearTimeout(_hmTimer);
  $$('.open-menu.open, .ide-menu.open').forEach(m => m.classList.remove('open'));
  const menu = wrap.querySelector('.open-menu, .ide-menu');
  if (!menu) return;
  const btn  = wrap.querySelector('button');
  const rect = btn.getBoundingClientRect();
  const menuW = menu.offsetWidth || 180;
  let left = rect.left;
  if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
  menu.style.top  = (rect.bottom + 4) + 'px';
  menu.style.left = left + 'px';
  menu.classList.add('open');
}

function _closeHoverMenu(wrap) {
  _hmTimer = setTimeout(() => {
    wrap.querySelector('.open-menu, .ide-menu')?.classList.remove('open');
  }, 120);
}

$$('.open-wrap, .ide-wrap').forEach(wrap => {
  wrap.addEventListener('mouseenter', () => _openHoverMenu(wrap));
  wrap.addEventListener('mouseleave', () => _closeHoverMenu(wrap));
});

// Close all on outside click / scroll
document.addEventListener('click', e => {
  if (!e.target.closest('.open-wrap') && !e.target.closest('.ide-wrap')) {
    $$('.open-menu.open, .ide-menu.open').forEach(m => m.classList.remove('open'));
  }
});

// Handle browser item clicks
document.addEventListener('click', e => {
  const item = e.target.closest('.om-item');
  if (!item) return;
  const url = item.dataset.url;
  const app = item.dataset.app;
  if (!url) return;
  $$('.open-menu.open').forEach(m => m.classList.remove('open'));
  if (app === 'tab') {
    window.open(url, '_blank', 'noreferrer');
  } else {
    api('open_browser', { url, app }, 'POST').then(r => {
      if (!r.ok) toast(`Could not open ${app}: ${r.error}`, 'err');
    });
  }
});

/* ═══════════════════════════════════════════════════════
   DEBUG STATUS — lazy load via IntersectionObserver
═══════════════════════════════════════════════════════ */
async function loadDebug(site) {
  if (S.dbg[site] !== undefined) return;
  S.dbg[site] = '?';
  refreshDebugBtn(site);
  const r = await api('debug_status', { site }, 'GET');
  S.dbg[site] = r.ok ? r.enabled : false;
  // Update card data attribute for filter
  const card = $(`.sc[data-site="${CSS.escape(site)}"]`);
  if (card) card.dataset.debug = S.dbg[site] ? 'on' : 'off';
  refreshDebugBtn(site);
}

function refreshDebugBtn(site) {
  const btn = $(`.js-debug-toggle[data-site="${CSS.escape(site)}"]`);
  if (!btn) return;
  const st = S.dbg[site];
  const lbl = btn.querySelector('.dl');
  if (st==='?') { lbl.innerHTML = '<span class="spin"></span>'; return; }
  const on = st===true;
  lbl.textContent = on ? 'Debug ON' : 'Debug OFF';
  btn.classList.toggle('btn-a-on', on);
  btn.dataset.enabled = on ? '1' : '0';
}

const dbgObserver = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) loadDebug(e.target.dataset.site); });
}, { threshold: 0.1 });
$$('.sc[data-type="wordpress"]').forEach(c => dbgObserver.observe(c));

document.addEventListener('click', async e => {
  const btn = e.target.closest('.js-debug-toggle');
  if (!btn) return;
  const site = btn.dataset.site;
  const on   = btn.dataset.enabled === '1';
  const label = `${on ? 'Disable' : 'Enable'} debug log — ${site}`;
  const log  = actLog.action(label);
  btn.classList.add('btn-busy');
  const r = await api('toggle_debug', { site, enable: !on }, 'POST');
  btn.classList.remove('btn-busy');
  if (r.ok) {
    S.dbg[site] = r.enabled;
    const card = $(`.sc[data-site="${CSS.escape(site)}"]`);
    if (card) card.dataset.debug = r.enabled ? 'on' : 'off';
    refreshDebugBtn(site);
    if (r.steps) r.steps.forEach(s => log.step(s.label, s.ok, s.cmd || '', s.output || ''));
    log.done(true);
  } else {
    log.step(r.error || 'WP-CLI error', false, '', r.error || '');
    log.done(false, r.error || 'WP-CLI error');
  }
});

/* ═══════════════════════════════════════════════════════
   DEBUG LOG MODAL
═══════════════════════════════════════════════════════ */
document.addEventListener('click', async e => {
  const btn = e.target.closest('.js-debug-log');
  if (!btn) return;
  const site = btn.dataset.site;
  S.logSite = site;
  $('#log-title').textContent = `Debug Log — ${site}`;
  $('#log-meta').innerHTML = '<span class="spin"></span> Loading…';
  $('#log-area').innerHTML = '';
  openModal('modal-log');
  const r = await api('debug_log', { site }, 'GET');
  if (!r.ok) {
    $('#log-meta').innerHTML = '';
    $('#log-area').innerHTML = `<div class="log-empty">Error: ${esc(r.error)}</div>`;
    return;
  }
  if (!r.exists || !r.content) {
    $('#log-meta').innerHTML = '';
    $('#log-area').innerHTML = '<div class="log-empty">No debug.log found or log is empty.</div>';
    return;
  }
  $('#log-meta').innerHTML = `<span>${fmtBytes(r.bytes)}</span> · <span>${r.total_lines} lines (showing last 300)</span>`;
  const pre = document.createElement('pre');
  pre.className = 'log-body';
  pre.textContent = r.content;
  $('#log-area').innerHTML = '';
  $('#log-area').appendChild(pre);
  pre.scrollTop = pre.scrollHeight;
});

$('#log-clear').addEventListener('click', async () => {
  if (!S.logSite || !confirm('Clear the debug.log?')) return;
  const r = await api('clear_log', { site: S.logSite }, 'POST');
  if (r.ok) {
    $('#log-meta').innerHTML = '';
    $('#log-area').innerHTML = '<div class="log-empty">Log cleared.</div>';
    toast('Debug log cleared');
    document.querySelector(`.sc[data-site="${CSS.escape(S.logSite)}"] .log-sz`)?.remove();
  }
});

/* ═══════════════════════════════════════════════════════
   CLEAN DB
═══════════════════════════════════════════════════════ */
document.addEventListener('click', async e => {
  const btn = e.target.closest('.js-clean-db');
  if (!btn) return;
  const site = btn.dataset.site;
  if (!confirm(`Clean database for "${site}"?\n\nThis will flush cache, delete transients, and remove post revisions.`)) return;
  btn.disabled = true;
  const log = actLog.action(`Clean database — ${site}`);
  const r   = await api('clean_db', { site }, 'POST');
  btn.disabled = false;
  if (!r.ok) {
    log.step(r.error || 'Failed', false, '', r.error || '');
    log.done(false);
    return;
  }
  const allOk = r.results.every(res => res.ok);
  r.results.forEach(res => log.step(res.label, res.ok, res.cmd || '', res.msg || ''));
  log.done(allOk);
});

/* ═══════════════════════════════════════════════════════
   SITE CARD ACTIONS (delete + tasks popup)
═══════════════════════════════════════════════════════ */
document.getElementById('sites-container')?.addEventListener('click', async e => {
  // Open in Finder
  const finderBtn = e.target.closest('.js-finder');
  if (finderBtn) {
    const r = await api('open_finder', { site: finderBtn.dataset.site }, 'POST');
    if (!r.ok) toast(r.error || 'Could not open Finder', 'err');
    return;
  }

  // Delete site
  const delBtn = e.target.closest('.js-site-delete');
  if (delBtn) {
    const site = delBtn.dataset.site;
    if (!confirm(`Delete "${site}" and remove its Valet link? This cannot be undone.`)) return;
    delBtn.disabled = true;
    const log = actLog.action(`Delete site — ${site}`);

    let sudoPass = '';
    const sudoCheck = await api('check_sudo', {});
    if (sudoCheck.needs_password) {
      sudoPass = await log.passwordPrompt('Administrator password required for Valet unlink');
    }

    const r = await api('delete_site', { site, sudo_pass: sudoPass }, 'POST');
    if (r.ok) {
      if (r.steps) r.steps.forEach(s => log.step(s.label, s.ok, s.cmd || '', s.output || ''));
      else {
        log.step('Valet unlink', true, `valet unlink ${site}`);
        log.step('Remove files', true, `rm -rf …/${site}`);
      }
      log.done(true, `${site} deleted`);
      setTimeout(() => location.reload(), 900);
    } else {
      log.step(r.error || 'Delete failed', false, '', r.error || '');
      log.done(false, r.error || 'Delete failed');
      delBtn.disabled = false;
    }
    return;
  }

  // Site tasks tooltip
  const tasksBtn = e.target.closest('.js-site-tasks');
  if (tasksBtn) {
    const tip = document.getElementById('task-tip');
    // Toggle off if same button clicked again
    if (tip._anchor === tasksBtn && tip.classList.contains('visible')) {
      tip.classList.remove('visible');
      tip._anchor = null;
      return;
    }
    tip._anchor = tasksBtn;
    const site = tasksBtn.dataset.site;
    document.getElementById('task-tip-site').textContent = site;
    document.getElementById('task-tip-counts').innerHTML = '<span style="color:var(--txtm);font-size:11px">Loading…</span>';
    document.getElementById('task-tip-body').innerHTML = '';
    // Position near button
    const rect = tasksBtn.getBoundingClientRect();
    const tipW = 280;
    let left = rect.left;
    if (left + tipW > window.innerWidth - 12) left = window.innerWidth - tipW - 12;
    if (left < 8) left = 8;
    let top = rect.bottom + 6;
    tip.style.left = left + 'px';
    tip.style.top  = top + 'px';
    tip.classList.add('visible');
    // Wire "Open board" button
    document.getElementById('task-tip-goto').onclick = () => {
      tip.classList.remove('visible');
      tip._anchor = null;
      switchTab('tasks');
      setTimeout(() => {
        const sel = document.getElementById('project-select');
        if (sel) { sel.value = site; sel.dispatchEvent(new Event('change')); }
      }, 100);
    };
    // Fetch and render
    const r = await api('get_tasks', { site }, 'GET');
    const tasks = r.tasks || [];
    const counts = { todo: 0, 'in-progress': 0, done: 0 };
    tasks.forEach(t => { if (counts[t.status] !== undefined) counts[t.status]++; });
    const total = tasks.length;
    document.getElementById('task-tip-counts').innerHTML = total
      ? `<span>${total} task${total !== 1 ? 's' : ''}</span>`
        + (counts['in-progress'] ? `<span class="badge badge-in-progress">${counts['in-progress']} active</span>` : '')
        + (counts.done ? `<span class="badge badge-done">${counts.done} done</span>` : '')
      : '';
    const body = document.getElementById('task-tip-body');
    if (!tasks.length) {
      body.innerHTML = '<div class="task-tip-empty">No tasks yet</div>';
    } else {
      body.innerHTML = tasks.slice(0, 8).map(t => `
        <div class="tti">
          <span class="tti-pri tti-pri-${t.priority || 'medium'}"></span>
          <span class="tti-title${t.status === 'done' ? ' done' : ''}">${esc(t.title)}</span>
          <span class="badge badge-${t.status}" style="flex-shrink:0">${t.status}</span>
        </div>`).join('')
        + (tasks.length > 8 ? `<div style="font-size:11px;color:var(--txtm);padding:6px 0 2px;text-align:center">+${tasks.length - 8} more</div>` : '');
    }
    return;
  }
});

// Close task tooltip on outside click
document.addEventListener('click', e => {
  const tip = document.getElementById('task-tip');
  if (!tip || !tip.classList.contains('visible')) return;
  if (tip.contains(e.target) || e.target.closest('.js-site-tasks')) return;
  tip.classList.remove('visible');
  tip._anchor = null;
}, true);

// Close preset tooltip on outside click
document.addEventListener('click', e => {
  const tip = document.getElementById('cp-tip');
  if (!tip || !tip.classList.contains('visible')) return;
  if (tip.contains(e.target) || e.target.closest('#cp-toggle-btn')) return;
  tip.classList.remove('visible');
  document.getElementById('cp-toggle-btn')?.classList.remove('open');
}, true);

/* ═══════════════════════════════════════════════════════
   ARCHIVE / UNARCHIVE
═══════════════════════════════════════════════════════ */
document.addEventListener('click', async e => {
  const btn = e.target.closest('.js-archive');
  if (!btn) return;
  const site = btn.dataset.site;
  btn.disabled = true;
  const r = await api('toggle_archive', { name: site, archive: true }, 'POST');
  if (r.ok) {
    toast(`${site} archived`);
    setTimeout(() => location.reload(), 800);
  } else {
    toast(r.error || 'Archive failed', 'err');
    btn.disabled = false;
  }
});

document.addEventListener('click', async e => {
  const btn = e.target.closest('.js-unarchive');
  if (!btn) return;
  const site = btn.dataset.site;
  btn.disabled = true;
  const r = await api('toggle_archive', { name: site, archive: false }, 'POST');
  if (r.ok) {
    toast(`${site} unarchived`);
    setTimeout(() => location.reload(), 800);
  } else {
    toast(r.error || 'Unarchive failed', 'err');
    btn.disabled = false;
  }
});

document.addEventListener('click', async e => {
  const btn = e.target.closest('.js-ssl');
  if (!btn) return;
  const site   = btn.dataset.site;
  const secure = btn.dataset.secure !== 'true';
  btn.disabled = true;

  const title = (secure ? 'Enable SSL — ' : 'Disable SSL — ') + site;
  const log   = actLog.action(title);

  let sudoPass = '';
  const sudoCheck = await api('check_sudo', {});
  if (sudoCheck.needs_password) {
    sudoPass = await log.passwordPrompt('Administrator password required for valet secure');
  }

  const r = await api('toggle_ssl', { name: site, secure, sudo_pass: sudoPass }, 'POST');
  const cmd = (secure ? 'valet secure ' : 'valet unsecure ') + site;
  if (r.ok) {
    log.step(secure ? 'valet secure' : 'valet unsecure', true, cmd, r.output || '');
    log.done(true, secure ? `SSL enabled for ${site}` : `SSL disabled for ${site}`);
    setTimeout(() => location.reload(), 900);
  } else if (r.error === 'Failed to fetch') {
    // valet secure restarts PHP-FPM/nginx mid-request — connection drop = SSL applied successfully
    log.step(secure ? 'valet secure' : 'valet unsecure', true, cmd, 'Service restarted — reloading…');
    log.done(true, secure ? `SSL enabled for ${site}` : `SSL disabled for ${site}`);
    setTimeout(() => location.reload(), 3000);
  } else {
    log.step(r.error || 'SSL toggle failed', false, cmd, r.error || '');
    log.done(false, r.error || 'SSL toggle failed');
    btn.disabled = false;
  }
});
