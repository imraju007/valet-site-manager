<?php
declare(strict_types=1);

class WindowsPlatform implements Platform
{
    // ── Config & paths ────────────────────────────────────────────────────

    public function configDir(): string
    {
        // Laravel Herd for Windows stores config at %USERPROFILE%\.config\herd
        return SYS_HOME . '\\.config\\herd';
    }

    public function sitesDirs(): array
    {
        // Herd mirrors Valet's internal structure under its config dir
        return [$this->configDir() . '\\Sites'];
    }

    public function certsDir(): string
    {
        return $this->configDir() . '\\Certificates';
    }

    public function valetConfigFile(): string
    {
        return $this->configDir() . '\\config.json';
    }

    public function composerBinDir(): string
    {
        $appData = getenv('APPDATA') ?: (SYS_HOME . '\\AppData\\Roaming');
        return $appData . '\\Composer\\vendor\\bin';
    }

    public function defaultSitesRootDir(): string
    {
        // Herd for Windows auto-parks ~/Herd
        return SYS_HOME . '\\Herd';
    }

    // ── Valet/Herd CLI ────────────────────────────────────────────────────

    public function valetBin(): string
    {
        return 'herd';
    }

    // ── App detection ─────────────────────────────────────────────────────

    public function detectBrowsers(): array
    {
        $pf      = getenv('PROGRAMFILES')      ?: 'C:\\Program Files';
        $pf86    = getenv('PROGRAMFILES(X86)') ?: 'C:\\Program Files (x86)';
        $local   = getenv('LOCALAPPDATA')      ?: (SYS_HOME . '\\AppData\\Local');

        $candidates = [
            'chrome'  => [
                "{$pf}\\Google\\Chrome\\Application\\chrome.exe",
                "{$pf86}\\Google\\Chrome\\Application\\chrome.exe",
                "{$local}\\Google\\Chrome\\Application\\chrome.exe",
            ],
            'firefox' => [
                "{$pf}\\Mozilla Firefox\\firefox.exe",
                "{$pf86}\\Mozilla Firefox\\firefox.exe",
            ],
            'brave'   => [
                "{$pf}\\BraveSoftware\\Brave-Browser\\Application\\brave.exe",
                "{$local}\\BraveSoftware\\Brave-Browser\\Application\\brave.exe",
            ],
            'edge'    => [
                "{$pf}\\Microsoft\\Edge\\Application\\msedge.exe",
            ],
        ];

        $result = [];
        foreach ($candidates as $key => $paths) {
            foreach ($paths as $p) {
                if (file_exists($p)) { $result[] = $key; break; }
            }
        }
        return $result;
    }

    public function detectIDEs(): array
    {
        $local = getenv('LOCALAPPDATA') ?: (SYS_HOME . '\\AppData\\Local');

        $candidates = [
            'vscode'   => ["{$local}\\Programs\\Microsoft VS Code\\Code.exe"],
            'cursor'   => ["{$local}\\Programs\\cursor\\Cursor.exe"],
        ];

        $result = [];
        foreach ($candidates as $key => $paths) {
            foreach ($paths as $p) {
                if (file_exists($p)) { $result[] = $key; break; }
            }
        }

        // PhpStorm is installed via JetBrains Toolbox to a versioned path
        $toolbox = $local . '\\JetBrains\\Toolbox\\apps\\PhpStorm';
        if (is_dir($toolbox)) $result[] = 'phpstorm';

        return $result;
    }

    // ── OS actions ───────────────────────────────────────────────────────

    public function openExplorer(string $path): void
    {
        // explorer.exe needs backslashes and no trailing slash
        $win = rtrim(str_replace('/', '\\', $path), '\\');
        exec('explorer.exe ' . escapeshellarg($win));
    }

    public function openInBrowser(string $url, string $app = 'default'): void
    {
        $local = getenv('LOCALAPPDATA') ?: (SYS_HOME . '\\AppData\\Local');
        $pf    = getenv('PROGRAMFILES')  ?: 'C:\\Program Files';

        $exeMap = [
            'chrome'  => "{$pf}\\Google\\Chrome\\Application\\chrome.exe",
            'firefox' => "{$pf}\\Mozilla Firefox\\firefox.exe",
            'brave'   => "{$local}\\BraveSoftware\\Brave-Browser\\Application\\brave.exe",
            'edge'    => "{$pf}\\Microsoft\\Edge\\Application\\msedge.exe",
        ];

        $u = escapeshellarg($url);
        if ($app === '' || $app === 'default' || $app === 'tab' || !isset($exeMap[$app])) {
            // start "" is required so the URL is not treated as the window title
            exec("start \"\" {$u}");
        } else {
            $exe = escapeshellarg($exeMap[$app]);
            exec("start \"\" {$exe} {$u}");
        }
    }

