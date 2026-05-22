/* ═══════════════════════════════════════════════════════
   TERMINAL PANEL
═══════════════════════════════════════════════════════ */
const TP = $('#terminal-panel');

document.addEventListener('click', e => {
  const btn = e.target.closest('.js-term');
  if (!btn) return;
  openTerminal(btn.dataset.site, btn.dataset.mode || 'bash');
});

$('#tp-close').addEventListener('click', closeTerminal);

$$('.tp-tab').forEach(t => t.addEventListener('click', () => {
  $$('.tp-tab').forEach(x => x.classList.remove('active'));
  t.classList.add('active');
  S.tp.mode = t.dataset.mode;
  renderQuickCmds();
  updatePrompt();
}));

function openTerminal(site, mode='bash') {
  S.tp.site = site;
  S.tp.mode = mode;
  S.tp.hist = [];
  S.tp.hIdx = -1;
  $('#tp-site-name').textContent = site + '.test';
  $$('.tp-tab').forEach(t => t.classList.toggle('active', t.dataset.mode===mode));
  $('#tp-out').innerHTML = '';
  renderQuickCmds();
  updatePrompt();
  tpLine(`Connected to ${site}`, 'info');
  tpLine(`Path: /.../${site}`, 'info');
  tpSep();
  TP.classList.add('open');
  S.tp.open = true;
  document.body.classList.add('tp-open');
  setTimeout(() => $('#tp-cmd').focus(), 250);
}

function closeTerminal() {
  TP.classList.remove('open');
  S.tp.open = false;
  document.body.classList.remove('tp-open');
}

function updatePrompt() {
  $('#tp-prompt').textContent = S.tp.mode==='wpcli' ? 'wp>' : '$';
}

function renderQuickCmds() {
  const cmds = QUICK_CMDS[S.tp.mode] || [];
  $('#tp-quick').innerHTML = cmds.map(c =>
    `<button class="qc" data-cmd="${esc(c)}">${esc(c)}</button>`).join('');
}

$('#tp-quick').addEventListener('click', e => {
  const btn = e.target.closest('.qc');
  if (!btn) return;
  $('#tp-cmd').value = btn.dataset.cmd;
  $('#tp-cmd').focus();
  runTermCmd();
});

$('#tp-run').addEventListener('click', runTermCmd);
$('#tp-cmd').addEventListener('keydown', e => {
  if (e.key==='Enter') { e.preventDefault(); runTermCmd(); return; }
  if (e.key==='ArrowUp') {
    e.preventDefault();
    if (S.tp.hist.length) {
      S.tp.hIdx = Math.min(S.tp.hIdx+1, S.tp.hist.length-1);
      $('#tp-cmd').value = S.tp.hist[S.tp.hIdx];
    }
    return;
  }
  if (e.key==='ArrowDown') {
    e.preventDefault();
    S.tp.hIdx = Math.max(S.tp.hIdx-1, -1);
    $('#tp-cmd').value = S.tp.hIdx<0 ? '' : S.tp.hist[S.tp.hIdx];
  }
});

async function runTermCmd() {
  const raw = $('#tp-cmd').value.trim();
  if (!raw || !S.tp.site) return;
  const cmd = S.tp.mode==='wpcli' && !raw.startsWith('wp ') && !raw.startsWith('php ') && !raw.startsWith('ls') && !raw.startsWith('pwd')
    ? 'wp ' + raw : raw;
  S.tp.hist.unshift(raw);
  S.tp.hIdx = -1;
  $('#tp-cmd').value = '';
  tpLine(`${S.tp.mode==='wpcli'?'wp>':'$'} ${cmd}`, 'prompt');
  tpLine('<span class="spin"></span>', 'out', true);

  const r = await api('run_cmd', { site: S.tp.site, cmd }, 'POST');
  // Remove last spinner line
  const lines = $$('.tl', $('#tp-out'));
  if (lines.length) lines[lines.length-1].remove();

  if (!r.ok) {
    tpLine(`Error: ${r.error}`, 'err');
  } else if (r.output) {
    tpLine(r.output, r.exit_code===0 ? 'out' : 'err');
  } else {
    tpLine('(no output)', 'info');
  }
  tpSep();
  $('#tp-out').scrollTop = $('#tp-out').scrollHeight;
}

function tpLine(text, type='out', raw=false) {
  const div = document.createElement('div');
  div.className = `tl tl-${type}`;
  if (type==='prompt') {
    div.innerHTML = `<span style="color:var(--cyan)">${esc(text.slice(0,2))}</span>${esc(text.slice(2))}`;
  } else if (raw) {
    div.innerHTML = text;
  } else {
    div.textContent = text;
  }
  $('#tp-out').appendChild(div);
}

function tpSep() {
  const div = document.createElement('div');
  div.className = 'tl-sep';
  $('#tp-out').appendChild(div);
}
