<?php
declare(strict_types=1);

/**
 * Handles all read/write operations for db.json.
 *
 * Schema:
 *   {
 *     "__tasks__":    { "<site-slug>": [ {...task}, ... ] },
 *     "__settings__": {
 *       "appearance":       { "action_btn": {}, "presets": [] },
 *       "new_site_default": { "plugins": [], "themes": [], "folder_structure": {} },
 *       "db_config":        { "host": "", "user": "", "password": "" },
 *       "wp_admin_config":  { "user": "", "password": "", "email": "" }
 *     }
 *   }
 */
class Database
{
    private string $path;
    private ?array $data = null;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    // ── Internal I/O ──────────────────────────────────────────────────────

    private function read(): array
    {
        if (!file_exists($this->path)) return [];
        return json_decode(file_get_contents($this->path), true) ?? [];
    }

    private function write(): void
    {
        file_put_contents(
            $this->path,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    /** Return the live data array, migrating and persisting once if needed. */
    private function data(): array
    {
        if ($this->data !== null) return $this->data;

        $raw      = $this->read();
        $migrated = $this->migrate($raw);
        $this->data = $migrated;

        if ($migrated !== $raw) $this->write();

        return $this->data;
    }

    // ── Schema helpers ────────────────────────────────────────────────────

    public static function defaultSettings(): array
    {
        return [
            'appearance'       => ['action_btn' => [], 'presets' => []],
            'new_site_default' => ['plugins' => [], 'themes' => [], 'folder_structure' => (object)[], 'wp_parent_dir' => '', 'static_parent_dir' => ''],
            'db_config'        => ['host' => '', 'user' => '', 'password' => '', 'pma_url' => ''],
            'wp_admin_config'  => ['user' => '', 'password' => '', 'email' => ''],
        ];
    }

    /**
     * Convert old flat structure to new nested structure.
     * Old: { "__settings": {...}, "<site>": [...] }
     * New: { "__tasks__": { "<site>": [...] }, "__settings__": { ... } }
     */
    private static function migrate(array $db): array
    {
        if (isset($db['__tasks__']) || isset($db['__settings__'])) return $db;

        $new = ['__tasks__' => [], '__settings__' => self::defaultSettings()];

        if (isset($db['__settings'])) {
            $old = $db['__settings'];

            $new['__settings__']['new_site_default']['plugins'] =
                array_values(array_filter(array_map('trim', explode("\n", $old['default_plugins'] ?? ''))));
            $new['__settings__']['new_site_default']['themes'] =
                array_values(array_filter(array_map('trim', explode("\n", $old['default_themes']  ?? ''))));

            $rawFs = $old['default_structure'] ?? '';
            if ($rawFs !== '') {
                $parsed = json_decode($rawFs, true);
                if (is_array($parsed)) {
                    $new['__settings__']['new_site_default']['folder_structure'] = $parsed;
                }
            }

            $new['__settings__']['wp_admin_config'] = [
                'user'     => $old['admin_user']     ?? '',
                'password' => $old['admin_password'] ?? '',
                'email'    => $old['admin_email']    ?? '',
            ];
            $new['__settings__']['db_config'] = [
                'host'     => $old['db_host']     ?? '',
                'user'     => $old['db_user']     ?? '',
                'password' => $old['db_password'] ?? '',
            ];
        }

        foreach ($db as $key => $val) {
            if (in_array($key, ['__settings', '__tasks__', '__settings__'], true)) continue;
            if (is_array($val)) $new['__tasks__'][$key] = $val;
        }

        return $new;
    }

    // ── Tasks ─────────────────────────────────────────────────────────────

    /** Return all tasks for a site. */
    public function getTasks(string $site): array
    {
        return $this->data()['__tasks__'][$site] ?? [];
    }

    /**
     * Create or update a task for a site.
     * If $task has no 'id', a new task is created and returned.
     * Throws RuntimeException if the id is set but not found.
     */
    public function saveTask(string $site, array $task): array
    {
        $this->data();
        $this->data['__tasks__'][$site] ??= [];

        if (empty($task['id'])) {
            $task['id']         = bin2hex(random_bytes(8));
            $task['created_at'] = date('Y-m-d');
            $task['subtasks']   ??= [];
            $task['status']     ??= 'todo';
            $task['priority']   ??= 'medium';
            $this->data['__tasks__'][$site][] = $task;
        } else {
            $found = false;
            foreach ($this->data['__tasks__'][$site] as &$t) {
                if ($t['id'] === $task['id']) {
                    $t     = $task;
                    $found = true;
                    break;
                }
            }
            unset($t);
            if (!$found) throw new RuntimeException('Task not found');
        }

        $this->write();
        return $task;
    }

    /** Remove a task by id. No-op if the task does not exist. */
    public function deleteTask(string $site, string $taskId): void
    {
        $this->data();
        if (!isset($this->data['__tasks__'][$site])) return;

        $this->data['__tasks__'][$site] = array_values(
            array_filter($this->data['__tasks__'][$site], fn($t) => $t['id'] !== $taskId)
        );

        if (empty($this->data['__tasks__'][$site])) {
            unset($this->data['__tasks__'][$site]);
        }

        $this->write();
    }

    // ── Settings ──────────────────────────────────────────────────────────

    /** Return the full settings array. */
    public function getSettings(): array
    {
        return $this->data()['__settings__'] ?? self::defaultSettings();
    }

    /**
     * Persist one settings section.
     *
     * Allowed sections: new_site_default | wp_admin_config | db_config | appearance
     * Throws InvalidArgumentException for unknown sections.
     */
    public function saveSettings(string $section, array $data): void
    {
        $allowed = ['new_site_default', 'wp_admin_config', 'db_config', 'appearance'];
        if (!in_array($section, $allowed, true)) {
            throw new InvalidArgumentException("Unknown settings section: {$section}");
        }

        $this->data();
        $this->data['__settings__'] ??= self::defaultSettings();

        match ($section) {
            'new_site_default' => $this->saveNewSiteDefault($data),
            'wp_admin_config'  => $this->saveWpAdminConfig($data),
            'db_config'        => $this->saveDbConfig($data),
            'appearance'       => $this->saveAppearance($data),
        };

        $this->write();
    }

    private function saveNewSiteDefault(array $data): void
    {
        $this->data['__settings__']['new_site_default']['plugins'] =
            array_values(array_filter(array_map('trim', (array)($data['plugins'] ?? []))));
        $this->data['__settings__']['new_site_default']['themes'] =
            array_values(array_filter(array_map('trim', (array)($data['themes']  ?? []))));

        $fs = $data['folder_structure'] ?? [];
        $this->data['__settings__']['new_site_default']['folder_structure'] =
            (is_array($fs) && !empty($fs)) ? $fs : (object)[];

        $this->data['__settings__']['new_site_default']['wp_parent_dir']     = trim($data['wp_parent_dir']     ?? '');
        $this->data['__settings__']['new_site_default']['static_parent_dir'] = trim($data['static_parent_dir'] ?? '');
    }

    private function saveWpAdminConfig(array $data): void
    {
        $this->data['__settings__']['wp_admin_config'] = [
            'user'     => trim($data['user']     ?? ''),
            'password' => trim($data['password'] ?? ''),
            'email'    => trim($data['email']    ?? ''),
        ];
    }

    private function saveDbConfig(array $data): void
    {
        $this->data['__settings__']['db_config'] = [
            'host'     => trim($data['host']     ?? ''),
            'user'     => trim($data['user']     ?? ''),
            'password' => trim($data['password'] ?? ''),
            'pma_url'  => trim($data['pma_url']  ?? ''),
        ];
    }

    private function saveAppearance(array $data): void
    {
        if (array_key_exists('action_btn', $data)) {
            $this->data['__settings__']['appearance']['action_btn'] = $data['action_btn'];
        }
        if (isset($data['presets']) && is_array($data['presets'])) {
            $this->data['__settings__']['appearance']['presets'] = $data['presets'];
        }
    }

    // ── Notes ─────────────────────────────────────────────────────────────

    public function getNote(string $site): string
    {
        return $this->data()['__notes__'][$site] ?? '';
    }

    public function saveNote(string $site, string $text): void
    {
        $this->data();
        $this->data['__notes__'] ??= [];
        if ($text === '') unset($this->data['__notes__'][$site]);
        else $this->data['__notes__'][$site] = $text;
        $this->write();
    }

    public function getNotes(): array
    {
        return $this->data()['__notes__'] ?? [];
    }

    public function getArchived(): array
    {
        return array_values($this->data()['__archived__'] ?? []);
    }

    public function archiveSite(string $name): void
    {
        $this->data();
        $list = $this->data['__archived__'] ?? [];
        if (!in_array($name, $list, true)) $list[] = $name;
        $this->data['__archived__'] = array_values($list);
        $this->write();
    }

    public function unarchiveSite(string $name): void
    {
        $this->data();
        $this->data['__archived__'] = array_values(
            array_filter($this->data['__archived__'] ?? [], fn($n) => $n !== $name)
        );
        $this->write();
    }
}
