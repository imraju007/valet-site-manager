# Valet Site Manager — Installation Guide

A cross-platform local WordPress development dashboard. Choose your platform below.

---

## Platform Support

| | macOS | Windows |
|---|---|---|
| Local server | Laravel Valet **or** Laravel Herd | Laravel Herd |
| PHP | Homebrew or Herd built-in | Herd built-in |
| MySQL | Homebrew | Herd built-in or standalone |
| WP-CLI | Homebrew | Manual install |
| Terminal | Terminal.app / iTerm2 | cmd / PowerShell / Windows Terminal |
| Folder picker | Native (osascript) | Native (PowerShell WinForms) |

---

---

# macOS Installation

## Prerequisites

- macOS 12 Monterey or later
- Admin access (some steps require `sudo`)
- Terminal.app or iTerm2

---

## Part 1 — Install the Stack

### Step 1 — Xcode Command Line Tools

```bash
xcode-select --install
```

A dialog will appear — click **Install**. Takes a few minutes. Required for Git and build tools.

---

### Step 2 — Homebrew

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

When it finishes, follow the **"Next steps"** printed at the end to add Homebrew to your PATH.

For Apple Silicon Macs, add to `~/.zprofile`:
```bash
echo 'eval "$(/opt/homebrew/bin/brew shellenv)"' >> ~/.zprofile
eval "$(/opt/homebrew/bin/brew shellenv)"
```

Verify:
```bash
brew --version
```

---

### Step 3 — PHP

```bash
brew install php
```

Verify:
```bash
php -v   # should print PHP 8.x.x
```

---

### Step 4 — Composer

```bash
brew install composer
```

Add Composer's global bin to your PATH. Add to `~/.zshrc` or `~/.zprofile`:
```bash
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

Reload:
```bash
source ~/.zshrc
```

Verify:
```bash
composer --version
```

---

### Step 5 — Laravel Valet

> **Alternative:** If you prefer a GUI, install [Laravel Herd](https://herd.laravel.com) instead of Valet. Herd bundles PHP, Valet, and a UI. Skip Steps 3–5 and use Herd's built-in PHP and site management. Valet Site Manager detects Herd automatically.

```bash
composer global require laravel/valet
valet install
```

Valet installs Nginx, sets up a local DNS resolver for `.test` domains, and starts as a background service.

Verify:
```bash
valet --version
ping -c1 anything.test   # should resolve to 127.0.0.1
```

---

### Step 6 — MySQL

```bash
brew install mysql
brew services start mysql
```

Optionally set a root password:
```bash
mysql_secure_installation
```

> **Tip:** Valet Site Manager defaults to `root` with no password. If you set a password, enter it in **Settings → DB Config** after setup.

Verify:
```bash
brew services list | grep mysql
```

---

### Step 7 — phpMyAdmin _(optional)_

A browser-based GUI for MySQL, available at `http://phpmyadmin.test`.

```bash
brew install phpmyadmin
```

Copy and edit the config:
```bash
cp "$(brew --prefix)/share/phpmyadmin/config.sample.inc.php" \
   "$(brew --prefix)/share/phpmyadmin/config.inc.php"
open "$(brew --prefix)/share/phpmyadmin/config.inc.php"
```

Make two edits inside `config.inc.php`:

**1. Set a blowfish secret** (any 32-character string):
```php
$cfg['blowfish_secret'] = 'your-32-character-random-string!!';
```
Generate one:
```bash
openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 32
```

**2. Set the host to `127.0.0.1`:**
```php
$cfg['Servers'][$i]['host'] = '127.0.0.1';
```

Link with Valet:
```bash
cd "$(brew --prefix)/share/phpmyadmin"
valet link phpmyadmin
```

Visit **http://phpmyadmin.test** — log in with your MySQL `root` credentials.

---

### Step 8 — WP-CLI

```bash
brew install wp-cli
```

Verify:
```bash
wp --version
```

If `wp` is not found by Valet's PHP process, symlink it:
```bash
sudo ln -sf $(which wp) /usr/local/bin/wp
```

---

## Part 2 — Set Up Your Dev Environment

### Step 9 — Create a sites directory and park it

```bash
mkdir ~/wordpress-dev
cd ~/wordpress-dev
valet park
```

`valet park` makes every subdirectory automatically available as `<name>.test`. No further config needed for new sites.

---

### Step 10 — Place Valet Site Manager

Clone the repo into your parked directory:
```bash
git clone <repo-url> ~/wordpress-dev/site-manager
```

Because `~/wordpress-dev` is parked, `site-manager.test` resolves automatically. Only run `valet link` if the folder is outside any parked directory:
```bash
cd ~/wordpress-dev/site-manager
valet link site-manager
```

---

### Step 11 — Open the dashboard

