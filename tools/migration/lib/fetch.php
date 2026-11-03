<?php
/**
 * Polite HTTP fetcher with an on-disk cache (migration tooling only, CLI).
 * One request at a time, a delay between live requests, fixed User-Agent.
 */

declare(strict_types=1);

const MIGRATION_UA = 'OMC-Migration/1.0';

function migration_cache_dir(): string
{
    $dir = getenv('OMC_MIGRATION_CACHE') ?: sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omc-migration-cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

/**
 * @return array{status:int, body:string, error:string, from_cache:bool}
 */
function migration_fetch(string $url, bool $binary = false, float $delay = 1.0): array
{
    $cacheFile = migration_cache_dir() . DIRECTORY_SEPARATOR . sha1($url) . ($binary ? '.bin' : '.html');
    if (!$binary && is_file($cacheFile)) {
        return ['status' => 200, 'body' => (string) file_get_contents($cacheFile), 'error' => '', 'from_cache' => true];
    }

    static $last = 0.0;
    $wait = $delay - (microtime(true) - $last);
    if ($wait > 0) {
        usleep((int) ($wait * 1_000_000));
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => MIGRATION_UA,
        CURLOPT_ENCODING       => '',
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $last = microtime(true);

    if ($body === false) {
        return ['status' => 0, 'body' => '', 'error' => $err, 'from_cache' => false];
    }
    if ($status === 200 && !$binary) {
        file_put_contents($cacheFile, $body);
    }
    return ['status' => $status, 'body' => (string) $body, 'error' => $err, 'from_cache' => false];
}
