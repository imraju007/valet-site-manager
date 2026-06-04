/* ═══════════════════════════════════════════════════════
   FOLDER TREE BUILDER
═══════════════════════════════════════════════════════ */
function createFolderTree() {
  let _nodes = [];
  let _el    = null;

  const _uid = () => Math.random().toString(36).slice(2, 9);

  function _find(list, id) {
    for (const n of list) {
      if (n.id === id) return n;
      if (n.children?.length) { const f = _find(n.children, id); if (f) return f; }
    }
    return null;
  }

  function _remove(list, id) {
    const i = list.findIndex(n => n.id === id);
    if (i >= 0) { list.splice(i, 1); return true; }
    for (const n of list) { if (n.children && _remove(n.children, id)) return true; }
    return false;
  }

  function toJson(list = _nodes) {
    const obj = {};
    for (const n of list) obj[n.name] = n.type === 'folder' ? toJson(n.children) : null;
    return obj;
  }

  function fromJson(obj) {
    function parse(o) {
      return Object.entries(o || {}).map(([name, val]) => ({
        id: _uid(), name, type: val === null ? 'file' : 'folder',
        children: val !== null ? parse(val) : []
      }));
    }
    _nodes = parse(obj || {});
    _render();
  }

  function _render() {
    if (!_el) return;
    _el.innerHTML = '';
    _renderList(_nodes, _el, 0);
    _el.appendChild(_mkActions('', 0));
  }

  function _renderList(list, container, depth) {
    list.forEach(n => container.appendChild(_mkNode(n, depth)));
  }

  function _mkNode(n, depth) {
    const wrap = document.createElement('div');
    wrap.className = 'ft-node';
    wrap.dataset.id = n.id;

    const row = document.createElement('div');
    row.className = 'ft-row';
    row.style.paddingLeft = (depth * 18 + 10) + 'px';
    row.innerHTML =
      (n.type === 'folder'
        ? `<button class="ft-toggle" data-ft-toggle="${n.id}"><svg width="7" height="7"><use href="#i-chevd"/></svg></button>`
        : `<span class="ft-spacer"></span>`) +
      `<span class="ft-ic">${n.type === 'folder' ? '📁' : '📄'}</span>` +
      `<span class="ft-lbl" data-ft-rename="${n.id}">${esc(n.name)}</span>` +
      `<button class="ft-del" data-ft-del="${n.id}" title="Remove">✕</button>`;
    wrap.appendChild(row);

    if (n.type === 'folder') {
      const kids = document.createElement('div');
      kids.className = 'ft-children';
      _renderList(n.children, kids, depth + 1);
      kids.appendChild(_mkActions(n.id, depth + 1));
      wrap.appendChild(kids);
    }
    return wrap;
  }

  function _mkActions(parentId, depth) {
    const el = document.createElement('div');
    el.className = 'ft-actions';
    el.style.paddingLeft = (depth * 18 + 10) + 'px';
    el.innerHTML =
      `<button class="ft-add" data-ft-parent="${parentId}" data-ft-type="folder">` +
        `<svg width="8" height="8"><use href="#i-plus"/></svg> Folder` +
      `</button>` +
      `<button class="ft-add" data-ft-parent="${parentId}" data-ft-type="file">` +
        `<svg width="8" height="8"><use href="#i-plus"/></svg> File` +
      `</button>`;
    return el;
  }

  function _showInput(addBtn, parentId, type, depth) {
    const row = document.createElement('div');
    row.className = 'ft-row ft-input-row';
    row.style.paddingLeft = (depth * 18 + 10) + 'px';
    row.innerHTML =
      `<span class="ft-spacer"></span>` +
      `<span class="ft-ic">${type === 'folder' ? '📁' : '📄'}</span>` +
      `<input class="ft-input" type="text" placeholder="${type === 'folder' ? 'folder-name' : 'file.ext'}">` +
      `<button class="ft-ok">↵</button><button class="ft-esc">✕</button>`;
    addBtn.parentElement.insertBefore(row, addBtn);
    const inp = row.querySelector('.ft-input');
    inp.focus();

    const commit = () => {
      const name = inp.value.trim().replace(/\//g, '');
      if (name) {
        const node = { id: _uid(), name, type, children: [] };
        if (!parentId) _nodes.push(node);
        else { const p = _find(_nodes, parentId); if (p) p.children.push(node); }
      }
      _render();
    };
    row.querySelector('.ft-ok').addEventListener('click', commit);
    row.querySelector('.ft-esc').addEventListener('click', _render);
    inp.addEventListener('keydown', e => {
      if (e.key === 'Enter') commit();
      if (e.key === 'Escape') _render();
    });
  }

  function _startRename(id) {
    const lbl = _el.querySelector(`[data-ft-rename="${id}"]`);
    if (!lbl) return;
    const node = _find(_nodes, id);
    if (!node) return;
    const inp = document.createElement('input');
    inp.className = 'ft-input ft-inline-input';
    inp.value = node.name;
    lbl.replaceWith(inp);
    inp.focus(); inp.select();
    const commit = () => { node.name = inp.value.trim().replace(/\//g, '') || node.name; _render(); };
    inp.addEventListener('blur', commit);
    inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); commit(); } if (e.key === 'Escape') _render(); });
  }

  function init(elId) {
    _el = document.getElementById(elId);
    if (!_el) return;
    _el.addEventListener('click', e => {
      const toggleId = e.target.closest('[data-ft-toggle]')?.dataset.ftToggle;
      if (toggleId) {
        const kids = _el.querySelector(`[data-id="${toggleId}"] .ft-children`);
        const btn  = e.target.closest('[data-ft-toggle]');
        if (kids) { const hidden = kids.style.display === 'none'; kids.style.display = hidden ? '' : 'none'; btn.classList.toggle('open', hidden); }
        return;
      }
      const delId = e.target.closest('[data-ft-del]')?.dataset.ftDel;
      if (delId) { _remove(_nodes, delId); _render(); return; }
      const addBtn = e.target.closest('[data-ft-parent]');
      if (addBtn) {
        const pid   = addBtn.dataset.ftParent;
        const type  = addBtn.dataset.ftType;
        const depth = pid ? (_el.querySelector(`[data-id="${pid}"]`)?.querySelectorAll('.ft-children').length + 1 || 1) : 0;
        _showInput(addBtn, pid, type, depth);
        return;
      }
    });
    _el.addEventListener('dblclick', e => {
      const renameId = e.target.closest('[data-ft-rename]')?.dataset.ftRename;
      if (renameId) _startRename(renameId);
    });
    _render();
  }

  return { init, fromJson, toJson };
}
const folderTree = createFolderTree();

