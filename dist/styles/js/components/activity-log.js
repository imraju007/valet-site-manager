/* ═══════════════════════════════════════════════════════
   ACTIVITY LOG PANEL
═══════════════════════════════════════════════════════ */
const actLog = (() => {
  const panel  = () => $('#act-log');
  const body   = () => $('#act-log-body');
  const dot    = () => $('#act-log-dot');

  const LOG_KEY = 'vsm_act_log';
  const LOG_TTL = 86400000; // 1 day in ms
  const LOG_MAX = 100;

  function _now() { return new Date().toTimeString().slice(0, 8); }

  function _setDot(cls) {
    const d = dot(); if (!d) return;
    d.className = 'act-log-dot' + (cls ? ' ' + cls : '');
  }

  // ── localStorage helpers ─────────────────────────────────
  function _readStorage() {
    try {
      const raw = localStorage.getItem(LOG_KEY);
      if (!raw) return [];
      const cutoff = Date.now() - LOG_TTL;
      return JSON.parse(raw).filter(e => e.ts > cutoff);
    } catch(e) { return []; }
  }

  function _saveEntry(entry) {
    const entries = [entry, ..._readStorage()].slice(0, LOG_MAX);
    try { localStorage.setItem(LOG_KEY, JSON.stringify(entries)); } catch(e) {}
  }

  function _clearStorage() {
    localStorage.removeItem(LOG_KEY);
  }

  // ── Render a single entry into the log body ──────────────
  function _renderGroup(entry, insertFn) {
    const b = body();
    if (!b) return;
    const group = document.createElement('div');
    group.className = 'al-group';
    group.innerHTML =
      `<div class="al-group-hdr">` +
        `<span class="al-group-title">${esc(entry.title)}</span>` +
        `<span class="al-time">${esc(entry.time)}</span>` +
      `</div>` +
      `<div class="al-steps"></div>`;
    const stepsEl = group.querySelector('.al-steps');
    (entry.steps || []).forEach(s => {
      const el = document.createElement('div');
      el.className = `al-step ${s.ok ? 'ok' : 'err'}`;
      const outText = (s.output || '').trim();
      el.innerHTML =
        `<div class="al-cmd-line">` +
          `<span class="al-status-ic">${s.ok ? '✓' : '✗'}</span>` +
          `<span class="al-prompt">$</span>` +
          `<span class="al-cmd">${esc(s.cmd || s.label)}</span>` +
        `</div>` +
        (outText ? `<div class="al-out">${esc(outText)}</div>` : '');
      stepsEl.appendChild(el);
    });
    insertFn(group);
  }

  // ── Restore persisted entries on page load ───────────────
  function _restore() {
    const entries = _readStorage();
    if (!entries.length) return;
    const b = body();
    // Iterate oldest→newest, insert each at top → newest ends up at top
    for (let i = entries.length - 1; i >= 0; i--) {
      _renderGroup(entries[i], g => b?.insertBefore(g, b.firstChild));
    }
    _setDot(entries[0]?.ok ? 'ok' : 'err');
  }

  // ── Public controls ──────────────────────────────────────
  function open()   { panel()?.classList.add('open'); document.body.classList.add('al-open'); }
  function close()  { panel()?.classList.remove('open'); document.body.classList.remove('al-open'); }
  function toggle() {
    const p = panel(); if (!p) return;
    p.classList.toggle('open');
    document.body.classList.toggle('al-open', p.classList.contains('open'));
  }
  function clear() {
    const b = body();
    if (b) b.innerHTML = '';
    _setDot('');
    _clearStorage();
  }

  let _running = 0;

  function action(title) {
    open();
    _running++;
    _setDot('running');

    const group = document.createElement('div');
    group.className = 'al-group';

    const time = _now();
    const hdr = document.createElement('div');
    hdr.className = 'al-group-hdr';
    hdr.innerHTML = `<span class="al-group-title">${esc(title)}</span><span class="al-time">${time}</span>`;
    group.appendChild(hdr);

    const stepsEl = document.createElement('div');
    stepsEl.className = 'al-steps';
    group.appendChild(stepsEl);

    const b = body();
    if (b) b.insertBefore(group, b.firstChild);

    const pendEl = document.createElement('div');
    pendEl.className = 'al-pending';
    pendEl.innerHTML = `<span class="al-cursor">▋</span><span>executing…</span>`;
    stepsEl.appendChild(pendEl);

    const _steps = [];

    function step(label, ok, cmd = '', output = '') {
      _steps.push({ label, ok, cmd, output });
    }

    function done(ok = true, msg = null) {
      pendEl.remove();
      _running = Math.max(0, _running - 1);
      if (_running === 0) _setDot(ok ? 'ok' : 'err');

      if (msg !== false) toast(msg ?? title, ok ? 'ok' : 'err');

      _steps.forEach((s, i) => {
        setTimeout(() => {
          const el = document.createElement('div');
          el.className = `al-step ${s.ok ? 'ok' : 'err'}`;
          const outText = (s.output || '').trim();
          el.innerHTML =
            `<div class="al-cmd-line">` +
              `<span class="al-status-ic">${s.ok ? '✓' : '✗'}</span>` +
              `<span class="al-prompt">$</span>` +
              `<span class="al-cmd">${esc(s.cmd || s.label)}</span>` +
            `</div>` +
            (outText ? `<div class="al-out">${esc(outText)}</div>` : '');
          stepsEl.appendChild(el);
          if (b) b.scrollTop = 0;
        }, i * 50);
      });

      // Save to localStorage after animation finishes
      setTimeout(() => {
        _saveEntry({ ts: Date.now(), title, time, ok, steps: _steps });
      }, _steps.length * 50 + 150);
    }

    function passwordPrompt(label) {
      return new Promise(resolve => {
        pendEl.style.display = 'none';
        const row = document.createElement('div');
        row.className = 'al-sudo-prompt';
        row.innerHTML =
          `<div class="al-sudo-label"><span class="al-sudo-icon">🔒</span><span>${esc(label)}</span></div>` +
          `<div class="al-sudo-fields">` +
            `<input type="password" class="al-sudo-input" placeholder="macOS password…" autocomplete="current-password">` +
            `<button class="al-sudo-btn">Continue ↵</button>` +
          `</div>`;
        stepsEl.appendChild(row);
        const input = row.querySelector('.al-sudo-input');
        const btn   = row.querySelector('.al-sudo-btn');
        input.focus();
        function submit() {
          const pass = input.value;
          row.remove();
          pendEl.style.display = '';
          _steps.push({ label: 'sudo password provided', ok: true, cmd: 'sudo -S -v', output: '' });
          resolve(pass);
        }
        btn.addEventListener('click', submit);
        input.addEventListener('keydown', e => { if (e.key === 'Enter') submit(); });
      });
    }

    return { step, done, passwordPrompt };
  }

  _restore();

  $('#act-log-hdr')?.addEventListener('click', e => {
    if (!e.target.closest('#act-log-clear') && !e.target.closest('#act-log-toggle')) toggle();
    else if (e.target.closest('#act-log-toggle')) toggle();
  });
  $('#act-log-clear')?.addEventListener('click', e => { e.stopPropagation(); clear(); });

  return { open, close, toggle, clear, action };
})();
