# Valet Site Manager — AI Instruction Set

> Hand this document to any AI assistant. It describes the full project, every feature that has been built, all design decisions, and critical implementation details. The AI should be able to continue work or recreate features without prior conversation context.

---

## 1. Project Overview

**Valet Site Manager** is a cross-platform local web dashboard for developers running [Laravel Valet](https://laravel.com/docs/valet) (macOS) or [Laravel Herd](https://herd.laravel.com) (macOS or Windows). It is served by Valet/Herd itself at a `.test` domain (e.g. `http://site-manager.test`).

- **Language**: PHP 8.1+ (no framework), vanilla JS (no build step), plain CSS
- **Platforms**: macOS (Valet or Herd) + Windows (Herd)
- **Entry point**: `index.php`
- **API**: `api.php` — all XHR calls go here via `?action=<name>`
- **Storage**: `Storage/db.json` — JSON flat-file database via `includes/Database.inc.php`
- **Auth**: password-protected via `includes/Auth.inc.php` + `login.php`
- **Fonts**: Space Grotesk (UI), JetBrains Mono (code/paths)
- **Icons**: inline SVG sprite in `components/svg-sprite.php`; referenced as `<use href="#i-name"/>`

---

## 2. File Structure

```
site-manager/
├── index.php                          # Main page, loads all components
├── api.php                            # All API endpoints — uses $platform for every OS call
├── app.php                            # Bootstrap: constants, platform factory, SiteScanner init
├── auto-login.php                     # One-click WP admin login
├── login.php                          # Auth gate
├── assets/
│   └── favicon.svg                    # SVG favicon (cyan→violet gradient, "S" letter)
├── components/
│   ├── _card.php                      # Single site card (included in loops)
│   ├── header.php                     # Top navigation bar
│   ├── svg-sprite.php                 # All SVG icon symbols
│   ├── panel-sites.php                # Sites tab panel
│   ├── panel-archive.php              # Archive tab panel
│   ├── panel-settings.php             # Settings tab panel
│   ├── panel-tasks.php                # Tasks tab panel
│   ├── activity-log.php               # Slide-in activity log panel
│   ├── modals.php                     # Modal dialogs (create site, confirm, etc.)
│   ├── terminal.php                   # Inline terminal panel
│   └── tooltips.php                   # Tooltip container
├── includes/
│   ├── Platform.inc.php               # Platform interface — defines every OS-specific operation
│   ├── platforms/
│   │   ├── MacosPlatform.inc.php      # macOS implementation (Valet + Herd, osascript, sudo wrapper)
│   │   └── WindowsPlatform.inc.php    # Windows implementation (Herd, PowerShell, explorer.exe)
│   ├── Utils.inc.php                  # SiteScanner (platform-aware) + WpExecutor (macOS exec helper)
│   ├── Database.inc.php               # JSON flat-file DB
│   └── Auth.inc.php                   # Session auth
├── dist/styles/
│   ├── css/
│   │   ├── styles.css                 # @import aggregator
│   │   └── components/
│   │       ├── variables.css          # CSS custom properties (dark + light theme)
│   │       ├── reset.css
│   │       ├── layout.css
│   │       ├── header.css
│   │       ├── site-card.css
│   │       ├── buttons.css
│   │       ├── toolbar.css
│   │       ├── settings.css
│   │       ├── activity-log.css
│   │       ├── modals.css
│   │       ├── terminal.css
│   │       ├── tasks.css
│   │       ├── tooltips.css
│   │       ├── misc.css
│   │       └── forms.css
│   └── js/
│       ├── scripts.js                 # @import aggregator
│       └── components/
│           ├── api.js                 # fetch wrapper + CSRF helper
│           ├── sites.js               # Sites panel, SSL, debug, hover menus, tab routing
│           ├── settings.js            # Settings panel, btn visibility, log badge toggle
│           ├── tasks.js               # Tasks CRUD
│           ├── activity-log.js        # actLog object — action log UI
│           ├── modals.js              # Modal open/close helpers
│           ├── terminal.js            # Inline terminal
│           └── theme.js               # Dark/light theme toggle
└── Storage/
    └── db.json                        # Tasks, archived sites, settings
```

---

## 3. Platform Abstraction Layer

The entire OS-specific surface area is hidden behind a `Platform` interface. Business logic in `api.php` and `Utils.inc.php` never checks `IS_WINDOWS` — it calls `$platform->method()` and the correct implementation runs.

### 3.1 Bootstrap (`app.php`)

```php
define('IS_WINDOWS', PHP_OS_FAMILY === 'Windows');
define('IS_MACOS',   PHP_OS_FAMILY === 'Darwin');

// Unified HOME — getenv('HOME') is empty on Windows
define('SYS_HOME', IS_WINDOWS
    ? (getenv('USERPROFILE') ?: 'C:\\Users\\Default')
    : (getenv('HOME') ?: '/tmp')
);

// APP_URL is dynamic — works with HTTP and HTTPS (Herd SSL)
define('APP_URL',
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'site-manager.test')
);

/** @var Platform $platform */
$platform = IS_WINDOWS ? new WindowsPlatform() : new MacosPlatform();
SiteScanner::init($platform);
```

### 3.2 Platform Interface (`includes/Platform.inc.php`)

```php
interface Platform {
    // Paths
    public function configDir(): string;        // ~/.config/valet or %USERPROFILE%\.config\herd
    public function sitesDirs(): array;         // all candidate Sites symlink directories
    public function certsDir(): string;         // where {name}.test.crt lives
    public function valetConfigFile(): string;  // path to config.json (for parked paths list)
    public function composerBinDir(): string;
    public function defaultSitesRootDir(): string;

    // CLI
    public function valetBin(): string;         // 'valet' or 'herd'

    // App detection
    public function detectBrowsers(): array;    // ['chrome', 'firefox', ...]
    public function detectIDEs(): array;        // ['vscode', 'phpstorm', ...]

    // OS actions
    public function openExplorer(string $path): void;
    public function openInBrowser(string $url, string $app = 'default'): void;
    public function openTerminal(string $path): void;
    public function openWpCli(string $path): void;
    public function pickFolder(): ?string;      // native folder picker, null = cancelled

    // Execution
    public function exec(string $cmd, string $cwd, string $sudoPass = ''): array; // [output, exitCode]
    public function needsSudoPassword(): bool;
}
```

### 3.3 MacosPlatform (`includes/platforms/MacosPlatform.inc.php`)

Key behaviours:
- `configDir()` — prefers `~/.config/herd` if it exists, falls back to `~/.config/valet`
- `valetBin()` — checks `/usr/local/bin/herd` and `/opt/homebrew/bin/herd`; returns `'herd'` if found, else `'valet'`
- `detectBrowsers()` / `detectIDEs()` — `glob("*.app")` scan of `/Applications` and `~/Applications`
- `openTerminal()` / `openWpCli()` — `osascript` → Terminal.app
- `pickFolder()` — `osascript -e "POSIX path of (choose folder...)"`
- `exec()` — delegates to `WpExecutor::exec()` (sudo wrapper)
- `needsSudoPassword()` — runs `sudo -n true`; returns true if exit code ≠ 0

### 3.4 WindowsPlatform (`includes/platforms/WindowsPlatform.inc.php`)

Key behaviours:
- `configDir()` — `%USERPROFILE%\.config\herd`
- `sitesDirs()` — `[configDir() . '\Sites']`
- `certsDir()` — `configDir() . '\Certificates'`
- `valetBin()` — always `'herd'`
- `detectBrowsers()` — checks known `%PROGRAMFILES%`, `%PROGRAMFILES(X86)%`, `%LOCALAPPDATA%` paths per browser
- `detectIDEs()` — checks known exe paths; PhpStorm detected via `%LOCALAPPDATA%\JetBrains\Toolbox\apps\PhpStorm` directory
- `openExplorer()` — `explorer.exe <path>`
- `openInBrowser()` — `start "" <exe> <url>` or `start "" <url>` for default
- `openTerminal()` — prefers `wt.exe` (Windows Terminal), then `pwsh.exe`, then `powershell.exe`, then `cmd.exe`
- `openWpCli()` — same terminal cascade, with `wp shell` appended
- `pickFolder()` — PowerShell `System.Windows.Forms.FolderBrowserDialog`
- `exec()` — direct `proc_open` with Windows-appropriate PATH; strips `\r\n` from output
- `needsSudoPassword()` — always `false` (Herd handles elevation internally)

---

## 4. CSS Variables (full token set)

Defined in `dist/styles/css/components/variables.css`. Dark theme (`:root`) and light theme (`[data-theme="light"]`):

| Token | Dark | Light | Meaning |
|---|---|---|---|
| `--bg` | `#05070f` | `#f0f4ff` | Page background |
| `--sur` | `#090d1a` | `#ffffff` | Card surface |
| `--sur2` | `#0d1220` | `#f5f8ff` | Input background |
| `--sur3` | `#111827` | `#eef2ff` | Elevated surface |
| `--bd` | `rgba(56,100,200,.14)` | `rgba(99,102,241,.12)` | Default border |
| `--bd2` | `rgba(56,100,200,.28)` | `rgba(99,102,241,.28)` | Strong border |
| `--bdg` | `rgba(0,210,255,.35)` | `rgba(79,70,229,.4)` | Glow/active border |
| `--txt` | `#5a6a8a` | `#6b7280` | Muted text |
| `--txts` | `#c8d6f0` | `#1f2937` | Secondary text |
| `--txtb` | `#e8f0ff` | `#111827` | Bold/primary text |
| `--txtm` | `#2e3d55` | `#9ca3af` | Very muted text |
| `--cyan` | `#00d2ff` | `#4f46e5` | Primary accent |
| `--cyand` | `rgba(0,210,255,.1)` | `rgba(79,70,229,.08)` | Accent background tint |
| `--vio` | `#a78bfa` | `#7c3aed` | Violet accent |
| `--grn` | `#34d399` | `#059669` | Green / success |
| `--grnd` | `rgba(52,211,153,.1)` | `rgba(5,150,105,.1)` | Green tint |
| `--amb` | `#fbbf24` | `#d97706` | Amber / warning |
| `--ambd` | `rgba(251,191,36,.1)` | `rgba(217,119,6,.1)` | Amber tint |
| `--red` | `#f87171` | `#dc2626` | Red / danger |
| `--redd` | `rgba(248,113,113,.1)` | `rgba(220,38,38,.1)` | Red tint |
| `--r` | `8px` | `8px` | Default border-radius |
| `--hh` | `56px` | `56px` | Header height |

---

## 5. SVG Icon Sprite

All icons live in `components/svg-sprite.php` as `<symbol>` elements. Reference with `<svg><use href="#i-name"/></svg>`.

| ID | Description |
|---|---|
| `#i-globe` | Browser / sites |
| `#i-shield` | WP Admin |
| `#i-code` | IDE / code |
| `#i-terminal` | Bash terminal |
| `#i-cli` | WP-CLI |
| `#i-doc` | Log file |
| `#i-bug` | Debug |
| `#i-db` | Database |
| `#i-sun` / `#i-moon` | Theme toggle |
| `#i-gear` | Settings |
| `#i-check` | Checkmark |
| `#i-chevd` / `#i-chevr` | Chevron down/right |
| `#i-plus` | Add |
| `#i-edit` | Edit |
| `#i-trash` | Delete |
| `#i-x` | Close |
| `#i-search` | Search |
| `#i-filter` | Filter |
| `#i-grid` / `#i-list` | View mode |
| `#i-tasks` | Tasks clipboard |
| `#i-sort` | Sort |
| `#i-folder` | Finder / Explorer |
| `#i-archive` | Archive (box, horizontal bar inside) |
| `#i-unarchive` | Unarchive (box, upward arrow inside) |
| `#i-lock` | Closed padlock (SSL on) |
| `#i-lock-open` | Open padlock (SSL off) |
| `#i-dots` | Vertical 3-dot menu |

---

## 6. Features — Complete Specification

### 6.1 Header (`components/header.php`)

**Layout** (left → right):
1. **Logo** — `.logo-mark` (28×28px cyan→violet gradient rounded square, "S" bold), `.logo-text` (title "VALET SITE MANAGER", subtitle "by [imraju007](https://github.com/imraju007/valet-site-manager)")
2. **Nav** — `.hdr-nav` with two nav buttons: Sites (with live count `#nc-sites`) and Tasks (with count `#nc-tasks`)
3. **Right** — `.hdr-right`:
   - Theme toggle `#theme-btn` (`.icb`)
   - 3-dot more menu (`.hdr-more`) containing Settings and Archive with archive count badge

**3-dot menu** opens on click, closes when clicking outside. CSS: `.hdr-more-menu` (hidden), `.open` (visible). Contains:
- Settings item `data-tab="settings"`
- Separator `.hdr-more-sep`
- Archive item `data-tab="archive"` with `#nc-archive` count badge

**Tab routing via URL hash** — `sites.js` reads `location.hash` on load, listens to `hashchange`, writes hash on tab switch. Valid tabs: `sites`, `tasks`, `settings`, `archive`. Falls back to `sites` for unknown hashes.

---

### 6.2 Site Card (`components/_card.php`)

Each card is `.sc` with `data-site`, `data-search`, `data-type`, `data-debug` attributes.

**Card header** `.sc-top` — 3 flex children:

1. **`.sc-top-name`** (`flex:1; min-width:0`) — `.sc-dot` (pulsing green dot) + `.sc-name` (truncated)
2. **`.sc-top-badges`** (`flex-shrink:0`) — type badge + ssl badge + debug badge
3. **`.sc-url`** (`flex-shrink:0; max-width:160px`) — monospace, truncated

**Badges:**
- `.sc-type-badge` — "WP" (blue) or "Static" (green)
- `.sc-ssl-badge` — green lock icon, shown when `$e['ssl']` is true
- `.sc-debug-badge` — amber bug icon, WP-only, hidden by default (`style="display:none"`), shown by JS

**Card actions** `.sc-actions`:

| Button | Class(es) | Notes |
|---|---|---|
| Open | `.btn.btn-c.js-open-trigger` | Hover → `.open-menu` (browser picker) |
| Admin | `.btn.btn-g` (link) | WP-only |
| IDE | `.btn.btn-v` in `.ide-wrap` | Hover → `.ide-menu`; hidden if no IDEs detected |
| Finder | `.btn.js-finder` | `open_finder` API → `$platform->openExplorer()` |
| SSL | `.btn.js-ssl` | Toggles via `$platform->valetBin() secure/unsecure` |
| Terminal | `.btn.js-term[data-mode="bash"]` | WP-only → `$platform->openTerminal()` |
| WP-CLI | `.btn.js-term[data-mode="wpcli"]` | WP-only → `$platform->openWpCli()` |
| Log | `.btn.btn-a.js-debug-log` | WP-only; `.log-sz` badge if `log_size > 0` |
| Debug | `.btn.btn-a.js-debug-toggle` | WP-only; `.debug-dot` corner indicator |
| Clean | `.btn.btn-r.js-clean-db` | WP-only |
| Tasks | `.btn.btn-v.js-site-tasks` | All site types |
| Archive | `.btn.js-archive` | Active sites only |
| Unarchive | `.btn.btn-v.js-unarchive` | Archived only — uses `#i-unarchive` icon |
| Delete | `.btn.btn-r.js-site-delete` | Both active and archived |

**Debug button corner dot** (`.debug-dot`): `position:absolute; top:-3px; right:-3px`. Hidden by default; CSS-driven by `.btn-a-on` class.

---

### 6.3 Dynamic App Detection

`SiteScanner::detectInstalledApps()` delegates entirely to the platform:

```php
public static function detectInstalledApps(): array {
    return [
        'browsers' => self::$platform->detectBrowsers(),
        'ides'     => self::$platform->detectIDEs(),
    ];
}
```

**macOS** (`MacosPlatform`): `glob("*.app")` scan of `/Applications` and `~/Applications`.

**Windows** (`WindowsPlatform`): checks known exe paths in `%PROGRAMFILES%`, `%PROGRAMFILES(X86)%`, `%LOCALAPPDATA%`. PhpStorm detected by presence of `%LOCALAPPDATA%\JetBrains\Toolbox\apps\PhpStorm` directory.

---

### 6.4 Hover Menus (Open / IDE)

Both menus use `position:fixed` children positioned via JS (escapes card overflow clipping). The pattern is identical on both platforms — it is pure JS/CSS with no OS dependency.

```js
function _openHoverMenu(wrap) {
  clearTimeout(_hmTimer);
  $('.open-menu.open, .ide-menu.open').forEach(m => m.classList.remove('open'));
  const menu = wrap.querySelector('.open-menu, .ide-menu');
  const rect = wrap.querySelector('button').getBoundingClientRect();
  const menuW = menu.offsetWidth || 180;
  let left = rect.left;
  if (left + menuW > window.innerWidth - 8) left = window.innerWidth - menuW - 8;
  menu.style.top  = (rect.bottom + 4) + 'px';
  menu.style.left = left + 'px';
  menu.classList.add('open');
}
```

120ms close delay prevents flicker when cursor moves from button into the menu.

---

### 6.5 SSL Toggle

**macOS behaviour**: `valet/herd secure` restarts PHP-FPM and nginx, killing the PHP process mid-request. The HTTP response never arrives — JS treats `r.error === 'Failed to fetch'` as likely-success.

**Windows behaviour**: `herd secure` completes normally without restarting the PHP process. JS `r.ok` path handles it cleanly.

**PHP** (`api.php`):
```php
$verb = $secure ? 'secure' : 'unsecure';
$cmd  = $platform->valetBin() . ' ' . $verb . ' ' . escapeshellarg($name);
[$out, $code] = $platform->exec($cmd, APP_DIR, $sudoPass);
```

**Sudo password flow** (macOS only):
1. JS calls `check_sudo` → `{ needs_password: bool }` (always `false` on Windows)
2. If needed: `log.passwordPrompt()` → returns password string
3. Passed as `sudo_pass` in POST body → `$platform->exec($cmd, $cwd, $sudoPass)`
4. `MacosPlatform::exec()` → `WpExecutor::exec()` → temp sudo wrapper script

---

### 6.6 `buildEntry()` — Per-site Data (`includes/Utils.inc.php`)

SSL detection uses `$platform->certsDir()` — resolves to the correct path per OS:

```php
private static function buildEntry(string $name, string $path, string $type, bool $linked): array {
    $certsDir = self::$platform->certsDir();
    $ssl      = $certsDir !== '' && file_exists($certsDir . DIRECTORY_SEPARATOR . $name . '.test.crt');
    $logFile  = $type === 'wordpress' ? $path . '/wp-content/debug.log' : '';
    $logSize  = ($logFile !== '' && file_exists($logFile)) ? (int)filesize($logFile) : 0;

    return [
        'name'     => $name,
        'url'      => ($ssl ? 'https://' : 'http://') . $name . '.test',
        'ssl'      => $ssl,
        'log_size' => $logSize,
        'admin'    => APP_URL . '/login.php?site=' . rawurlencode($name),
        'path'     => $path,
        'type'     => $type,   // 'wordpress' or 'static'
        'phpstorm' => 'phpstorm://open?file=' . rawurlencode($path),
        'vscode'   => 'vscode://file/' . str_replace('%2F', '/', rawurlencode($path)),
        'cursor'   => 'cursor://file/' . str_replace('%2F', '/', rawurlencode($path)),
        'linked'   => $linked,
    ];
}
```

**SSL cert paths by platform:**
- macOS Valet: `~/.config/valet/Certificates/{name}.test.crt`
- macOS Herd: `~/.config/herd/Certificates/{name}.test.crt`
- Windows Herd: `%USERPROFILE%\.config\herd\Certificates\{name}.test.crt`

---

### 6.7 `valetSites()` — Symlink Discovery

Uses `$platform->sitesDirs()` to find the correct symlink directory per OS:

```php
public static function valetSites(): array {
    $results = [];
    foreach (self::$platform->sitesDirs() as $dir) {
        if (!is_dir($dir)) continue;
        foreach (scandir($dir) as $name) {
            if ($name === '.' || $name === '..') continue;
            $link = $dir . DIRECTORY_SEPARATOR . $name;
            if (!is_link($link)) continue;
            $real = realpath($link);
            if ($real && is_dir($real)) $results[$name] = $real;
        }
        if (!empty($results)) break;
    }
    return $results;
}
```

macOS probes both `~/.config/{valet|herd}/Sites` and the legacy `~/.valet/Sites`. Windows probes `%USERPROFILE%\.config\herd\Sites`.

---

### 6.8 Debug Button State (`sites.js`)

```js
function refreshDebugBtn(site) {
  const btn = $(`.js-debug-toggle[data-site="${CSS.escape(site)}"]`);
  if (!btn) return;
  const st = S.dbg[site];
  const lbl = btn.querySelector('.dl');
  if (st === '?') { lbl.innerHTML = '<span class="spin"></span>'; return; }
  const on = st === true;
  lbl.textContent = on ? 'Debug ON' : 'Debug OFF';
  btn.classList.toggle('btn-a-on', on);
  btn.dataset.enabled = on ? '1' : '0';
  // corner dot is CSS-driven by btn-a-on — no style manipulation needed
  const badge = btn.closest('.sc')?.querySelector('.sc-debug-badge');
  if (badge) badge.style.display = on ? '' : 'none';
}
```

Debug state is lazy-loaded via `IntersectionObserver`.

---

### 6.9 Log Size Badge

PHP renders inside `.js-debug-log`:
```php
<?php if ($e['log_size'] > 0): ?>
  <span class="log-sz"><?= $fmt_bytes($e['log_size']) ?></span>
<?php endif; ?>
```

After clear: JS removes `.log-sz` span from DOM directly. Settings toggle uses `localStorage` key `sh-log-sz` → `body.hide-log-sz` class.

---

### 6.10 Button Visibility (Settings → Appearance)

Body classes toggled from `localStorage`. CSS in `site-card.css`:

```css
body.hide-btn-open     .open-wrap                  { display: none }
body.hide-btn-ide      .ide-wrap                   { display: none }
body.hide-btn-finder   .js-finder                  { display: none }
body.hide-btn-tasks    .js-site-tasks              { display: none }
body.hide-btn-delete   .js-site-delete             { display: none }
body.hide-btn-admin    .sc-actions a.btn-g         { display: none }
body.hide-btn-terminal .js-term[data-mode="bash"]  { display: none }
body.hide-btn-wpcli    .js-term[data-mode="wpcli"] { display: none }
body.hide-btn-log      .js-debug-log               { display: none }
body.hide-btn-debug    .js-debug-toggle            { display: none }
body.hide-btn-clean    .js-clean-db                { display: none }
body.hide-log-sz       .log-sz                     { display: none }
```

---

### 6.11 Activity Log (`actLog`)

`dist/styles/js/components/activity-log.js` — slide-in panel for real-time operation progress.

**Key methods:**
- `actLog.action(title)` → returns `log` handle
- `log.step(label, ok, cmd, output)` → adds step row
- `log.done(success, message)` → finalises entry
- `log.passwordPrompt(message)` → inline password input, returns `Promise<string>`

**Use pattern for any privileged operation:**
```js
const log = actLog.action('Operation title');
const sudoCheck = await api('check_sudo', {});
let sudoPass = '';
if (sudoCheck.needs_password) {          // always false on Windows
  sudoPass = await log.passwordPrompt('Administrator password required');
}
const r = await api('action_name', { ...payload, sudo_pass: sudoPass }, 'POST');
```

---

### 6.12 Archive Page

- Same `_card.php` partial with `$archived = true`
- Archived cards: `.sc-archived` class (reduced opacity), `.sc-accent` uses `var(--bd2)`
- **Unarchive button**: `#i-unarchive` icon (box + upward arrow), class `.btn.btn-v.js-unarchive`
- **Archive button**: `#i-archive` icon, class `.btn.js-archive`

---

### 6.13 WpExecutor — macOS Sudo Wrapper (`includes/Utils.inc.php`)

Used only by `MacosPlatform::exec()`. Not called directly anywhere in `api.php`.

1. Creates temp dir `vsm_<random>` in `/tmp`
2. Writes a `sudo` bash wrapper script that reads `_VSM_SUDO_PASS` env var and pipes it to `sudo -S`
3. Prepends temp dir to `PATH` so any `sudo` call in the command uses the wrapper
4. Cleans up temp files after execution (even on failure)

This is required because macOS `timestamp_type=tty` prevents credential caching across process boundaries — the PHP process cannot inherit a cached sudo session from the browser.

---

## 7. Design Conventions

### Button colour system

| Class | Colour | Use |
|---|---|---|
| `.btn` | neutral | Default action |
| `.btn-c` | cyan | Primary/Open |
| `.btn-g` | green | Safe/positive (Admin, IDE) |
| `.btn-g-on` | green filled | Active state (SSL on) |
| `.btn-v` | violet | Secondary positive (Tasks, Unarchive) |
| `.btn-a` | amber | Warning (Log, Debug) |
| `.btn-a-on` | amber filled | Active warning state (Debug ON) |
| `.btn-r` | red | Destructive (Delete, Clean) |

### Site card structure rules

- **No `overflow:hidden`** — clips `position:fixed` hover menus
- **No `transform`** — creates stacking contexts that trap child z-index

### CSS body-class approach

Feature visibility is toggled by adding/removing classes on `document.body` from `localStorage`. Applied before first paint to avoid flash.

---

## 8. API Endpoint Reference

All endpoints in `api.php`. POST endpoints require `X-CSRF-Token` header (available as JS global `CSRF_TOKEN`). Every OS-specific operation routes through `$platform`.

| Action | Method | Key params | Returns | Platform call |
|---|---|---|---|---|
| `check_sudo` | GET | — | `{ needs_password }` | `needsSudoPassword()` |
| `run_cmd` | POST | `site`, `cmd` | `{ output, exit_code }` | `exec()` |
| `open_browser` | POST | `url`, `app` | `{ ok }` | `openInBrowser()` |
| `debug_log` | GET | `site` | `{ exists, content, bytes, total_lines }` | — |
| `clear_log` | POST | `site` | `{ ok }` | — |
| `debug_status` | GET | `site` | `{ enabled }` | `exec()` |
| `toggle_debug` | POST | `site`, `enable` | `{ enabled, steps }` | `exec()` |
| `open_terminal` | POST | `site` | `{ ok }` | `openTerminal()` |
| `open_wpcli` | POST | `site` | `{ ok }` | `openWpCli()` |
| `clean_db` | POST | `site` | `{ results[] }` | `exec()` |
| `get_tasks` | GET | `site` | `{ tasks[] }` | — |
| `save_task` | POST | `site`, `task` | `{ task }` | — |
| `delete_task` | POST | `site`, `task_id` | `{ ok }` | — |
| `delete_site` | POST | `site`, `sudo_pass` | `{ steps[] }` | `exec()`, `valetBin()` |
| `create_site` | POST | `name`, `type`, `parent_dir`, `sudo_pass`, `plugins`, `themes` | `{ ok, steps[], url }` | `exec()`, `valetBin()` |
| `open_finder` | POST | `site` | `{ ok }` | `openExplorer()` |
| `get_settings` | GET | — | `{ settings }` | — |
| `save_settings` | POST | `section`, `data` | `{ ok }` | — |
| `pick_path` | GET | `type` | `{ path, cancelled }` | `pickFolder()` |
| `toggle_ssl` | POST | `name`, `secure`, `sudo_pass` | `{ ok, output }` | `exec()`, `valetBin()` |
| `toggle_archive` | POST | `name`, `archive` | `{ ok }` | — |

---

## 9. Cross-Platform Behaviour Reference

| Feature | macOS (Valet) | macOS (Herd) | Windows (Herd) |
|---|---|---|---|
| CLI binary | `valet` | `herd` | `herd` |
| Config dir | `~/.config/valet` | `~/.config/herd` | `%USERPROFILE%\.config\herd` |
| SSL certs | `~/.config/valet/Certificates/` | `~/.config/herd/Certificates/` | `%USERPROFILE%\.config\herd\Certificates\` |
| Sites symlinks | `~/.config/valet/Sites/` | `~/.config/herd/Sites/` | `%USERPROFILE%\.config\herd\Sites\` |
| Composer bin | `~/.composer/vendor/bin` | `~/.composer/vendor/bin` | `%APPDATA%\Composer\vendor\bin` |
| Default sites root | `~/Sites` | `~/Sites` | `%USERPROFILE%\Herd` |
| File browser | Finder (`open`) | Finder (`open`) | Explorer (`explorer.exe`) |
| Folder picker | `osascript` choose folder | `osascript` choose folder | PowerShell WinForms |
| Terminal | Terminal.app via `osascript` | Terminal.app via `osascript` | wt.exe → pwsh → powershell → cmd |
| Sudo required | Yes (for SSL, link, unlink) | Yes | No (Herd handles internally) |
| `check_sudo` result | `true` if sudo cache expired | `true` if sudo cache expired | always `false` |
| Browser detection | `/Applications` glob | `/Applications` glob | `%PROGRAMFILES%` path checks |
| SSL "Failed to fetch" | Yes — FPM restarts | Yes — FPM restarts | No — completes normally |

---

## 10. Known Behaviours & Gotchas

1. **SSL "Failed to fetch" (macOS only)** — `valet/herd secure` restarts PHP-FPM mid-request. JS treats `r.error === 'Failed to fetch'` as success, reloads after 3s. On Windows this never happens.

2. **No overflow:hidden on `.sc`** — hover menus use `position:fixed`. Never add it.

3. **No transform on `.sc`** — creates stacking context trapping z-index of children.

4. **Debug state is lazy** — `IntersectionObserver` triggers `debug_status` API when each card enters the viewport.

5. **Archive detection** — based on `Storage/db.json`, not the filesystem. Files still exist on disk.

6. **IDE URL schemes are cross-platform** — `phpstorm://`, `vscode://`, `cursor://` are registered by the IDE installer on both macOS and Windows. The URLs in `buildEntry()` are identical on both platforms.

7. **`WpExecutor` is macOS-only** — it is called exclusively by `MacosPlatform::exec()`. Never call it directly from `api.php` or anywhere else. `WindowsPlatform::exec()` has its own implementation.

8. **`SYS_HOME` replaces `getenv('HOME')`** — `getenv('HOME')` is empty on Windows. Always use the `SYS_HOME` constant.

9. **`APP_URL` is dynamic** — built from `$_SERVER['HTTPS']` and `$_SERVER['HTTP_HOST']` so it works for both HTTP and HTTPS and both `site-manager.test` and `valet-site-manager.test`.

10. **Tab hash routing** — `#sites`, `#tasks`, `#settings`, `#archive`. Settings is only in the 3-dot menu but hash navigation still works.

11. **Sudo wrapper cleanup** — temp files in `/tmp/vsm_*` are always deleted after `WpExecutor::exec()`, even on failure.

12. **Windows CRLF** — `WindowsPlatform::exec()` strips `\r\n` → `\n` from all command output.
