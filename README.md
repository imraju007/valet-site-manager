# Valet Site Manager

A cross-platform local WordPress development dashboard. Manage all your `.test` sites, tasks, and tooling from one place.

Runs on **macOS** (Laravel Valet or Herd) and **Windows** (Laravel Herd).

## Demo

![Demo](assets/preview/demo.gif)

## Screenshots

<p align="center">
  <img src="assets/preview/dashboard-dark.png" width="32%">
  <img src="assets/preview/dashboard-dark-list.png" width="32%">
  <img src="assets/preview/dashboard-light.png" width="32%">
  <img src="assets/preview/tasks-dark.png" width="32%">
  <img src="assets/preview/tasks-light.png" width="32%">
  <img src="assets/preview/settings-dark.png" width="32%">
  <img src="assets/preview/settings-light.png" width="32%">
  <img src="assets/preview/archive-dark.png" width="32%">
  <img src="assets/preview/archive-light.png" width="32%">
</p>

---

## Platform Support

| | macOS | Windows |
|---|---|---|
| Local server | Laravel Valet **or** Laravel Herd | Laravel Herd |
| PHP | Homebrew or Herd built-in | Herd built-in |
| MySQL | Homebrew | Standalone installer |
| WP-CLI | Homebrew | Via Composer |
| Terminal | Terminal.app / iTerm2 | Windows Terminal / PowerShell |
| Folder picker | Native (osascript) | Native (PowerShell WinForms) |

---

## Installation

See **[install.md](install.md)** for full step-by-step instructions for both platforms.

**Quick start (macOS):**
```bash
brew install php composer mysql wp-cli
composer global require laravel/valet && valet install
brew services start mysql
mkdir ~/wordpress-dev && cd ~/wordpress-dev && valet park
git clone <repo-url> site-manager
```
Visit **http://site-manager.test**

**Quick start (Windows):**
1. Install [Laravel Herd](https://herd.laravel.com) — it bundles PHP, Nginx, and `.test` DNS
2. Install [Composer](https://getcomposer.org/Composer-Setup.exe) and run `composer global require wp-cli/wp-cli-bundle`
3. Install [MySQL Community](https://dev.mysql.com/downloads/installer/)
4. Clone into `%USERPROFILE%\Herd\`: `git clone <repo-url> site-manager`

Visit **http://site-manager.test**

---

## Features

### Site Cards

Every site in your parked directory appears as a card showing its name, URL, type badge, SSL status, and debug indicator.

| Button | Action |
|--------|--------|
| **Open** | Opens the site in your browser |
| **Admin** | One-click login to `wp-admin` |
| **Terminal** | Opens a terminal `cd`'d to the site root |
| **WP-CLI** | Opens a terminal running `wp shell` |
| **Explorer / Finder** | Opens the site folder in your file browser |
| **IDE** | Opens the site in VS Code, Cursor, or PhpStorm |

Toggle which buttons appear in **Settings → Appearance → Action Buttons**.

### Create a New Site

Click **New Site** and fill in the form. For a WordPress site the dashboard will:

1. Create the folder and Valet/Herd link
2. Download WordPress core
3. Create the MySQL database
4. Write `wp-config.php`
5. Run the WordPress installer
6. Install any default plugins and themes from **Settings → New Site Defaults**

Each step streams live in the activity log panel.

### Debug Log

Click the **Bug** icon on any WordPress site card to:

- Toggle `WP_DEBUG` / `WP_DEBUG_LOG` on or off (badge appears in the card header when enabled)
- View the last 300 lines of `wp-content/debug.log`
- Clear the log with one click

### Database Cleaner

Click the **DB** icon to flush the object cache, delete all transients, and remove post revisions — all via WP-CLI.

### Tasks

A per-site task board with title, description, priority, due date, status (Todo / In Progress / Done), subtasks, and a ring progress indicator.

### Archive

Archive sites you're not actively working on. Archived sites are hidden from the main dashboard and can be restored at any time.

---

## Configuration

### Settings → WP Admin Config

Default credentials for new WordPress installs and one-click admin login.

| Field | Default | Notes |
|-------|---------|-------|
| Username | `admin` | |
| Password | `admin` | |
| Email | _(auto)_ | Use `{sitename}` as a placeholder |

### Settings → DB Config

MySQL connection used when creating new sites.

| Field | Default | Notes |
|-------|---------|-------|
| Host | `127.0.0.1` | Always use `127.0.0.1`, not `localhost` |
| User | `root` | |
| Password | _(empty)_ | Set if you configured one during MySQL install |

### Settings → New Site Defaults

Plugins and themes to install on every new WordPress site. One per line — WordPress.org slugs or zip URLs.

### Settings → Appearance

Switch between built-in color presets (Cyber, Emerald, Purple, Amber, Rose) or build a custom preset with the color picker.

---

## Folder Structure

```
~/wordpress-dev/          ← parked directory (macOS)
%USERPROFILE%\Herd\       ← parked directory (Windows)
├── site-manager/         ← this app → site-manager.test
├── my-project/           ← WordPress site → my-project.test
├── client-site/          ← another site → client-site.test
└── static-site/          ← static site → static-site.test
```

`Storage/db.json` is created on first use and stores all tasks and settings.

---

## Troubleshooting

For platform-specific troubleshooting see [install.md](install.md).

**Site not loading at `.test`**
```bash
# macOS
valet start && valet links

# Windows — check Herd tray icon is running, then:
herd links
```

**`wp: command not found` during site creation**
```bash
# macOS
sudo ln -sf $(which wp) /usr/local/bin/wp

# Windows
composer global require wp-cli/wp-cli-bundle
```

**MySQL connection refused**
```bash
# macOS
brew services start mysql

# Windows (PowerShell as Admin)
Start-Service MySQL80
```

**Auto-login fails**

Confirm the credentials in **Settings → WP Admin Config** match the actual WordPress admin account for that site.

**PHP version mismatch**
```bash
# macOS
php -v && valet php -v    # switch: valet use php@8.2

# Windows
php -v && herd php -v     # switch in Herd tray app
```
