<?php
/**
 * Debug objetivo da clonagem (prefeitura demo): fingerprint de arquivos, BD, OPcache, logs.
 * Deve ficar em admin/clone_debug.php (mesmo nível que cadastrar_prefeitura.php) para deploy simples.
 * Uso: superadmin em debug_clone_ambiente.php; log detalhado com ?debug_clone=1 ou arquivo admin/.clone_debug
 */

if (function_exists('clone_debug_base_dir')) {
    return;
}

function clone_debug_base_dir(): string
{
    return __DIR__;
}

function clone_debug_tmp_dir(): string
{
    $d = clone_debug_base_dir() . '/tmp';
    if (!is_dir($d)) {
        @mkdir($d, 0755, true);
    }
    return $d;
}

function clone_debug_functions_demo_path(): string
{
    return clone_debug_base_dir() . '/functions_demo.php';
}

function clone_debug_file_fingerprint(string $absPath): array
{
    $out = [
        'path' => $absPath,
        'exists' => false,
        'readable' => false,
        'mtime' => null,
        'mtime_iso' => null,
        'size' => null,
        'md5' => null,
    ];
    if (!file_exists($absPath)) {
        return $out;
    }
    $out['exists'] = true;
    $out['readable'] = is_readable($absPath);
    if ($out['readable']) {
        $mt = @filemtime($absPath);
        $out['mtime'] = $mt;
        $out['mtime_iso'] = $mt ? date('c', $mt) : null;
        $out['size'] = @filesize($absPath);
        $out['md5'] = @md5_file($absPath);
    }
    return $out;
}

function clone_debug_verbose(): bool
{
    if (session_status() === PHP_SESSION_ACTIVE && (int) ($_SESSION['clone_debug_verbose'] ?? 0) === 1) {
        return true;
    }
    return is_file(clone_debug_base_dir() . '/.clone_debug');
}

function clone_debug_log(string $line): void
{
    if (!clone_debug_verbose()) {
        return;
    }
    $file = clone_debug_tmp_dir() . '/clone_debug.log';
    $ts = date('Y-m-d H:i:s');
    @file_put_contents($file, '[' . $ts . '] ' . $line . "\n", FILE_APPEND | LOCK_EX);
}

function clone_debug_opcache_hint(): array
{
    if (!function_exists('opcache_get_status')) {
        return ['available' => false, 'enabled' => null, 'note' => 'opcache_get_status não existe neste PHP'];
    }
    $st = @opcache_get_status(false);
    if (!is_array($st)) {
        return ['available' => true, 'enabled' => false, 'note' => 'opcache_get_status retornou vazio'];
    }
    return [
        'available' => true,
        'enabled' => !empty($st['opcache_enabled']),
        'full' => $st,
    ];
}

function clone_debug_snapshot_runtime(?PDO $pdo = null, bool $includeOpcacheScriptDetail = false): array
{
    $db = null;
    if ($pdo instanceof PDO) {
        try {
            $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
        } catch (Throwable $e) {
            $db = '(erro: ' . $e->getMessage() . ')';
        }
    }
    $oc = clone_debug_opcache_hint();
    $demoPath = clone_debug_functions_demo_path();
    $snap = [
        'written_at_iso' => date('c'),
        'database' => $db,
        'php_version' => PHP_VERSION,
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? '',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        'functions_demo' => clone_debug_file_fingerprint($demoPath),
        'cadastrar_prefeitura' => clone_debug_file_fingerprint(clone_debug_base_dir() . '/cadastrar_prefeitura.php'),
        'opcache' => [
            'available' => $oc['available'],
            'enabled' => $oc['enabled'] ?? null,
        ],
    ];
    if (($includeOpcacheScriptDetail || clone_debug_verbose()) && !empty($oc['full'])) {
        $scripts = $oc['full']['scripts'] ?? [];
        $hit = [];
        foreach ($scripts as $path => $_meta) {
            if (stripos((string) $path, 'functions_demo.php') !== false) {
                $hit[$path] = $_meta;
            }
        }
        $snap['opcache_scripts_functions_demo'] = $hit ?: '(nenhum script functions_demo.php listado no buffer OPcache)';
    }
    return $snap;
}

function clone_debug_write_last_result(?PDO $pdo, array $payload): void
{
    $path = clone_debug_tmp_dir() . '/clone_last_result.json';
    $payload['snapshot'] = clone_debug_snapshot_runtime($pdo, true);
    @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function clone_debug_html_banner(): string
{
    $f = clone_debug_file_fingerprint(clone_debug_functions_demo_path());
    if (!$f['exists']) {
        return '<div class="alert alert-danger small">clone_debug: functions_demo.php não encontrado em ' . htmlspecialchars($f['path']) . '</div>';
    }
    $md5 = htmlspecialchars((string) $f['md5']);
    $mt = htmlspecialchars((string) ($f['mtime_iso'] ?? ''));
    $p = htmlspecialchars($f['path']);
    return '<div class="alert alert-warning border-warning small font-monospace mb-3"><strong>Debug clonagem ativo</strong><br>'
        . 'Arquivo em uso: ' . $p . '<br>MD5: ' . $md5 . ' · mtime: ' . $mt
        . '<br><a href="debug_clone_ambiente.php" class="alert-link">Abrir diagnóstico completo</a></div>';
}