Visit **http://site-manager.test** in your browser.

---

## macOS Troubleshooting

**Site not loading at `.test`**
```bash
valet start
valet links
ping -c1 site-manager.test   # should return 127.0.0.1
```

**`wp: command not found` during site creation**
```bash
sudo ln -sf $(which wp) /usr/local/bin/wp
```

**MySQL connection refused**
```bash
brew services start mysql
```

**PHP version mismatch**
```bash
php -v          # CLI version
valet php -v    # Valet's version — should match
```
Switch: `valet use php@8.2`

**phpMyAdmin not loading**
```bash
valet links    # confirm phpmyadmin is listed
valet start
```
Re-link if missing:
```bash
cd "$(brew --prefix)/share/phpmyadmin" && valet link phpmyadmin
```

**phpMyAdmin "Access denied for 'root'@'localhost'"**

Change `localhost` to `127.0.0.1` in `config.inc.php` — macOS MySQL uses a socket for `localhost` but TCP for `127.0.0.1`.

**Permission denied errors**
```bash
sudo chown -R $(whoami) ~/wordpress-dev
```

**SSL toggle shows "Failed to fetch"**

This is normal on macOS — `valet secure` restarts PHP-FPM, killing the in-flight request. The app treats this as success and reloads after 3 seconds.

---

---

# Windows Installation

## Prerequisites

- Windows 10 (version 2004+) or Windows 11
- Administrator access
- PowerShell 5.1+ (built-in) or PowerShell 7+ (recommended)
- A modern browser (Chrome, Edge, Firefox)

---

## Part 1 — Install the Stack

### Step 1 — Laravel Herd