/* ═══════════════════════════════════════════════════════
   BUTTON VISIBILITY
═══════════════════════════════════════════════════════ */
const BTN_VIS_KEY = 'sh-btn-vis';
const BTN_VIS_MAP = {
  open:     'hide-btn-open',
  ide:      'hide-btn-ide',
  finder:   'hide-btn-finder',
  tasks:    'hide-btn-tasks',
  delete:   'hide-btn-delete',
  admin:    'hide-btn-admin',
  terminal: 'hide-btn-terminal',
  wpcli:    'hide-btn-wpcli',
  log:      'hide-btn-log',
  debug:    'hide-btn-debug',
  clean:    'hide-btn-clean',
};

function loadBtnVis() {
  try {
    const saved = JSON.parse(localStorage.getItem(BTN_VIS_KEY) || '{}');
    return Object.fromEntries(Object.keys(BTN_VIS_MAP).map(k => [k, saved[k] !== false]));
  } catch {
    return Object.fromEntries(Object.keys(BTN_VIS_MAP).map(k => [k, true]));
  }
}

function applyBtnVisibility(vis) {
  for (const [key, cls] of Object.entries(BTN_VIS_MAP))
    document.body.classList.toggle(cls, !vis[key]);
}

applyBtnVisibility(loadBtnVis());

/* ═══════════════════════════════════════════════════════
   LOG SIZE BADGE VISIBILITY
═══════════════════════════════════════════════════════ */
const LOG_SZ_KEY = 'sh-log-sz';

function loadLogSzVis() {
  return localStorage.getItem(LOG_SZ_KEY) !== 'false'; // default true
}

function applyLogSzVis(show) {
  document.body.classList.toggle('hide-log-sz', !show);
}

applyLogSzVis(loadLogSzVis());

