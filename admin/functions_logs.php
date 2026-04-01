<?php
/**
 * Normaliza IP para armazenamento em log (::ffff:x.x.x.x → x.x.x.x).
 */
function normalizar_ip_para_log(string $ip): string {
    $ip = trim($ip);
    if ($ip === '') {
        return '';
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $ip;
    }
    if (stripos($ip, '::ffff:') === 0) {
        $v4 = substr($ip, strlen('::ffff:'));
        if (filter_var($v4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $v4;
        }
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return $ip;
    }
    return '';
}

/**
 * IP do cliente para auditoria: prioriza cabeçalhos de CDN/proxy quando presentes,
 * depois REMOTE_ADDR. Em ambientes sem proxy, cabeçalhos como X-Forwarded-For podem ser omitidos
 * ou definidos pelo provedor; não confie em XFF se o acesso direto ao PHP for público sem proxy.
 */
function obter_ip_cliente_para_log(): string {
    $candidatos = [];
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $candidatos[] = (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_TRUE_CLIENT_IP'])) {
        $candidatos[] = (string) $_SERVER['HTTP_TRUE_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $candidatos[] = (string) $_SERVER['HTTP_X_REAL_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        foreach (explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']) as $p) {
            $candidatos[] = trim($p);
        }
    }
    $candidatos[] = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    foreach ($candidatos as $bruto) {
        $n = normalizar_ip_para_log($bruto);
        if ($n !== '') {
            return $n;
        }
    }
    return '';
}

function registrar_log($pdo, $acao, $tabela, $detalhes) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $usuario_id = $_SESSION['admin_user_id'] ?? 0;
    $usuario_nome = $_SESSION['admin_user_nome'] ?? 'Sistema/Visitante';
    $ip = obter_ip_cliente_para_log();
    $id_prefeitura = $_SESSION['id_prefeitura'] ?? null;

    try {
        $stmt = $pdo->prepare('INSERT INTO logs_sistema (usuario_id, usuario_nome, acao, tabela, detalhes, ip_endereco, id_prefeitura) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$usuario_id, $usuario_nome, $acao, $tabela, $detalhes, $ip, $id_prefeitura]);
    } catch (PDOException $e) {
        error_log('Erro ao registrar log: ' . $e->getMessage());
    }
}
