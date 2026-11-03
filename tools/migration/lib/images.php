<?php
/**
 * Copies referenced images from the WordPress file backup into public/wp-content/uploads/<same path>,
 * downloading from the live site only when the backup lacks the file.
 */

declare(strict_types=1);

final class ImageMigrator
{
    /** @var array<string, array<string, mixed>> keyed by root-relative URL path (as used on the old page) */
    public array $refs = [];
    public int $totalBytes = 0;
    public bool $stopped = false;

    public function __construct(
        private string $backupUploads,
        private string $publicRoot,
        private int $limitBytes,
        private bool $dryRun = false,
    ) {
    }

    public function addRef(string $src, string $role, string $alt, string $page, string $suggest = ''): void
    {
        $path = parse_url($src, PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, '/wp-content/uploads/') || parse_url($src, PHP_URL_HOST) !== null) {
            $this->refs['external:' . $src] ??= ['path' => $src, 'roles' => [], 'alts' => [], 'pages' => [], 'suggest' => '', 'external' => true];
            $this->refs['external:' . $src]['roles'][$role] = true;
            $this->refs['external:' . $src]['pages'][$page] = true;
            return;
        }
        $r = &$this->refs[$path];
        $r ??= ['path' => $path, 'roles' => [], 'alts' => [], 'pages' => [], 'suggest' => '', 'external' => false];
        $r['roles'][$role] = true;
        $r['pages'][$page] = true;
        if ($alt !== '') {
            $r['alts'][$alt] = true;
        } elseif ($suggest !== '' && $r['suggest'] === '') {
            $r['suggest'] = $suggest;
        }
        if ($alt === '') {
            $r['empty_alt_uses'] = ($r['empty_alt_uses'] ?? 0) + 1;
        }
    }

    /** Adds the unsized originals (foo.jpg / foo-scaled.jpg for foo-300x200.jpg) that exist in the backup. */
    public function addOriginals(): void
    {
        foreach (array_keys($this->refs) as $key) {
            $r = $this->refs[$key];
            if ($r['external'] || !preg_match('~^(.*)-\d+x\d+(\.[a-z0-9]+)$~i', $r['path'], $m)) {
                continue;
            }
            foreach ([$m[1] . $m[2], $m[1] . '-scaled' . $m[2]] as $orig) {
                if (isset($this->refs[$orig])) {
                    $this->refs[$orig]['roles']['original'] = true;
                    break;
                }
                if (is_file($this->backupFile($orig))) {
                    $this->refs[$orig] = ['path' => $orig, 'roles' => ['original' => true], 'alts' => [], 'pages' => $r['pages'],
                        'suggest' => '', 'external' => false, 'original_of' => $r['path']];
                    break;
                }
            }
        }
    }

    private function backupFile(string $urlPath): string
    {
        $rel = substr(rawurldecode($urlPath), strlen('/wp-content/uploads/'));
        return $this->backupUploads . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    }

    private function targetFile(string $urlPath): string
    {
        return $this->publicRoot . str_replace('/', DIRECTORY_SEPARATOR, rawurldecode($urlPath));
    }

    public function run(): void
    {
        ksort($this->refs);
        foreach ($this->refs as $key => &$r) {
            if ($r['external']) {
                $r['source'] = 'external';
                continue;
            }
            if ($this->stopped) {
                $r['source'] = 'not-copied(limit)';
                continue;
            }
            $dest = $this->targetFile($r['path']);
            $backup = $this->backupFile($r['path']);
            $bytes = null;
            if (is_file($backup)) {
                $r['source'] = 'backup';
                $bytes = filesize($backup);
                if (!$this->dryRun && (!is_file($dest) || filesize($dest) !== $bytes)) {
                    @mkdir(dirname($dest), 0777, true);
                    copy($backup, $dest);
                }
            } else {
                if (is_file($dest)) {
                    $r['source'] = 'downloaded';
                    $bytes = filesize($dest);
                } elseif ($this->dryRun) {
                    $r['source'] = 'would-download';
                } else {
                    $res = migration_fetch(SITE_ORIGIN . $r['path'], true, 0.5);
                    if ($res['status'] === 200 && $res['body'] !== '') {
                        @mkdir(dirname($dest), 0777, true);
                        file_put_contents($dest, $res['body']);
                        $r['source'] = 'downloaded';
                        $bytes = strlen($res['body']);
                    } else {
                        $r['source'] = 'missing';
                        $r['note'] = 'HTTP ' . $res['status'];
                    }
                }
            }
            $r['bytes'] = $bytes;
            $file = is_file($dest) ? $dest : (is_file($backup) ? $backup : null);
            if ($file) {
                $info = @getimagesize($file);
                $r['width'] = $info[0] ?? null;
                $r['height'] = $info[1] ?? null;
            }
            $this->totalBytes += (int) $bytes;
            if ($this->totalBytes > $this->limitBytes) {
                $this->stopped = true;
                fwrite(STDERR, sprintf("Image copy limit reached (%.1f MB) — stopping.\n", $this->totalBytes / 1048576));
            }
        }
    }

    public function dims(string $src): ?array
    {
        $path = parse_url($src, PHP_URL_PATH);
        $r = $this->refs[$path] ?? null;
        return ($r && !empty($r['width'])) ? [$r['width'], $r['height']] : null;
    }

    public function writeCsv(string $file): void
    {
        $fh = fopen($file, 'w');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['path', 'path_decoded', 'source', 'bytes', 'width', 'height', 'roles', 'alt_from_content', 'alt_suggested', 'pages', 'note']);
        foreach ($this->refs as $r) {
            $needsAlt = ($r['alts'] === []) && !isset($r['roles']['original']) || !empty($r['empty_alt_uses']);
            fputcsv($fh, [
                $r['path'],
                $r['external'] ? '' : rawurldecode($r['path']),
                $r['source'] ?? '',
                $r['bytes'] ?? '',
                $r['width'] ?? '',
                $r['height'] ?? '',
                implode('|', array_keys($r['roles'])),
                implode(' | ', array_keys($r['alts'])),
                $needsAlt ? $r['suggest'] : '',
                implode(' ', array_slice(array_keys($r['pages']), 0, 5)) . (count($r['pages']) > 5 ? ' …+' . (count($r['pages']) - 5) : ''),
                ($r['note'] ?? '') . (isset($r['original_of']) ? 'original of ' . rawurldecode($r['original_of']) : ''),
            ]);
        }
        fclose($fh);
    }
}