/* ═══════════════════════════════════════════════════════
   SETTINGS PANEL INIT
═══════════════════════════════════════════════════════ */
function initSettingsPanel() {
  // accordion — only one open at a time
  document.getElementById('panel-settings')?.addEventListener('click', e => {
    const hdr = e.target.closest('.settings-acc-hdr');
    if (!hdr) return;
    const section = hdr.closest('.settings-section');
    const isOpen  = section.classList.contains('open');
    $$('.settings-section').forEach(s => s.classList.remove('open'));
    if (!isOpen) section.classList.add('open');
  });

  // btn style
  document.getElementById('btn-style-opts')?.addEventListener('click', e => {
    const opt = e.target.closest('.sp-styleopt');
    if (!opt) return;
    applyBtnStyle(opt.dataset.style);
    toast('Button style saved');
  });
  applyBtnStyle(localStorage.getItem('sh-btn-style') || 'icon-text');

  // sliders — live preview on input, toast on release
  $('#icon-size-slider')?.addEventListener('input', e => applyIconSize(+e.target.value));
  $('#text-size-slider')?.addEventListener('input', e => applyTextSize(+e.target.value));
  $('#icon-size-slider')?.addEventListener('change', () => toast('Icon size saved'));
  $('#text-size-slider')?.addEventListener('change', () => toast('Text size saved'));
  applyIconSize(+(localStorage.getItem('sh-ic-size')  || 12));
  applyTextSize(+(localStorage.getItem('sh-txt-size') || 12));

  // built-in presets
  $$('.gs-preset-btn').forEach(b => b.addEventListener('click', () => {
    applyPreset(b.dataset.preset);
    toast(`${b.textContent.trim()} preset applied`);
  }));
  renderCustomPresets();

  // custom presets — delegated click on container
  document.getElementById('gs-custom-presets')?.addEventListener('click', e => {
    const delBtn = e.target.closest('.cp-del-btn');
    if (delBtn) {
      const name = delBtn.dataset.delPreset;
      delete CUSTOM_PRESETS[name];
      saveCustomPresets();
      renderCustomPresets();
      if (localStorage.getItem('sh-preset') === name) applyPreset('cyber');
      toast(`Preset "${name}" deleted`);
      return;
    }
    const presetBtn = e.target.closest('.gs-preset-btn[data-custom]');
    if (presetBtn) {
      applyPreset(presetBtn.dataset.preset);
      toast(`${presetBtn.dataset.preset} preset applied`);
    }
  });

  // custom preset tooltip
  const cpToggle  = document.getElementById('cp-toggle-btn');
  const cpTip     = document.getElementById('cp-tip');
  const cpColorMap = [
    { id: 'cp-cyan',  cssVar: '--cyan'  },
    { id: 'cp-cyan2', cssVar: '--cyan2' },
    { id: 'cp-vio',   cssVar: '--vio'   },
    { id: 'cp-grn',   cssVar: '--grn'   },
    { id: 'cp-amb',   cssVar: '--amb'   },
  ];

  function closeCpTip() {
    cpTip?.classList.remove('visible');
    cpToggle?.classList.remove('open');
  }

  function seedColorPickers() {
    cpColorMap.forEach(({ id, cssVar }) => {
      const input  = document.getElementById(id);
      const swatch = document.getElementById('cp-swatch-' + id.replace('cp-', ''));
      if (!input) return;
      const val = getComputedStyle(document.documentElement).getPropertyValue(cssVar).trim();
      if (val && /^#[0-9a-f]{6}$/i.test(val)) {
        input.value = val;
        if (swatch) swatch.style.background = val;
      }
    });
  }

  cpToggle?.addEventListener('click', () => {
    if (cpTip.classList.contains('visible')) { closeCpTip(); return; }
    seedColorPickers();
    document.getElementById('cp-name').value = '';
    const rect   = cpToggle.getBoundingClientRect();
    const tipW   = 240;
    let left = rect.left;
    if (left + tipW > window.innerWidth - 12) left = window.innerWidth - tipW - 12;
    if (left < 8) left = 8;
    cpTip.style.left = left + 'px';
    cpTip.style.top  = (rect.bottom + 6) + 'px';
    cpTip.classList.add('visible');
    cpToggle.classList.add('open');
  });

  // live color preview while picking
  cpColorMap.forEach(({ id, cssVar }) => {
    document.getElementById(id)?.addEventListener('input', e => {
      const val = e.target.value;
      document.documentElement.style.setProperty(cssVar, val);
      const swatch = document.getElementById('cp-swatch-' + id.replace('cp-', ''));
      if (swatch) swatch.style.background = val;
      if (cssVar === '--cyan') {
        document.documentElement.style.setProperty('--cyand',  hexAlpha(val, 0.1));
        document.documentElement.style.setProperty('--cyandh', hexAlpha(val, 0.18));
        document.documentElement.style.setProperty('--bdg',    hexAlpha(val, 0.35));
      }
    });
  });

  document.getElementById('cp-cancel-btn')?.addEventListener('click', closeCpTip);

  document.getElementById('cp-save-btn')?.addEventListener('click', () => {
    const nameInput = document.getElementById('cp-name');
    const name = nameInput.value.trim();
    if (!name) { nameInput.focus(); return; }
    if (COLOR_PRESETS[name]) { toast('That name is reserved for a built-in preset', 'err'); return; }
    const vars = {};
    cpColorMap.forEach(({ id, cssVar }) => {
      vars[cssVar] = document.getElementById(id)?.value || '';
    });
    CUSTOM_PRESETS[name] = vars;
    saveCustomPresets();
    applyPreset(name);
    renderCustomPresets();
    closeCpTip();
    toast(`Preset "${name}" saved`);
  });

  // button visibility toggles — seed from localStorage, wire change
  const _btnVis = loadBtnVis();
  $$('[data-btn-key]').forEach(cb => {
    cb.checked = _btnVis[cb.dataset.btnKey];
    cb.addEventListener('change', () => {
      _btnVis[cb.dataset.btnKey] = cb.checked;
      localStorage.setItem(BTN_VIS_KEY, JSON.stringify(_btnVis));
      applyBtnVisibility(_btnVis);
      const label = cb.closest('.btn-vis-row')?.querySelector('span')?.textContent?.trim() || cb.dataset.btnKey;
      toast(cb.checked ? `${label} button shown` : `${label} button hidden`);
      if (cb.dataset.btnKey === 'log') syncLogSubopts();
    });
  });

  // log size badge sub-option
  const logSzTgl    = $('#tgl-log-sz');
  const logSubopts  = $('#log-subopts');
  const logBtnCb    = document.querySelector('[data-btn-key="log"]');

  function syncLogSubopts() {
    if (logSubopts) logSubopts.classList.toggle('hidden', logBtnCb ? !logBtnCb.checked : false);
  }
  syncLogSubopts();

  if (logSzTgl) {
    logSzTgl.checked = loadLogSzVis();
    logSzTgl.addEventListener('change', () => {
      localStorage.setItem(LOG_SZ_KEY, logSzTgl.checked ? 'true' : 'false');
      applyLogSzVis(logSzTgl.checked);
      toast(logSzTgl.checked ? 'Log size badge shown' : 'Log size badge hidden');
    });
  }

  // folder tree init
  folderTree.init('gs-tree');

  // save new_site_default
  document.getElementById('gs-save')?.addEventListener('click', async () => {
    const plugins  = $('#gs-plugins').value.trim().split('\n').map(s => s.trim()).filter(Boolean);
    const themes   = $('#gs-themes').value.trim().split('\n').map(s => s.trim()).filter(Boolean);
    const folder_structure = folderTree.toJson();
    const wp_parent_dir     = $('#gs-wp-dir')?.value.trim()     || '';
    const static_parent_dir = $('#gs-static-dir')?.value.trim() || '';
    const r = await api('save_settings', { section: 'new_site_default', data: { plugins, themes, folder_structure, wp_parent_dir, static_parent_dir } }, 'POST');
    if (r.ok) toast('Settings saved');
    else toast(r.error || 'Save failed', 'err');
  });

  // browse buttons for parent directories
  document.getElementById('gs-wp-dir-browse')?.addEventListener('click', () => browsePick('folder', document.getElementById('gs-wp-dir')));
  document.getElementById('gs-static-dir-browse')?.addEventListener('click', () => browsePick('folder', document.getElementById('gs-static-dir')));

  // save wp_admin_config
  document.getElementById('gs-creds-save')?.addEventListener('click', async () => {
    const r = await api('save_settings', {
      section: 'wp_admin_config',
      data: {
        user:     $('#gs-admin-user').value.trim(),
        password: $('#gs-admin-pass').value.trim(),
        email:    $('#gs-admin-email').value.trim(),
      },
    }, 'POST');
    if (r.ok) toast('Credentials saved');
    else toast(r.error || 'Save failed', 'err');
  });

  // save db_config
  document.getElementById('gs-db-save')?.addEventListener('click', async () => {
    const r = await api('save_settings', {
      section: 'db_config',
      data: {
        host:     $('#gs-db-host').value.trim(),
        user:     $('#gs-db-user').value.trim(),
        password: $('#gs-db-pass').value.trim(),
        pma_url:  $('#gs-pma-url')?.value.trim() || '',
      },
    }, 'POST');
    if (r.ok) toast('DB config saved');
    else toast(r.error || 'Save failed', 'err');
  });

  // browse buttons in settings
  document.getElementById('gs-plugins-browse')?.addEventListener('click', () => browsePickAppend('file', document.getElementById('gs-plugins')));
  document.getElementById('gs-themes-browse')?.addEventListener('click', () => browsePickAppend('file', document.getElementById('gs-themes')));
}
initSettingsPanel();