    public function openTerminal(string $path): void
    {
        $safe = escapeshellarg($path);
        if ($this->_hasExe('wt.exe')) {
            // Windows Terminal — preferred
            exec("wt.exe -d {$safe}");
        } elseif ($this->_hasExe('pwsh.exe')) {
            // PowerShell 7+
            exec("start pwsh.exe -NoExit -Command Set-Location {$safe}");
        } elseif ($this->_hasExe('powershell.exe')) {
            exec("start powershell.exe -NoExit -Command Set-Location {$safe}");
        } else {
            $win = str_replace('/', '\\', $path);
            exec('start cmd.exe /K "cd /d ' . escapeshellarg($win) . '"');
        }
    }

    public function openWpCli(string $path): void
    {
        $safe = escapeshellarg($path);
        if ($this->_hasExe('wt.exe')) {
            exec("wt.exe -d {$safe} powershell.exe -NoExit -Command wp shell");
        } else {
            $win = str_replace('/', '\\', $path);
            exec('start cmd.exe /K "cd /d ' . escapeshellarg($win) . ' && wp shell"');
        }
    }

    public function pickFolder(): ?string
    {
        // PowerShell WinForms folder browser dialog
        $ps = 'Add-Type -AssemblyName System.Windows.Forms; '
            . '$d = New-Object System.Windows.Forms.FolderBrowserDialog; '
            . '$d.Description = "Select a directory"; '
            . 'if ($d.ShowDialog() -eq [System.Windows.Forms.DialogResult]::OK) '
            . '{ Write-Output $d.SelectedPath }';

        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = @proc_open(
            ['powershell', '-NoProfile', '-NonInteractive', '-Command', $ps],
            $desc, $pipes
        );
        if (!is_resource($proc)) return null;
        fclose($pipes[0]);
        $out  = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
        return $out !== '' ? $out : null;
    }

    // ── Command execution ─────────────────────────────────────────────────

    public function exec(string $cmd, string $cwd, string $sudoPass = ''): array
    {
        // Windows does not use sudo — Herd handles its own elevation internally.
        // $sudoPass is accepted but silently ignored.
        $phpBin  = PHP_BINARY;
        $appData = getenv('APPDATA')    ?: (SYS_HOME . '\\AppData\\Roaming');
        $local   = getenv('LOCALAPPDATA') ?: (SYS_HOME . '\\AppData\\Local');
        $pf      = getenv('PROGRAMFILES') ?: 'C:\\Program Files';

        $env = array_merge(getenv() ?: [], [
            'WP_CLI_PHP'      => $phpBin,
            'WP_CLI_PHP_ARGS' => '-d error_reporting=24575',
            'PATH'            => $this->composerBinDir()
                               . ';C:\\ProgramData\\herd\\bin'
                               . ";{$pf}\\PHP"
                               . ';' . (getenv('PATH') ?: ''),
            'HOME'            => SYS_HOME,
        ]);

        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $desc, $pipes, $cwd, $env);
        if (!is_resource($proc)) {
            return ['[error: proc_open failed]', 1];
        }
        fclose($pipes[0]);
        $out  = stream_get_contents($pipes[1]);
        $err  = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        $combined = str_replace("\r\n", "\n", rtrim($out . ($err && $err !== $out ? "\n" . $err : '')));
        $combined = preg_replace('/^(Deprecated|Notice|Warning|PHP Deprecated|PHP Notice|PHP Warning):[ \t].+\n?/m', '', $combined);
        $combined = preg_replace('/^[ \t]*in phar:\/\/.+on line[ \t]+\d+\n?/m', '', $combined);
        return [trim($combined), $code];
    }

    public function needsSudoPassword(): bool
    {
        return false; // Herd handles elevation internally on Windows
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function _hasExe(string $exe): bool
    {
        exec('where ' . escapeshellarg($exe) . ' 2>nul', $out, $code);
        return $code === 0 && !empty($out);
    }
}
