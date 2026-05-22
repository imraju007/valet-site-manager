/* ═══════════════════════════════════════════════════════
   API + UTILITIES
═══════════════════════════════════════════════════════ */
function toast(msg, type='ok') {
  const el = document.createElement('div');
  el.className = `toast toast-${type}`;
  el.textContent = msg;
  $('#toasts').appendChild(el);
  setTimeout(() => el.remove(), 3000);
}

async function api(action, payload=null, method=null) {
  const isGet = method==='GET' || (!payload && method!=='POST');
  let url = `api.php?action=${encodeURIComponent(action)}`;
  const opts = { headers: {'Content-Type':'application/json', 'X-CSRF-Token': CSRF_TOKEN} };
  if (isGet) {
    if (payload) url += '&' + new URLSearchParams(payload);
    opts.method = 'GET';
  } else {
    opts.method = 'POST';
    opts.body = JSON.stringify(payload ?? {});
  }
  try {
    const r = await fetch(url, opts);
    return r.json();
  } catch(e) {
    return { ok: false, error: e.message };
  }
}

async function browsePick(type, inputEl) {
  const r = await api('pick_path', { type }, 'GET');
  if (r.ok && r.path) inputEl.value = r.path;
}

async function browsePickAppend(type, textareaEl) {
  const r = await api('pick_path', { type }, 'GET');
  if (r.ok && r.path) {
    const cur = textareaEl.value.trim();
    textareaEl.value = cur ? cur + '\n' + r.path : r.path;
  }
}

function fmtBytes(b) {
  if (b < 1024) return b + ' B';
  if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
  return (b/1048576).toFixed(1) + ' MB';
}

function openModal(id)  { $(`#${id}`).classList.add('open') }
function closeModal(id) { $(`#${id}`).classList.remove('open') }
function closeAllModals(){ $$('.modal.open').forEach(m=>m.classList.remove('open')) }
