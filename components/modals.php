<!-- ── IDE Modal ──────────────────────────────────────────────────────── -->
<div class="modal" id="modal-ide">
  <div class="mbox">
    <div class="mhdr">
      <span class="mtitle">Open in IDE</span>
      <button class="mclose js-mclose"><svg><use href="#i-x"/></svg></button>
    </div>
    <div class="mbody">
      <p style="font-size:11px;color:var(--txtm);margin-bottom:14px;
                font-family:'JetBrains Mono',monospace;word-break:break-all"
         id="ide-path"></p>
      <div class="ide-opts">
        <a id="ide-phpstorm" class="ide-opt" href="#">
          <span class="ide-opt-ic"><svg width="24px" height="24px" viewBox="0 0 256 256" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <defs> <linearGradient x1="1.35359116%" y1="144.124579%" x2="75.5801105%" y2="24.6296296%" id="linearGradient-1"> <stop stop-color="#765AF8" offset="2%"> </stop> <stop stop-color="#B345F1" offset="38%"> </stop> <stop stop-color="#FA3293" offset="76%"> </stop> <stop stop-color="#FF318C" offset="94%"> </stop> </linearGradient> <linearGradient x1="60.0228311%" y1="98.5204082%" x2="25.4109589%" y2="6.72193878%" id="linearGradient-2"> <stop stop-color="#765AF8" offset="18%"> </stop> <stop stop-color="#8655F6" offset="24%"> </stop> <stop stop-color="#9F4CF3" offset="34%"> </stop> <stop stop-color="#AE47F2" offset="44%"> </stop> <stop stop-color="#B345F1" offset="52%"> </stop> </linearGradient> <linearGradient x1="87.7722772%" y1="80.7973422%" x2="17.9405941%" y2="35.4983389%" id="linearGradient-3"> <stop stop-color="#765AF8" offset="2%"> </stop> <stop stop-color="#B345F1" offset="38%"> </stop> </linearGradient> </defs> <g> <path d="M0,49.2 L43.6,0 L132.8,19.2 L144.8,55.6 L136,118.8 L100.537692,100.651642 L102.4,151.2 L102.4,195.2 L24.4,196 L0,49.2 Z" fill="url(#linearGradient-1)"> </path> <polygon fill="url(#linearGradient-2)" points="80.8 150 85.6 89.6 158 15.2 222.8 27.2 256 110 221.2 144.4 164.8 135.2 129.2 172"> </polygon> <polygon fill="url(#linearGradient-3)" points="158 15.2 54 107.6 74 226 160.4 256 256 198.8"> </polygon> <rect fill="#000000" x="48" y="48" width="160" height="160"> </rect> <path d="M63.2,178 L123.2,178 L123.2,188 L63.2,188 L63.2,178 Z M62.4,68.8 L89.6,68.8 C105.6,68.8 115.2,78 115.2,91.6 C115.2,106.8 103.2,114.8 88.4,114.8 L77.2,114.8 L77.2,134.8 L62.4,134.8 L62.4,68.8 L62.4,68.8 Z M88.8,102.8 C95.78,102.8 100.4,98 100.4,92.4 C100.4,86 96,82.4 88.4,82.4 L77.2,82.4 L77.2,102.8 L88.8,102.8 Z M118.8,125.6 L127.6,115.2 C133.6,120 140,123.2 147.6,123.2 C153.6,123.2 157.2,120.8 157.2,116.8 C157.2,113.2 154.8,111.2 143.6,108.4 C130,104.8 121.6,101.2 121.6,88 L121.6,87.6 C121.6,75.6 131.2,67.6 144.8,67.6 C153.771202,67.5615535 162.498743,70.5176559 169.6,76 L162,87.2 C156,83.2 150.4,80.8 144.8,80.8 C139.2,80.8 136.4,83.2 136.4,86.8 C136.4,91.2 139.2,92.8 150.8,95.6 C164.4,99.2 172,104 172,115.6 C172,128.8 162,136.4 147.6,136.4 C136.984542,136.54349 126.703599,132.688136 118.8,125.6 Z" fill="#FFFFFF"> </path> </g> </g></svg></span>
          <div><div class="ide-opt-name">PhpStorm</div><div class="ide-opt-sub">JetBrains</div></div>
        </a>
        <a id="ide-vscode" class="ide-opt" href="#">
          <span class="ide-opt-ic"><svg width="24px" height="24px" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M21.0016 3.11679C21.0016 2.23783 20.0175 2.23782 19.5801 2.34769C20.1924 1.86426 20.9105 1.98147 21.1656 2.12796L27.079 5.02747C27.6424 5.30375 27.9998 5.8786 27.9998 6.50857V25.5831C27.9998 26.2215 27.6329 26.8025 27.058 27.0743L21.4937 29.7054C21.1109 29.8701 20.2799 30.2767 19.5801 29.7053C20.4549 29.8702 20.9287 29.2476 21.0016 28.8264V3.11679Z" fill="url(#paint0_linear_87_8101)"></path> <path d="M19.6512 2.3319C20.1154 2.24017 21.0018 2.28271 21.0018 3.11685V9.68254L3.07359 23.2453C2.76022 23.4824 2.3192 23.443 2.05229 23.1542L0.204532 21.1548C-0.0849358 20.8416 -0.0646824 20.3513 0.249624 20.0633L19.5802 2.34775L19.6512 2.3319Z" fill="url(#paint1_linear_87_8101)"></path> <path d="M21.0018 22.3708L3.07359 8.80801C2.76022 8.57094 2.3192 8.61028 2.05229 8.8991L0.204532 10.8985C-0.0849358 11.2117 -0.0646824 11.702 0.249624 11.9901L19.5802 29.7056C20.455 29.8704 20.9289 29.2478 21.0018 28.8266V22.3708Z" fill="url(#paint2_linear_87_8101)"></path> <defs> <linearGradient id="paint0_linear_87_8101" x1="23.79" y1="2" x2="23.79" y2="30" gradientUnits="userSpaceOnUse"> <stop stop-color="#32B5F1"></stop> <stop offset="1" stop-color="#2B9FED"></stop> </linearGradient> <linearGradient id="paint1_linear_87_8101" x1="21.0018" y1="5.53398" x2="1.0217" y2="22.3051" gradientUnits="userSpaceOnUse"> <stop stop-color="#0F6FB3"></stop> <stop offset="0.270551" stop-color="#1279B7"></stop> <stop offset="0.421376" stop-color="#1176B5"></stop> <stop offset="0.618197" stop-color="#0E69AC"></stop> <stop offset="0.855344" stop-color="#0F70AF"></stop> <stop offset="1" stop-color="#0F6DAD"></stop> </linearGradient> <linearGradient id="paint2_linear_87_8101" x1="1.15522" y1="9.98389" x2="21.0791" y2="26.4808" gradientUnits="userSpaceOnUse"> <stop stop-color="#1791D2"></stop> <stop offset="1" stop-color="#1173C5"></stop> </linearGradient> </defs> </g></svg></span>
          <div><div class="ide-opt-name">VS Code</div><div class="ide-opt-sub">Microsoft</div></div>
        </a>
        <a id="ide-cursor" class="ide-opt" href="#">
          <span class="ide-opt-ic">✦</span>
          <div><div class="ide-opt-name">Cursor</div><div class="ide-opt-sub">AI-powered editor</div></div>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ── Debug Log Modal ────────────────────────────────────────────────── -->
<div class="modal" id="modal-log">
  <div class="mbox wide">
    <div class="mhdr">
      <span class="mtitle" id="log-title">Debug Log</span>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="btn btn-r" id="log-clear" style="font-size:11px;padding:3px 8px">Clear</button>
        <button class="mclose js-mclose"><svg><use href="#i-x"/></svg></button>
      </div>
    </div>
    <div class="mbody">
      <div class="log-meta" id="log-meta"></div>
      <div id="log-area"></div>
    </div>
  </div>
</div>

<!-- ── Clean DB Modal ─────────────────────────────────────────────────── -->
<div class="modal" id="modal-clean">
  <div class="mbox">
    <div class="mhdr">
      <span class="mtitle" id="clean-title">Clean Database</span>
      <button class="mclose js-mclose"><svg><use href="#i-x"/></svg></button>
    </div>
    <div class="mbody" id="clean-body">
      <div style="display:flex;align-items:center;gap:10px;color:var(--txt);font-size:13px">
        <span class="spin"></span> Running WP-CLI…
      </div>
    </div>
  </div>
</div>

<!-- ── Task Form Modal ────────────────────────────────────────────────── -->
<div class="modal" id="modal-task">
  <div class="mbox">
    <div class="mhdr">
      <span class="mtitle" id="task-form-title">New Task</span>
      <button class="mclose js-mclose"><svg><use href="#i-x"/></svg></button>
    </div>
    <div class="mbody">
      <input type="hidden" id="tf-id">
      <input type="hidden" id="tf-status-init">
      <div class="fgrp">
        <label class="flabel" for="tf-title">Title</label>
        <input type="text" id="tf-title" class="finput" placeholder="Task title…" autocomplete="off">
      </div>
      <div class="fgrp">
        <label class="flabel" for="tf-desc">Description</label>
        <textarea id="tf-desc" class="finput" rows="3" placeholder="Optional details, notes, or context…" style="resize:vertical;min-height:72px;line-height:1.5"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div class="fgrp" style="margin-bottom:0">
          <label class="flabel" for="tf-status">Status</label>
          <select id="tf-status" class="fselect">
            <option value="todo">Todo</option>
            <option value="inprogress">In Progress</option>
            <option value="done">Done</option>
          </select>
        </div>
        <div class="fgrp" style="margin-bottom:0">
          <label class="flabel" for="tf-priority">Priority</label>
          <select id="tf-priority" class="fselect">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>
        <div class="fgrp" style="margin-bottom:0">
          <label class="flabel" for="tf-due">Due Date</label>
          <input type="date" id="tf-due" class="finput" style="color-scheme:dark">
        </div>
      </div>
    </div>
    <div class="mfoot">
      <button class="btn js-mclose">Cancel</button>
      <button class="btn btn-c" id="tf-save">Save Task</button>
    </div>
  </div>
</div>

<!-- ── Create Site Modal ──────────────────────────────────────────────── -->
<div class="modal" id="modal-create-site">
  <div class="mbox" style="max-width:560px">
    <div class="mhdr">
      <span class="mtitle">Add Site</span>
      <button class="mclose js-mclose"><svg><use href="#i-x"/></svg></button>
    </div>
    <div class="mbody">
      <!-- Step 1: type -->
      <div id="cs-step-type">
        <div class="fgrp">
          <label class="flabel">Site Type</label>
          <div class="cs-type-opts">
            <label class="cs-type-opt">
              <input type="radio" name="cs-type" value="static" id="cs-type-static">
              <span class="cs-type-card">
                <span class="cs-type-ic"><svg fill="currentColor" height="28px" width="28px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512.002 512.002" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M373.333,277.335c5.888,0,10.667-4.779,10.667-10.667v-256c0-5.888-4.779-10.667-10.667-10.667H138.667 c-1.429,0-2.837,0.299-4.139,0.832c-0.405,0.171-0.704,0.491-1.067,0.725c-0.811,0.469-1.664,0.896-2.347,1.557l-128,128 c-0.491,0.491-0.768,1.152-1.152,1.728c-0.384,0.576-0.875,1.067-1.131,1.707C0.299,135.852,0,137.239,0,138.668v362.667 c0,5.888,4.779,10.667,10.667,10.667h362.667c5.888,0,10.667-4.779,10.667-10.667v-64c0-5.888-4.779-10.667-10.667-10.667 c-5.888,0-10.667,4.779-10.667,10.667v53.333H21.333V149.334h117.333c5.888,0,10.667-4.779,10.667-10.667V21.334h213.333v245.333 C362.667,272.556,367.445,277.335,373.333,277.335z M128,128.001H36.416L128,36.417V128.001z"></path> </g> </g> <g> <g> <path d="M205.248,299.009c-5.824-1.429-11.52,2.027-12.928,7.765l-13.653,54.613l-19.52-45.568 c-3.371-7.851-16.256-7.851-19.627,0L120,361.388l-13.653-54.613c-1.408-5.739-7.317-9.216-12.928-7.765 c-5.717,1.429-9.173,7.211-7.765,12.928l21.333,85.333c1.109,4.437,4.928,7.68,9.493,8.043c4.907,0.405,8.853-2.24,10.667-6.443 l22.187-51.776l22.187,51.776c1.707,3.947,5.568,6.464,9.813,6.464c0.277,0,0.555,0,0.853-0.021 c4.565-0.363,8.363-3.605,9.493-8.043l21.333-85.333C214.443,306.22,210.987,300.439,205.248,299.009z"></path> </g> </g> <g> <g> <path d="M354.581,299.009c-5.824-1.429-11.52,2.027-12.928,7.765L328,361.388l-19.52-45.568c-3.371-7.851-16.256-7.851-19.627,0 l-19.52,45.568l-13.653-54.613c-1.429-5.739-7.317-9.216-12.928-7.765c-5.717,1.429-9.173,7.211-7.765,12.928l21.333,85.333 c1.109,4.437,4.928,7.68,9.493,8.043c4.949,0.405,8.853-2.24,10.667-6.443l22.187-51.776l22.187,51.776 c1.707,3.947,5.568,6.464,9.813,6.464c0.277,0,0.555,0,0.853-0.021c4.565-0.363,8.363-3.605,9.493-8.043l21.333-85.333 C363.776,306.22,360.32,300.439,354.581,299.009z"></path> </g> </g> <g> <g> <path d="M503.915,299.009c-5.803-1.429-11.52,2.027-12.928,7.765l-13.653,54.613l-19.52-45.568 c-3.371-7.851-16.256-7.851-19.627,0l-19.52,45.568l-13.653-54.613c-1.408-5.739-7.317-9.216-12.928-7.765 c-5.717,1.429-9.173,7.211-7.765,12.928l21.333,85.333c1.109,4.437,4.928,7.68,9.493,8.043c4.971,0.405,8.853-2.24,10.667-6.443 L448,347.095l22.187,51.776c1.707,3.947,5.568,6.464,9.813,6.464c0.277,0,0.555,0,0.853-0.021 c4.565-0.363,8.363-3.605,9.493-8.043l21.333-85.333C513.109,306.22,509.653,300.439,503.915,299.009z"></path> </g> </g> </g></svg></span>
                <span class="cs-type-name">Static</span>
                <span class="cs-type-desc">HTML/PHP, no database</span>
              </span>
            </label>
            <label class="cs-type-opt">
              <input type="radio" name="cs-type" value="wordpress" id="cs-type-wp" checked>
              <span class="cs-type-card">
                <span class="cs-type-ic"><svg width="28px" height="28px" viewBox="0 0 48 48" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>Wordpress-color</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Icons" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Color-" transform="translate(-400.000000, -760.000000)" fill="#00759D"> <path d="M400,783.99925 C400,793.499047 405.520173,801.708803 413.525923,805.598425 L402.077565,774.232445 C400.747023,777.216038 400,780.519141 400,783.99925 Z M440.201556,782.788712 C440.201556,779.821619 439.135023,777.768055 438.222994,776.170505 C437.006456,774.191943 435.864921,772.517891 435.864921,770.540829 C435.864921,768.33426 437.537473,766.280696 439.895547,766.280696 C440.00205,766.280696 440.102553,766.294197 440.206056,766.300197 C435.936923,762.388075 430.247245,760 423.99955,760 C415.614288,760 408.238557,764.302134 403.946923,770.816838 C404.510941,770.834839 405.041958,770.845339 405.491972,770.845339 C408.00155,770.845339 411.888172,770.540829 411.888172,770.540829 C413.181212,770.464327 413.334217,772.366386 412.041176,772.517891 C412.041176,772.517891 410.740636,772.670896 409.29459,772.747398 L418.033864,798.743211 L423.287028,782.991218 L419.548911,772.747398 C418.25587,772.670896 417.030332,772.517891 417.030332,772.517891 C415.737292,772.441389 415.888797,770.464327 417.183337,770.540829 C417.183337,770.540829 421.146461,770.845339 423.504535,770.845339 C426.014113,770.845339 429.900734,770.540829 429.900734,770.540829 C431.195275,770.464327 431.34678,772.366386 430.053739,772.517891 C430.053739,772.517891 428.751698,772.670896 427.307153,772.747398 L435.980424,798.545205 L438.375999,790.546955 C439.411032,787.225851 440.201556,784.842276 440.201556,782.788712 Z M445.059908,772.48534 C445.163411,773.250364 445.221913,774.06939 445.221913,774.952917 C445.221913,777.387493 444.765899,780.125079 443.396356,783.549686 L436.065627,804.743848 C443.20135,800.584218 448,792.852977 448,783.9997 C448,779.82657 446.933467,775.903947 445.059908,772.48534 Z M424.421063,786.098716 L417.219338,807.022869 C419.370405,807.655889 421.644476,808.0009 423.99955,808.0009 C426.794137,808.0009 429.474721,807.517885 431.969299,806.640358 C431.906297,806.536854 431.846295,806.428851 431.798294,806.310347 L424.421063,786.098716 Z" id="Wordpress"> </path> </g> </g> </g></svg></span>
                <span class="cs-type-name">WordPress</span>
                <span class="cs-type-desc">Latest WP, admin/admin</span>
              </span>
            </label>
          </div>
        </div>

        <!-- Common fields -->
        <div class="fgrp">
          <label class="flabel" for="cs-name">Site Name</label>
          <div class="finput-row">
            <input type="text" id="cs-name" class="finput" placeholder="my-project"
              autocomplete="off" style="font-family:'JetBrains Mono',monospace;flex:1">
            <button type="button" class="fld-clr" data-clr="cs-name">Clear</button>
          </div>
          <div style="font-size:11px;color:var(--txtm);margin-top:4px">
            Lowercase letters, numbers, hyphens · available at <span id="cs-preview" style="color:var(--cyan)">sitename.test</span>
          </div>
        </div>

        <div class="fgrp">
          <label class="flabel" for="cs-dir">Parent Directory</label>
          <div class="finput-row">
            <input type="text" id="cs-dir" class="finput" style="font-family:'JetBrains Mono',monospace;flex:1" placeholder="/path/to/sites">
            <button type="button" class="fld-clr" data-clr="cs-dir">Clear</button>
            <button type="button" class="btn btn-v" id="cs-dir-browse" title="Browse">📁</button>
          </div>
        </div>

        <!-- Static-only fields -->
        <div id="cs-static-fields" style="display:none">
          <div class="fgrp">
            <label class="flabel">Folder Structure</label>
            <div class="finput-row" style="align-items:flex-start">
              <div class="ft-tree" id="cs-tree" style="flex:1;min-width:0"></div>
              <button type="button" class="fld-clr" data-clr="cs-tree">Clear</button>
            </div>
          </div>
        </div>

        <!-- WordPress-only fields -->
        <div id="cs-wp-fields">
          <div class="fgrp">
            <label class="flabel" for="cs-plugins">Plugin Paths (one per line)</label>
            <div class="finput-row" style="align-items:flex-start">
              <textarea id="cs-plugins" class="finput" rows="3"
                style="font-family:'JetBrains Mono',monospace;font-size:11px;resize:vertical;flex:1"
                placeholder="/path/to/plugin.zip or plugin-slug"></textarea>
              <button type="button" class="fld-clr" data-clr="cs-plugins">Clear</button>
            </div>
            <button type="button" class="btn" id="cs-plugins-browse" style="font-size:11px;padding:3px 8px;margin-top:4px">📁 Add path…</button>
          </div>
          <div class="fgrp">
            <label class="flabel" for="cs-themes">Theme Paths (one per line)</label>
            <div class="finput-row" style="align-items:flex-start">
              <textarea id="cs-themes" class="finput" rows="2"
                style="font-family:'JetBrains Mono',monospace;font-size:11px;resize:vertical;flex:1"
                placeholder="/path/to/theme.zip or theme-slug"></textarea>
              <button type="button" class="fld-clr" data-clr="cs-themes">Clear</button>
            </div>
            <button type="button" class="btn" id="cs-themes-browse" style="font-size:11px;padding:3px 8px;margin-top:4px">📁 Add path…</button>
          </div>
        </div>
      </div>

      <!-- Step 2: progress log -->
      <div id="cs-log" style="display:none">
        <div class="cs-log-area" id="cs-log-area"></div>
      </div>
    </div>
    <div class="mfoot">
      <button class="btn js-mclose">Cancel</button>
      <button class="btn btn-c" id="cs-create"><svg><use href="#i-plus"/></svg> Create Site</button>
    </div>
  </div>
</div>
