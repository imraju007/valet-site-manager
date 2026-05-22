/* ═══════════════════════════════════════════════════════
   CREATE SITE
═══════════════════════════════════════════════════════ */
const csName    = $('#cs-name');
const csPreview = $('#cs-preview');
const csLog     = $('#cs-log');
const csLogArea = $('#cs-log-area');
const csCreate  = $('#cs-create');
const csDir     = $('#cs-dir');

// Default parent dir from SITES data (fallback if no saved setting)
const defaultDir = SITES.length ? SITES[0].path.replace(/\/[^/]+$/, '') : '';

// Saved parent dirs per type (populated when modal opens)
let _savedWpDir = '', _savedStaticDir = '';

const csFolderTree = createFolderTree();
csFolderTree.init('cs-tree');

/* ── per-field clear button visibility ── */
function _setClearVisible(id, visible) {
  document.querySelector(`.fld-clr[data-clr="${id}"]`)?.classList.toggle('show', visible);
}
function _checkClear(id) {
  if (id === 'cs-tree') {
    _setClearVisible(id, Object.keys(csFolderTree.toJson()).length > 0);
  } else {
    const el = document.getElementById(id);
    _setClearVisible(id, !!(el?.value.trim()));
  }
}

// Listen for input on text fields
['cs-name', 'cs-dir', 'cs-plugins', 'cs-themes'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', () => _checkClear(id));
});

// Watch tree DOM for changes
const _csTreeEl = document.getElementById('cs-tree');
if (_csTreeEl) new MutationObserver(() => _checkClear('cs-tree')).observe(_csTreeEl, { childList: true, subtree: true });

function _applyDirForType(isWp) {
  const saved = isWp ? _savedWpDir : _savedStaticDir;
  csDir.value = saved || defaultDir;
  _checkClear('cs-dir');
}

$('#create-site-btn').addEventListener('click', () => {
  csName.value = '';
  csLog.style.display = 'none';
  csLogArea.innerHTML = '';
  csCreate.disabled = false;
  csCreate.innerHTML = '<svg><use href="#i-plus"/></svg> Create Site';
  document.getElementById('cs-step-type').style.display = '';
  document.getElementById('cs-type-wp').checked = true;
  document.getElementById('cs-wp-fields').style.display = '';
  document.getElementById('cs-static-fields').style.display = 'none';
  csFolderTree.fromJson({});
  ['cs-name', 'cs-dir', 'cs-plugins', 'cs-themes', 'cs-tree'].forEach(id => _setClearVisible(id, false));
  // Pre-fill from saved settings
  api('get_settings', null, 'GET').then(r => {
    if (!r.ok || !r.settings) return;
    const nd = r.settings.new_site_default || {};
    _savedWpDir     = nd.wp_parent_dir     || '';
    _savedStaticDir = nd.static_parent_dir || '';
    _applyDirForType(true);
    if (nd.plugins?.length) { $('#cs-plugins').value = nd.plugins.join('\n'); _checkClear('cs-plugins'); }
    if (nd.themes?.length)  { $('#cs-themes').value  = nd.themes.join('\n');  _checkClear('cs-themes');  }
    const fs = nd.folder_structure;
    csFolderTree.fromJson((fs && typeof fs === 'object') ? fs : {});
  });
  openModal('modal-create-site');
  setTimeout(() => csName.focus(), 60);
});

$$('input[name="cs-type"]').forEach(radio => radio.addEventListener('change', () => {
  const isWp = document.getElementById('cs-type-wp').checked;
  document.getElementById('cs-wp-fields').style.display     = isWp ? '' : 'none';
  document.getElementById('cs-static-fields').style.display = isWp ? 'none' : '';
  _applyDirForType(isWp);
}));

csName.addEventListener('input', () => {
  const v = csName.value.replace(/[^a-z0-9-]/g, '');
  csName.value = v;
  csPreview.textContent = (v || 'sitename') + '.test';
});

function csStep(icon, text, state='') {
  const el = document.createElement('div');
  el.className = 'cs-step' + (state ? ' ' + state : '');
  el.innerHTML = `<span class="cs-step-ic">${icon}</span><span class="cs-step-txt">${esc(text)}</span>`;
  csLogArea.appendChild(el);
  csLogArea.scrollTop = csLogArea.scrollHeight;
  return el;
}

