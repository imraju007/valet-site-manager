<!-- ── Custom Preset Tooltip ────────────────────────────────────────────── -->
<div class="cp-tip" id="cp-tip">
  <div class="cp-tip-hdr">New Preset</div>
  <div class="cp-swatches">
    <label class="cp-swatch-item" title="Primary">
      <input type="color" id="cp-cyan" value="#00d2ff">
      <span class="cp-swatch-color" id="cp-swatch-cyan" style="background:#00d2ff"></span>
      <span class="cp-swatch-lbl">Primary</span>
    </label>
    <label class="cp-swatch-item" title="Primary Dark">
      <input type="color" id="cp-cyan2" value="#0099bb">
      <span class="cp-swatch-color" id="cp-swatch-cyan2" style="background:#0099bb"></span>
      <span class="cp-swatch-lbl">Dark</span>
    </label>
    <label class="cp-swatch-item" title="Accent">
      <input type="color" id="cp-vio" value="#a78bfa">
      <span class="cp-swatch-color" id="cp-swatch-vio" style="background:#a78bfa"></span>
      <span class="cp-swatch-lbl">Accent</span>
    </label>
    <label class="cp-swatch-item" title="Success">
      <input type="color" id="cp-grn" value="#34d399">
      <span class="cp-swatch-color" id="cp-swatch-grn" style="background:#34d399"></span>
      <span class="cp-swatch-lbl">Success</span>
    </label>
    <label class="cp-swatch-item" title="Warning">
      <input type="color" id="cp-amb" value="#fbbf24">
      <span class="cp-swatch-color" id="cp-swatch-amb" style="background:#fbbf24"></span>
      <span class="cp-swatch-lbl">Warning</span>
    </label>
  </div>
  <div class="cp-tip-foot">
    <input type="text" class="cp-tip-name" id="cp-name" placeholder="Preset name…" maxlength="32">
    <div class="cp-form-btns">
      <button class="btn btn-s btn-prim" id="cp-save-btn">Save</button>
      <button class="btn btn-s" id="cp-cancel-btn">✕</button>
    </div>
  </div>
</div>

<!-- ── Task Tooltip ────────────────────────────────────────────────────── -->
<div class="task-tip" id="task-tip">
  <div class="task-tip-hdr">
    <span class="task-tip-site" id="task-tip-site"></span>
    <button class="task-tip-goto" id="task-tip-goto">Open board →</button>
  </div>
  <div class="task-tip-counts" id="task-tip-counts"></div>
  <div class="task-tip-body" id="task-tip-body"></div>
</div>
