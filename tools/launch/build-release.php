<?php
/**
 * ชุดไฟล์สำหรับอัปขึ้น production (FTP / File Manager) จาก commit เดียว — ไฟล์ชุดเดียวกับที่ CI ส่งขึ้น staging
 *
 *   php tools/launch/build-release.php [<commit>=HEAD] [<out-dir>=../releases]
 *
 * ได้: mlmsoftware-<sha>.zip + mlmsoftware-<sha>.sha256 (checksum ทุกไฟล์ — ตรวจหลังอัปได้)
 *
 * ‼️ สร้างจาก git (`git archive`) ไม่ใช่จากโฟลเดอร์ทำงาน — ไฟล์ที่ยังไม่ commit / .env / backup ในเครื่องไม่มีทางหลุดเข้า zip
 * ‼️ ตัดออกตาม .github/workflows/deploy.yml (docs · tools · tests · project · .github · server.php · .env* · CLAUDE.md …)
 * ‼️ zip ไม่มี .env — สร้าง .env บนเซิร์ฟเวอร์เองตาม LAUNCH.md ข้อ 2
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root   = dirname(__DIR__, 2);
$commit = $argv[1] ?? 'HEAD';
$outDir = rtrim($argv[2] ?? dirname($root) . '/releases', '/\\');
$sha    = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' rev-parse --short=7 ' . escapeshellarg($commit . '^{commit}') . ' 2>&1'));

if (preg_match('/^[0-9a-f]{7}$/', $sha) !== 1) {
    fwrite(STDERR, "unknown commit: {$commit}\n");
    exit(2);
}
if (str_starts_with(realpath(dirname($outDir)) . '/', realpath($root) . '/') || str_starts_with($outDir, $root)) {
    fwrite(STDERR, "write the release outside the project folder\n");
    exit(2);
}
@mkdir($outDir, 0777, true);

$zipPath = $outDir . '/mlmsoftware-' . $sha . '.zip';
$tmp     = $zipPath . '.tmp';
exec('git -C ' . escapeshellarg($root) . ' archive --format=zip -o ' . escapeshellarg($tmp) . ' ' . escapeshellarg($sha), $o, $code);
if ($code !== 0) {
    fwrite(STDERR, "git archive failed\n");
    exit(1);
}

// same rules as deploy.yml `exclude:`
$excluded = static fn (string $p): bool => (bool) preg_match('#(^|/)\.git[^/]*(/|$)|(^|/)node_modules/|(^|/)old/#', $p)
    || (bool) preg_match('#^(\.github|\.claude|\.codex|docs|tools|tests|project)/#', $p)
    || in_array($p, ['server.php', '.env', '.env.example', 'maintenance.flag', 'AGENTS.md', 'CLAUDE.md'], true);

$zip = new ZipArchive();
$zip->open($tmp);
$removed = 0;
$manifest = [];
for ($i = $zip->numFiles - 1; $i >= 0; $i--) {
    $name = (string) $zip->getNameIndex($i);
    if ($excluded($name)) {
        $zip->deleteIndex($i);
        $removed++;
    } elseif (!str_ends_with($name, '/')) {
        $manifest[$name] = hash('sha256', (string) $zip->getFromIndex($i));
    }
}
ksort($manifest);
$zip->addFromString('RELEASE.txt', "commit {$sha}\nbuilt " . date('c') . "\nfiles " . count($manifest) . "\nsee project/LAUNCH.md in the repository\n");
$zip->close();
rename($tmp, $zipPath);

$lines = [];
foreach ($manifest as $name => $hash) {
    $lines[] = $hash . '  ' . $name;
}
file_put_contents($outDir . '/mlmsoftware-' . $sha . '.sha256', implode("\n", $lines) . "\n");

// guard: nothing secret or dev-only slipped in
$bad = array_filter(array_keys($manifest), static fn (string $n): bool => (bool) preg_match('#(^|/)\.env$|\.sql\.gz$|storage/backups/(?!\.gitkeep)|storage/logs/.+\.log$#', $n));
printf("%s\n  %d files · %.1f MB · %d excluded · commit %s\n", $zipPath, count($manifest), filesize($zipPath) / 1048576, $removed, $sha);
if ($bad !== []) {
    fwrite(STDERR, 'refusing: sensitive files in the release: ' . implode(', ', $bad) . "\n");
    unlink($zipPath);
    exit(1);
}
foreach (['public/index.php', 'public/.htaccess', '.htaccess', 'scripts/lib/Migrator.php', 'database/migrations/049_lead_notify_status.sql', 'app/Services/PreflightService.php'] as $must) {
    if (!isset($manifest[$must])) {
        fwrite(STDERR, "missing required file: {$must}\n");
        exit(1);
    }
}
echo "  required files present · no .env / dumps / logs inside\n";