csCreate.addEventListener('click', async () => {
  const name = csName.value.trim();
  if (!name) { csName.focus(); return; }
  if (!/^[a-z0-9][a-z0-9-]*$/.test(name)) { toast('Invalid site name', 'err'); return; }

  const type      = document.getElementById('cs-type-wp').checked ? 'wordpress' : 'static';
  const parentDir = csDir.value.trim();
  const plugins   = type === 'wordpress' ? $('#cs-plugins').value.trim() : '';
  const themes    = type === 'wordpress' ? $('#cs-themes').value.trim()  : '';
  const structObj = csFolderTree.toJson();
  const structure = type === 'static' ? (Object.keys(structObj).length ? JSON.stringify(structObj) : '') : '';

  csCreate.disabled = true;
  csCreate.innerHTML = '⏳ Creating…';
  document.getElementById('cs-step-type').style.display = 'none';
  csLog.style.display = 'block';
  csLogArea.innerHTML = '<div style="text-align:center;padding:24px 0;color:var(--txtm);font-size:12px">See Activity Log below…</div>';

  const log = actLog.action(`Create ${type} site — ${name}`);

  let sudoPass = '';
  const sudoCheck = await api('check_sudo', {});
  if (sudoCheck.needs_password) {
    sudoPass = await log.passwordPrompt('Administrator password required for Valet');
  }

  const r = await api('create_site', { name, type, parent_dir: parentDir, plugins, themes, structure, sudo_pass: sudoPass }, 'POST');

  if (r.steps) r.steps.forEach(s => log.step(s.label, s.ok, s.cmd || '', s.output || ''));

  const stepsFailed = r.steps?.some(s => !s.ok);

  if (r.ok && !stepsFailed) {
    log.step(`Ready at ${name}.test`, true, `open http://${name}.test`);
    log.done(true, `${name}.test created`);
    csCreate.innerHTML = '✓ Done';
    csLogArea.innerHTML = `<div style="text-align:center;padding:24px 0;font-size:13px;color:var(--grn)">✓ ${name}.test is ready</div>`;
    setTimeout(() => location.reload(), 1800);
  } else {
    const errMsg = !r.ok ? (r.error || 'Creation failed') : 'One or more steps failed — check the activity log';
    if (!r.ok) log.step(r.error || 'Creation failed', false, '', r.error || '');
    log.done(false, errMsg);
    csLogArea.innerHTML = `<div style="text-align:center;padding:24px 0;font-size:12px;color:var(--red)">✗ ${esc(errMsg)}</div>`;
    csCreate.disabled = false;
    csCreate.innerHTML = '<svg><use href="#i-plus"/></svg> Retry';
  }
});

csName.addEventListener('keydown', e => { if (e.key==='Enter') csCreate.click(); });

// Browse buttons for create-site modal
document.getElementById('cs-dir-browse')?.addEventListener('click', () => browsePick('folder', document.getElementById('cs-dir')));
document.getElementById('cs-plugins-browse')?.addEventListener('click', () => { browsePickAppend('file', document.getElementById('cs-plugins')); _checkClear('cs-plugins'); });
document.getElementById('cs-themes-browse')?.addEventListener('click', () => { browsePickAppend('file', document.getElementById('cs-themes')); _checkClear('cs-themes'); });

// Clear buttons (delegated)
document.getElementById('modal-create-site')?.addEventListener('click', e => {
  const btn = e.target.closest('.fld-clr');
  if (!btn) return;
  const id = btn.dataset.clr;
  if (id === 'cs-tree') { csFolderTree.fromJson({}); return; } // MutationObserver handles visibility
  if (id === 'cs-name') { csName.value = ''; csPreview.textContent = 'sitename.test'; _checkClear('cs-name'); return; }
  const el = document.getElementById(id);
  if (el) { el.value = ''; _checkClear(id); }
});

/* ═══════════════════════════════════════════════════════
   MODAL CLOSE (delegated)
═══════════════════════════════════════════════════════ */
document.addEventListener('click', e => {
  if (e.target.closest('.js-mclose')) closeAllModals();
});
$$('.modal').forEach(m => m.addEventListener('click', e => {
  if (e.target===m) closeAllModals();
}));
