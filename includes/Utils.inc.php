<?php
declare(strict_types=1);

class SiteScanner
{
    public static function valetSites(): array
    {
        $home = getenv('HOME') ?: '';
        if (!$home) return [];
        $results = [];
        foreach ([$home . '/.config/valet/Sites', $home . '/.valet/Sites'] as $dir) {
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

    public static function sitePath(string $name, string $root): string|false
    {
        $clean = preg_replace('/[^a-z0-9_-]/i', '', $name);
        if ($clean === '' || $root === false) return false;

        $path = $root . DIRECTORY_SEPARATOR . $clean;
        if (is_dir($path)) return realpath($path) ?: false;

        foreach (self::valetSites() as $vName => $vPath) {
            if ($vName === $clean) return $vPath;
        }
        return false;
    }

    public static function detectType(string $path): ?string
    {
        if (file_exists($path . '/wp-config.php')) return 'wordpress';
        if (file_exists($path . '/index.php') || file_exists($path . '/index.html')) return 'static';
        return null;
    }

    public static function allSites(string $root): array
    {
        $entries = [];

        if ($root !== false) {
            $dirs = array_filter(scandir($root) ?: [], static function ($item) use ($root) {
                if (in_array($item, ['.', '..', basename(APP_DIR)], true)) return false;
                return is_dir($root . DIRECTORY_SEPARATOR . $item);
            });
            foreach ($dirs as $dir) {
                $path = $root . DIRECTORY_SEPARATOR . $dir;
                $type = self::detectType($path);
                if (!$type) continue;
                $entries[$dir] = self::buildEntry($dir, $path, $type, false);
            }

            foreach (self::valetSites() as $vName => $vPath) {
                if (isset($entries[$vName])) {
                    $entries[$vName]['linked'] = true;
                } else {
                    $type = self::detectType($vPath) ?? 'static';
                    $entries[$vName] = self::buildEntry($vName, $vPath, $type, true);
                }
            }
        }

        usort($entries, static fn($a, $b) => strcmp($a['name'], $b['name']));
        return array_values($entries);
    }

    private static function buildEntry(string $name, string $path, string $type, bool $linked): array
    {
        return [
            'name'     => $name,
            'url'      => 'http://' . $name . '.test',
            'admin'    => APP_URL . '/login.php?site=' . rawurlencode($name),
            'path'     => $path,
            'type'     => $type,
            'phpstorm' => 'phpstorm://open?file=' . rawurlencode($path),
            'vscode'   => 'vscode://file/' . str_replace('%2F', '/', rawurlencode($path)),
            'cursor'   => 'cursor://file/' . str_replace('%2F', '/', rawurlencode($path)),
            'linked'   => $linked,
        ];
    }
}

class WpExecutor
{
    /**
     * Execute a shell command.
     *
     * Pass $sudoPass when the command (or a subprocess it spawns) may call sudo.
     * A temporary sudo wrapper is placed first in PATH so that any sudo invocation
     * receives the password via stdin (-S) rather than requiring a TTY — this works
     * even on macOS where timestamp_type=tty prevents credential caching across
     * process boundaries.
     */
    public static function exec(string $cmd, string $cwd, string $sudoPass = ''): array
    {
        $phpBin = PHP_BINARY;
        $home   = getenv('HOME') ?: '/tmp';

        $tmpDir = null;
        if ($sudoPass !== '') {
            // Write a tiny sudo wrapper that injects the password via stdin.
            // The password is passed through an env var (never appears in argv).
            $tmpDir = sys_get_temp_dir() . '/vsm_' . bin2hex(random_bytes(4));
            @mkdir($tmpDir, 0700, true);
            file_put_contents(
                $tmpDir . '/sudo',
                "#!/bin/bash\nprintf '%s\\n' \"\$_VSM_SUDO_PASS\" | /usr/bin/sudo -S \"\$@\" 2>/dev/null\n"
            );
            chmod($tmpDir . '/sudo', 0700);
        }

        $pathPrefix = $tmpDir ? ($tmpDir . ':') : '';
        $env = array_merge(getenv() ?: [], [
            'WP_CLI_PHP'      => $phpBin,
            'WP_CLI_PHP_ARGS' => '-d error_reporting=24575',
            'PATH'            => $pathPrefix . dirname($phpBin)
                               . ':' . $home . '/.composer/vendor/bin'
                               . ':/usr/local/bin:/opt/homebrew/bin:/opt/homebrew/sbin'
                               . ':/usr/bin:/bin:/usr/sbin:/sbin',
            'HOME'            => $home,
        ]);
        if ($sudoPass !== '') $env['_VSM_SUDO_PASS'] = $sudoPass;

        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $desc, $pipes, $cwd, $env);
        if (!is_resource($proc)) {
            if ($tmpDir) { @unlink($tmpDir . '/sudo'); @rmdir($tmpDir); }
            return ['[error: proc_open failed]', 1];
        }
        fclose($pipes[0]);
        $out  = stream_get_contents($pipes[1]);
        $err  = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        if ($tmpDir) { @unlink($tmpDir . '/sudo'); @rmdir($tmpDir); }

        $combined = rtrim($out . ($err && $err !== $out ? "\n" . $err : ''));
        $combined = preg_replace('/^(Deprecated|Notice|Warning|PHP Deprecated|PHP Notice|PHP Warning):[ \t].+\r?\n?/m', '', $combined);
        $combined = preg_replace('/^[ \t]*in phar:\/\/.+on line[ \t]+\d+\r?\n?/m', '', $combined);
        return [trim($combined), $code];
    }
}