Download and install **[Laravel Herd for Windows](https://herd.laravel.com)**.

Herd is an all-in-one local development environment. It bundles:
- PHP (multiple versions, switchable)
- Nginx
- Local DNS for `.test` domains
- `herd` CLI
- SSL certificate management

After installing, Herd starts automatically as a system tray app.

Verify the CLI is working — open PowerShell or Windows Terminal:
```powershell
herd --version
```

Verify `.test` DNS is working:
```powershell
ping site-manager.test   # should resolve to 127.0.0.1
```

> **Note:** Herd's default sites directory is `%USERPROFILE%\Herd` (`C:\Users\YourName\Herd`). Every subfolder here is automatically served as `<name>.test` — no manual linking needed.

---

### Step 2 — Composer

Download and run the **[Composer Windows installer](https://getcomposer.org/Composer-Setup.exe)**.

The installer automatically adds Composer to your PATH and finds Herd's PHP.

Verify in a new PowerShell window:
```powershell
composer --version
```

> **Important:** Open a **new** PowerShell window after installing so the updated PATH takes effect.

---

### Step 3 — WP-CLI

WP-CLI does not have a Windows installer — you need to set it up manually.

**Option A — Via Composer (recommended):**
```powershell
composer global require wp-cli/wp-cli-bundle
```

This installs `wp` into `%APPDATA%\Composer\vendor\bin`. Valet Site Manager adds this directory to the PATH automatically when running commands.

Verify:
```powershell
wp --version
```

**Option B — Manual download:**

1. Download `wp-cli.phar` from [wp-cli.org](https://wp-cli.org)
2. Create `C:\wp-cli\` and move the file there, renaming it to `wp.bat` with this content:
   ```bat
   @ECHO OFF
   php "C:\wp-cli\wp-cli.phar" %*
   ```
3. Add `C:\wp-cli` to your system PATH via **System Properties → Environment Variables**

---

### Step 4 — MySQL

Herd for Windows does **not** bundle MySQL. Install it separately.

**Option A — MySQL Installer (recommended):**

Download [MySQL Community Installer](https://dev.mysql.com/downloads/installer/) and run it. Choose **Developer Default** or **Server Only**.

During setup:
- Set authentication method to **Legacy Authentication** (required for WP-CLI)
- Set a root password or leave blank for passwordless local access

After install, verify MySQL is running in **Services** (`services.msc`) or:
```powershell
mysqladmin -u root ping
```

**Option B — Via Laragon or XAMPP:**

If you already have Laragon or XAMPP installed, point Valet Site Manager at that MySQL instance by entering the correct host, port, user, and password in **Settings → DB Config**.

---

### Step 5 — phpMyAdmin _(optional)_

**Option A — Via Herd (simplest):**

Herd for Windows does not include phpMyAdmin natively. Download the zip from [phpmyadmin.net](https://www.phpmyadmin.net/downloads/), extract it to `%USERPROFILE%\Herd\phpmyadmin\`, and access it at **http://phpmyadmin.test**.

Configure by copying `config.sample.inc.php` → `config.inc.php` and editing:
```php
$cfg['blowfish_secret'] = 'your-32-character-random-string!!';
$cfg['Servers'][$i]['host'] = '127.0.0.1';
```

**Option B — Standalone:**

Use [HeidiSQL](https://www.heidisql.com) or [TablePlus](https://tableplus.com) as a native GUI alternative to phpMyAdmin. Both work without Valet/Herd.

---

### Step 6 — Windows Terminal _(optional but recommended)_

Install **[Windows Terminal](https://aka.ms/terminal)** from the Microsoft Store. Valet Site Manager's **Terminal** button will use it automatically if present — it is a much better experience than cmd.exe.

---

## Part 2 — Set Up Your Dev Environment

### Step 7 — Sites directory

Herd auto-parks `%USERPROFILE%\Herd` on installation. Every subfolder is served as `<name>.test` automatically — no extra steps needed.

If you want to use a different directory:
1. Open Herd → **General → Add Directory**
2. Add your preferred path (e.g. `C:\Users\YourName\wordpress-dev`)

---

### Step 8 — Place Valet Site Manager

Clone the repo into your Herd directory:
```powershell
cd $env:USERPROFILE\Herd
git clone <repo-url> site-manager
```

Because `%USERPROFILE%\Herd` is auto-parked by Herd, `site-manager.test` resolves immediately.

If you place it in a custom directory that you added in Step 7, it will also be served automatically.

---

### Step 9 — Open the dashboard

Visit **http://site-manager.test** in your browser.

---

## Part 3 — Configuration (both platforms)

### Settings → WP Admin Config

Default credentials used for new WordPress sites and one-click admin login.

| Field | Default | Notes |
|---|---|---|
| Username | `admin` | |
| Password | `admin` | |
| Email | _(auto)_ | Use `{sitename}` as placeholder |

---

### Settings → DB Config

MySQL connection details used when creating new sites.

| Field | Default | Notes |
|---|---|---|
| Host | `127.0.0.1` | Use `127.0.0.1`, not `localhost` |
| User | `root` | |
| Password | _(empty)_ | Set if you configured one during MySQL install |

> **Windows note:** Always use `127.0.0.1`, not `localhost`. On Windows, `localhost` may resolve to IPv6 (`::1`) and fail to connect.

---

### Settings → New Site Defaults

Pre-fill plugins and themes installed on every new WordPress site. One per line — use WordPress.org slugs or zip URLs.

---

## Windows Troubleshooting

**Site not loading at `.test`**

1. Check Herd is running — look for the tray icon
2. Confirm DNS is resolving:
   ```powershell
   nslookup site-manager.test
   ```
   Should return `127.0.0.1`. If not, open Herd → restart DNS service.

3. Check the site is served:
   ```powershell
   herd links
   ```

**`wp: command not found` during site creation**

Ensure WP-CLI is in `%APPDATA%\Composer\vendor\bin`. Run:
```powershell
where wp
```
If not found, re-run `composer global require wp-cli/wp-cli-bundle` in a new terminal.

**MySQL connection refused when creating a site**

1. Verify MySQL service is running:
   ```powershell
   Get-Service -Name "MySQL*"
   ```
2. Start it if stopped:
   ```powershell
   Start-Service MySQL80   # name may vary
   ```
3. Confirm credentials in **Settings → DB Config**. Use `127.0.0.1` as host.

**Folder picker (pick path) not working**

The folder picker uses PowerShell `System.Windows.Forms`. Verify PowerShell is available:
```powershell
powershell -NoProfile -Command "Add-Type -AssemblyName System.Windows.Forms; Write-Output OK"
```
Should print `OK`. If not, your PowerShell execution policy may be blocking it. Run in an admin PowerShell:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

**Terminal button opens cmd instead of Windows Terminal**

Install Windows Terminal from the Microsoft Store. Valet Site Manager prefers `wt.exe` when available. After installing, no configuration is needed — it will be picked up automatically on the next use.

**SSL toggle not working on Windows**

Ensure Herd is running with sufficient permissions. `herd secure` and `herd unsecure` require Herd's background service to be active. If the SSL toggle returns an error, try running:
```powershell
herd secure site-name
```
directly in PowerShell to see the full error message.

**Auto-login fails**

Confirm the credentials in **Settings → WP Admin Config** match the actual WP admin account. Auto-login reads these credentials and authenticates via a one-time token — if the credentials are wrong it will redirect back to `wp-login.php`.

**PHP version mismatch**

Herd manages PHP versions. Open the Herd tray app → select the PHP version to use for your sites. The CLI and server versions should match. Run:
```powershell
php -v
herd php -v
```
Switch version in Herd UI if they differ.

**`proc_open` errors or blank API responses**

PHP must have `proc_open` enabled. Open Herd → PHP → verify no `disable_functions` line blocks it. Valet Site Manager uses `proc_open` for all command execution.
