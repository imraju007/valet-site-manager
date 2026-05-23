<?php
declare(strict_types=1);

/**
 * Platform Abstraction Layer.
 * All OS-specific operations are defined here and implemented per platform.
 * Business logic in api.php / Utils.inc.php calls this interface only.
 */
interface Platform
{
    // ── Config & paths ────────────────────────────────────────────────────

    /** Root config dir: ~/.config/valet (macOS Valet), ~/.config/herd (macOS Herd),
     *  or %USERPROFILE%\.config\herd (Windows Herd) */
    public function configDir(): string;

    /** All candidate Site symlink directories to probe in order */
    public function sitesDirs(): array;

    /** Directory containing {name}.test.crt SSL certificates */
    public function certsDir(): string;

    /** Directory containing config.json (paths, tld, …) */
    public function valetConfigFile(): string;

    /** Composer global vendor/bin path */
    public function composerBinDir(): string;

    /** Default parent directory for new sites */
    public function defaultSitesRootDir(): string;

    // ── Valet/Herd CLI ────────────────────────────────────────────────────

    /** Returns 'valet' or 'herd' depending on what is installed */
    public function valetBin(): string;

    // ── App detection ─────────────────────────────────────────────────────

    /** Returns array of installed browser keys, e.g. ['chrome','firefox'] */
    public function detectBrowsers(): array;

    /** Returns array of installed IDE keys, e.g. ['vscode','phpstorm'] */
    public function detectIDEs(): array;

    // ── OS actions ───────────────────────────────────────────────────────

    /** Open path in system file browser (Finder / Explorer) */
    public function openExplorer(string $path): void;

    /** Open URL in browser; $app = 'default'|'tab'|'chrome'|'firefox'|… */
    public function openInBrowser(string $url, string $app = 'default'): void;

    /** Open path in a terminal emulator */
    public function openTerminal(string $path): void;

    /** Open path in a terminal running wp shell */
    public function openWpCli(string $path): void;

    /** Show native folder-picker dialog; returns chosen path or null if cancelled */
    public function pickFolder(): ?string;

    // ── Command execution ─────────────────────────────────────────────────

    /** Run a shell command; $sudoPass is used only where sudo is needed (macOS) */
    public function exec(string $cmd, string $cwd, string $sudoPass = ''): array; // [output, exitCode]

    /** Whether the current OS/user setup requires a sudo password for Valet ops */
    public function needsSudoPassword(): bool;
}
