<?php
declare(strict_types=1);

class MacosPlatform implements Platform
{
    // ── Config & paths ────────────────────────────────────────────────────

    public function configDir(): string
    {
        static $dir = null;
        if ($dir !== null) return $dir;
        // Prefer Herd if installed, fall back to plain Valet
        $herd  = SYS_HOME . '/.config/herd';
        $valet = SYS_HOME . '/.config/valet';
        return $dir = is_dir($herd) ? $herd : $valet;
    }

    public function sitesDirs(): array
    {
        return [
            $this->configDir() . '/Sites',
            SYS_HOME . '/.valet/Sites', // legacy pre-config Valet path
        ];
    }

    public function certsDir(): string
    {
        return $this->configDir() . '/Certificates';
    }

    public function valetConfigFile(): string
    {
        return $this->configDir() . '/config.json';
    }

    public function composerBinDir(): string
    {
        return SYS_HOME . '/.composer/vendor/bin';
    }

    public function defaultSitesRootDir(): string
    {
        return SYS_HOME . '/Sites';
    }

    // ── Valet/Herd CLI ────────────────────────────────────────────────────

    public function valetBin(): string
    {
        static $bin = null;
        if ($bin !== null) return $bin;
        foreach (['/usr/local/bin/herd', '/opt/homebrew/bin/herd'] as $path) {
            if (file_exists($path)) return $bin = 'herd';
        }
        return $bin = 'valet';
    }

    // ── App detection ─────────────────────────────────────────────────────

    public function detectBrowsers(): array
    {
        $appDirs = array_filter(['/Applications', SYS_HOME . '/Applications'], 'is_dir');
        $found   = [];
        foreach ($appDirs as $dir) {
            foreach (glob("{$dir}/*.app") ?: [] as $p) {
                $found[basename($p)] = true;
            }
        }
        $map = [
            'chrome'  => 'Google Chrome.app',
            'firefox' => 'Firefox.app',
            'safari'  => 'Safari.app',
            'arc'     => 'Arc.app',
            'brave'   => 'Brave Browser.app',
        ];
        $result = [];
        foreach ($map as $key => $app) {
            if (isset($found[$app])) $result[] = $key;
        }
        return $result;
    }

    public function detectIDEs(): array
    {
        $appDirs = array_filter(['/Applications', SYS_HOME . '/Applications'], 'is_dir');
        $found   = [];
        foreach ($appDirs as $dir) {
            foreach (glob("{$dir}/*.app") ?: [] as $p) {
                $found[basename($p)] = true;
            }
        }
        $defs = [
            'phpstorm' => ['app' => 'PhpStorm.app',              'cli' => []],
            'vscode'   => ['app' => 'Visual Studio Code.app',     'cli' => ['/usr/local/bin/code', '/opt/homebrew/bin/code']],
            'cursor'   => ['app' => 'Cursor.app',                 'cli' => ['/usr/local/bin/cursor', '/opt/homebrew/bin/cursor']],
        ];
        $result = [];
        foreach ($defs as $key => $def) {
            if (isset($found[$def['app']])) { $result[] = $key; continue; }
            foreach ($def['cli'] as $bin) {
                if (file_exists($bin)) { $result[] = $key; break; }
            }
        }
        return $result;
    }

    // ── OS actions ───────────────────────────────────────────────────────

    public function openExplorer(string $path): void
    {
        exec('open ' . escapeshellarg($path) . ' > /dev/null 2>&1 &');
    }

    public function openInBrowser(string $url, string $app = 'default'): void
    {
        $u   = escapeshellarg($url);
        $map = [
            'chrome'  => 'Google Chrome',
            'firefox' => 'Firefox',
            'safari'  => 'Safari',
            'arc'     => 'Arc',
            'brave'   => 'Brave Browser',
        ];
        if ($app === '' || $app === 'default' || $app === 'tab') {
            exec("open {$u} > /dev/null 2>&1 &");
        } elseif (isset($map[$app])) {
            $a = escapeshellarg($map[$app]);
            exec("open -a {$a} {$u} > /dev/null 2>&1 &");
        } else {
            exec("open {$u} > /dev/null 2>&1 &");
        }
    }

    public function openTerminal(string $path): void
    {
        $safe   = str_replace(['"', '\\'], ['\\"', '\\\\'], $path);
        $script = "tell application \"Terminal\"\n\tdo script \"cd \\\"{$safe}\\\"\"\n\tactivate\nend tell";
        exec('osascript -e ' . escapeshellarg($script) . ' > /dev/null 2>&1 &');
    }

    public function openWpCli(string $path): void
    {
        $safe   = str_replace(['"', '\\'], ['\\"', '\\\\'], $path);
        $script = "tell application \"Terminal\"\n\tdo script \"cd \\\"{$safe}\\\" && wp shell\"\n\tactivate\nend tell";
        exec('osascript -e ' . escapeshellarg($script) . ' > /dev/null 2>&1 &');
    }

    public function pickFolder(): ?string
    {
        $script = "POSIX path of (choose folder with prompt \"Select a directory:\")";
        $desc   = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc   = @proc_open(['osascript', '-e', $script], $desc, $pipes);
        if (!is_resource($proc)) return null;
        fclose($pipes[0]);
        $out  = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        return ($code === 0 && $out !== '') ? rtrim($out, "/\n") : null;
    }

    // ── Command execution ─────────────────────────────────────────────────

    public function exec(string $cmd, string $cwd, string $sudoPass = ''): array
    {
        return WpExecutor::exec($cmd, $cwd, $sudoPass);
    }

    public function needsSudoPassword(): bool
    {
        [, $code] = WpExecutor::exec('sudo -n true 2>/dev/null', APP_DIR);
        return $code !== 0;
    }
}
