/* ═══════════════════════════════════════════════════════
   THEME
═══════════════════════════════════════════════════════ */
function applyTheme(t) {
  document.documentElement.dataset.theme = t;
  localStorage.setItem('sh-theme', t);
  S.theme = t;
  const u = t==='dark' ? '#i-sun' : '#i-moon';
  $('#theme-btn').querySelector('use').setAttribute('href', u);
  $('#theme-btn').title = t==='dark' ? 'Light mode' : 'Dark mode';
}
$('#theme-btn').addEventListener('click', ()=> applyTheme(S.theme==='dark'?'light':'dark'));
applyTheme(S.theme);

/* ═══════════════════════════════════════════════════════
   SETTINGS FUNCTIONS (apply* run on page load + in panel)
═══════════════════════════════════════════════════════ */
function applyBtnStyle(style) {
  document.body.dataset.btnStyle = style;
  localStorage.setItem('sh-btn-style', style);
  $$('.sp-styleopt').forEach(o => o.classList.toggle('active', o.dataset.style === style));
}

function applyIconSize(px) {
  document.documentElement.style.setProperty('--btn-ic-size', px + 'px');
  localStorage.setItem('sh-ic-size', px);
  const sl = $('#icon-size-slider');
  if (sl) {
    const pct = ((px - +sl.min) / (+sl.max - +sl.min)) * 100;
    sl.style.setProperty('--pct', pct + '%');
    sl.value = px;
  }
  const val = $('#icon-size-val');
  if (val) val.textContent = px + 'px';
}

function applyTextSize(px) {
  document.documentElement.style.setProperty('--btn-txt-size', px + 'px');
  localStorage.setItem('sh-txt-size', px);
  const sl = $('#text-size-slider');
  if (sl) {
    const pct = ((px - +sl.min) / (+sl.max - +sl.min)) * 100;
    sl.style.setProperty('--pct', pct + '%');
    sl.value = px;
  }
  const val = $('#text-size-val');
  if (val) val.textContent = px + 'px';
}

// Apply saved sizes on page load (sliders not in DOM yet at this point, guarded by null checks above)
applyBtnStyle(localStorage.getItem('sh-btn-style') || 'icon-text');
applyIconSize(+(localStorage.getItem('sh-ic-size')  || 12));
applyTextSize(+(localStorage.getItem('sh-txt-size') || 12));

/* ═══════════════════════════════════════════════════════
   COLOR PRESETS
═══════════════════════════════════════════════════════ */
const COLOR_PRESETS = {
  cyber:   { '--cyan':'#00d2ff','--cyan2':'#0099bb','--vio':'#a78bfa','--grn':'#34d399','--amb':'#fbbf24' },
  emerald: { '--cyan':'#10b981','--cyan2':'#059669','--vio':'#6366f1','--grn':'#34d399','--amb':'#f59e0b' },
  purple:  { '--cyan':'#a78bfa','--cyan2':'#7c3aed','--vio':'#ec4899','--grn':'#34d399','--amb':'#fbbf24' },
  amber:   { '--cyan':'#f59e0b','--cyan2':'#d97706','--vio':'#a78bfa','--grn':'#34d399','--amb':'#ef4444' },
  rose:    { '--cyan':'#f43f5e','--cyan2':'#e11d48','--vio':'#8b5cf6','--grn':'#10b981','--amb':'#f59e0b' },
};

// Custom presets — stored in localStorage as { name: { '--cyan':…, … } }
let CUSTOM_PRESETS = (() => {
  try { return JSON.parse(localStorage.getItem('sh-custom-presets') || '{}'); }
  catch { return {}; }
})();

function saveCustomPresets() {
  localStorage.setItem('sh-custom-presets', JSON.stringify(CUSTOM_PRESETS));
}

function renderCustomPresets() {
  const container = document.getElementById('gs-custom-presets');
  const row       = document.getElementById('cp-saved-row');
  if (!container) return;
  const names = Object.keys(CUSTOM_PRESETS);
  if (row) row.style.display = names.length ? '' : 'none';
  const active = localStorage.getItem('sh-preset');
  container.innerHTML = names.map(n => `
    <div class="gs-custom-preset">
      <button class="gs-preset-btn${active === n ? ' active' : ''}" data-preset="${esc(n)}" data-custom="1">${esc(n)}</button>
      <button class="cp-del-btn" data-del-preset="${esc(n)}" title="Delete preset">×</button>
    </div>`).join('');
}

function applyPreset(name) {
  const vars = COLOR_PRESETS[name] || CUSTOM_PRESETS[name];
  if (!vars) return;
  Object.entries(vars).forEach(([k,v]) => document.documentElement.style.setProperty(k, v));
  // Derived rgba vars
  const c = vars['--cyan'];
  document.documentElement.style.setProperty('--cyand',  hexAlpha(c, 0.1));
  document.documentElement.style.setProperty('--cyandh', hexAlpha(c, 0.18));
  document.documentElement.style.setProperty('--bdg',    hexAlpha(c, 0.35));
  localStorage.setItem('sh-preset', name);
  $$('.gs-preset-btn').forEach(b => b.classList.toggle('active', b.dataset.preset === name));
}

function hexAlpha(hex, a) {
  const r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
  return `rgba(${r},${g},${b},${a})`;
}

// Restore saved preset on load
const savedPreset = localStorage.getItem('sh-preset');
if (savedPreset && (COLOR_PRESETS[savedPreset] || CUSTOM_PRESETS[savedPreset])) applyPreset(savedPreset);
