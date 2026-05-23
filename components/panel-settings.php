<!-- ── Settings Panel ─────────────────────────────────────────────────── -->
<div class="panel hidden" id="panel-settings">
  <div class="settings-layout">

    <!-- ── Appearance ─────────────────────────────────────────────────── -->
    <section class="settings-section">
      <button class="settings-acc-hdr">
        Appearance
        <svg width="14" height="14"><use href="#i-chevd"/></svg>
      </button>
      <div class="settings-acc-body">
        <div class="settings-acc-inner">
          <div class="app-cols">

            <!-- ── Left: display options ───────────────────────────────── -->
            <div class="app-col">

              <div class="app-field">
                <div class="app-field-label">Action Button Style</div>
                <div class="sp-opts" id="btn-style-opts">
                  <button class="sp-styleopt" data-style="icon-text">
                    <svg width="13" height="13"><use href="#i-globe"/></svg>
                    <span>Icon + Text</span><span class="sp-styleopt-chk">✓</span>
                  </button>
                  <button class="sp-styleopt" data-style="icon-only">
                    <svg width="13" height="13"><use href="#i-globe"/></svg>
                    <span>Icon Only</span><span class="sp-styleopt-chk">✓</span>
                  </button>
                  <button class="sp-styleopt" data-style="text-only">
                    <span>Text Only</span><span class="sp-styleopt-chk">✓</span>
                  </button>
                </div>
              </div>

              <div class="app-field">
                <div class="app-field-label">Icon Size</div>
                <div class="sp-slider-row">
                  <input type="range" class="sp-slider" id="icon-size-slider" min="9" max="20" step="1" value="12">
                  <span class="sp-slider-val" id="icon-size-val">12px</span>
                </div>
              </div>

              <div class="app-field">
                <div class="app-field-label">Text Size</div>
                <div class="sp-slider-row">
                  <input type="range" class="sp-slider" id="text-size-slider" min="9" max="16" step="1" value="12">
                  <span class="sp-slider-val" id="text-size-val">12px</span>
                </div>
              </div>

            </div>

            <div class="app-col-div"></div>

            <!-- ── Right: color presets ────────────────────────────────── -->
            <div class="app-col">

              <div class="app-field">
                <div class="app-field-label">Color Preset</div>
                <div class="gs-presets" id="gs-presets">
                  <button class="gs-preset-btn" data-preset="cyber">Cyber Blue</button>
                  <button class="gs-preset-btn" data-preset="emerald">Emerald</button>
                  <button class="gs-preset-btn" data-preset="purple">Purple Haze</button>
                  <button class="gs-preset-btn" data-preset="amber">Amber</button>
                  <button class="gs-preset-btn" data-preset="rose">Rose</button>
                </div>
              </div>

              <!-- Shown once at least one custom preset exists -->
              <div class="app-field" id="cp-saved-row" style="display:none">
                <div class="app-field-label">Custom Presets</div>
                <div id="gs-custom-presets" class="gs-presets"></div>
              </div>

              <!-- Custom preset builder trigger -->
              <div class="cp-builder">
                <button class="cp-new-btn" id="cp-toggle-btn">
                  <svg width="9" height="9"><use href="#i-plus"/></svg>
                  New preset
                </button>
              </div>

            </div><!-- /app-col right -->
          </div><!-- /app-cols -->

          <!-- ── Button Visibility ─────────────────────────────────────── -->
          <div class="app-sec-div"></div>
          <div class="app-field-label" style="margin-bottom:12px">Action Button Visibility</div>
          <div class="btn-vis-cols">

            <div class="btn-vis-col">
              <div class="btn-vis-col-hdr">All Sites</div>
              <label class="btn-vis-row"><span>Open</span><span class="tgl-sw"><input type="checkbox" data-btn-key="open"><span class="tgl-track"></span></span></label>
              <label class="btn-vis-row"><span>IDE</span><span class="tgl-sw"><input type="checkbox" data-btn-key="ide"><span class="tgl-track"></span></span></label>
              <label class="btn-vis-row"><span>Finder</span><span class="tgl-sw"><input type="checkbox" data-btn-key="finder"><span class="tgl-track"></span></span></label>
              <label class="btn-vis-row"><span>Tasks</span><span class="tgl-sw"><input type="checkbox" data-btn-key="tasks"><span class="tgl-track"></span></span></label>
              <label class="btn-vis-row"><span>Delete</span><span class="tgl-sw"><input type="checkbox" data-btn-key="delete"><span class="tgl-track"></span></span></label>
            </div>

            <div class="btn-vis-div"></div>

            <div class="btn-vis-col">
              <div class="btn-vis-col-hdr">WordPress Only</div>
              <label class="btn-vis-row"><span>Admin</span><span class="tgl-sw"><input type="checkbox" data-btn-key="admin"><span class="tgl-track"></span></span></label>
              <label class="btn-vis-row"><span>Terminal</span><span class="tgl-sw"><input type="checkbox" data-btn-key="terminal"><span class="tgl-track"></span></span></label>
              <label class="btn-vis-row"><span>WP-CLI</span><span class="tgl-sw"><input type="checkbox" data-btn-key="wpcli"><span class="tgl-track"></span></span></label>
              <label class="btn-vis-row"><span>Log</span><span class="tgl-sw"><input type="checkbox" data-btn-key="log"><span class="tgl-track"></span></span></label>
              <div class="btn-vis-sub" id="log-subopts">
                <label class="btn-vis-row btn-vis-sub-row"><span>Size badge</span><span class="tgl-sw"><input type="checkbox" id="tgl-log-sz"><span class="tgl-track"></span></span></label>
              </div>
              <label class="btn-vis-row"><span>Debug</span><span class="tgl-sw"><input type="checkbox" data-btn-key="debug"><span class="tgl-track"></span></span></label>
              <label class="btn-vis-row"><span>Clean DB</span><span class="tgl-sw"><input type="checkbox" data-btn-key="clean"><span class="tgl-track"></span></span></label>
            </div>

          </div><!-- /btn-vis-cols -->

        </div>
      </div>
    </section>

    <!-- ── New Site Defaults ───────────────────────────────────────────── -->
    <section class="settings-section">
      <button class="settings-acc-hdr">
        New Site Defaults
        <svg width="14" height="14"><use href="#i-chevd"/></svg>
      </button>
      <div class="settings-acc-body">
        <div class="settings-acc-inner">

          <div class="nsd-cols">

            <!-- Left: Static -->
            <div class="nsd-col">
              <div class="nsd-col-hdr">Static Site</div>

              <div class="fgrp">
                <label class="flabel">Default Parent Directory</label>
                <div class="finput-row">
                  <input type="text" id="gs-static-dir" class="finput"
                    placeholder="Leave blank to use parked directory">
                  <button type="button" class="btn" id="gs-static-dir-browse" style="flex-shrink:0">📁</button>
                </div>
              </div>

              <div class="fgrp">
                <label class="flabel">Default Folder Structure</label>
                <div class="ft-tree" id="gs-tree"></div>
              </div>
            </div>

            <!-- Divider -->
            <div class="nsd-divider"></div>

            <!-- Right: WordPress -->
            <div class="nsd-col">
              <div class="nsd-col-hdr">WordPress</div>

              <div class="fgrp">
                <label class="flabel">Default Parent Directory</label>
                <div class="finput-row">
                  <input type="text" id="gs-wp-dir" class="finput"
                    placeholder="Leave blank to use parked directory">
                  <button type="button" class="btn" id="gs-wp-dir-browse" style="flex-shrink:0">📁</button>
                </div>
              </div>

              <div class="fgrp">
                <label class="flabel">Default Plugins</label>
                <textarea id="gs-plugins" class="finput" rows="4"
                  style="font-family:'JetBrains Mono',monospace;font-size:11px;resize:vertical"
                  placeholder="One slug or /path/to/plugin.zip per line"></textarea>
                <button type="button" class="btn" id="gs-plugins-browse" style="font-size:11px;padding:3px 8px;margin-top:4px">📁 Add path…</button>
              </div>

              <div class="fgrp">
                <label class="flabel">Default Themes</label>
                <textarea id="gs-themes" class="finput" rows="3"
                  style="font-family:'JetBrains Mono',monospace;font-size:11px;resize:vertical"
                  placeholder="One slug or /path/to/theme.zip per line"></textarea>
                <button type="button" class="btn" id="gs-themes-browse" style="font-size:11px;padding:3px 8px;margin-top:4px">📁 Add path…</button>
              </div>
            </div>

          </div><!-- /nsd-cols -->

          <div class="settings-save-row">
            <button class="btn btn-c" id="gs-save">Save Defaults</button>
          </div>

        </div>
      </div>
    </section>

    <!-- ── WP Admin Credentials ──────────────────────────────────────────── -->
    <section class="settings-section">
      <button class="settings-acc-hdr">
        WordPress Admin Credentials
        <svg width="14" height="14"><use href="#i-chevd"/></svg>
      </button>
      <div class="settings-acc-body">
        <div class="settings-acc-inner">

          <p class="settings-note">Used as defaults when creating new WordPress sites and for one-click admin login.</p>

          <div class="settings-row">
            <div class="settings-row-label">Username</div>
            <input type="text" id="gs-admin-user" class="finput settings-input" placeholder="admin" autocomplete="off">
          </div>

          <div class="settings-row">
            <div class="settings-row-label">Password</div>
            <input type="text" id="gs-admin-pass" class="finput settings-input" placeholder="admin" autocomplete="new-password">
          </div>

          <div class="settings-row">
            <div class="settings-row-label">Email</div>
            <div>
              <input type="email" id="gs-admin-email" class="finput settings-input" placeholder="admin@{sitename}.test" autocomplete="off">
              <div class="settings-hint"><code>{sitename}</code> is replaced with the actual site name on creation.</div>
            </div>
          </div>

          <div class="settings-save-row">
            <button class="btn btn-c" id="gs-creds-save">Save Credentials</button>
          </div>

        </div>
      </div>
    </section>

    <!-- ── Database Configuration ────────────────────────────────────────── -->
    <section class="settings-section">
      <button class="settings-acc-hdr">
        Database Configuration
        <svg width="14" height="14"><use href="#i-chevd"/></svg>
      </button>
      <div class="settings-acc-body">
        <div class="settings-acc-inner">

          <p class="settings-note">Default database connection used when creating new WordPress sites.</p>

          <div class="settings-row">
            <div class="settings-row-label">Host</div>
            <input type="text" id="gs-db-host" class="finput settings-input" placeholder="127.0.0.1" autocomplete="off">
          </div>

          <div class="settings-row">
            <div class="settings-row-label">Username</div>
            <input type="text" id="gs-db-user" class="finput settings-input" placeholder="root" autocomplete="off">
          </div>

          <div class="settings-row">
            <div class="settings-row-label">Password</div>
            <input type="password" id="gs-db-pass" class="finput settings-input" placeholder="(empty)" autocomplete="new-password">
          </div>

          <div class="settings-save-row">
            <button class="btn btn-c" id="gs-db-save">Save DB Config</button>
          </div>

        </div>
      </div>
    </section>

  </div>
</div>
