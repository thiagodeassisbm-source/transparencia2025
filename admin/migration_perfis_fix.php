<?php
require_once '../conexao.php';
session_start();

// Como o usuário pode não ser superadmin mas é o dono da prefeitura, 
// vamos permitir se ele estiver logado como admin da prefeitura.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Acesso negado. Faça login primeiro.");
}

try {
    // 1. Adiciona id_prefeitura se não existir
    $check = $pdo->query("SHOW COLUMNS FROM perfis LIKE 'id_prefeitura'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE perfis ADD COLUMN id_prefeitura INT DEFAULT NULL AFTER id");
        echo "Coluna id_prefeitura adicionada a perfis.<br>";
    }

    // 2. Tenta vincular perfis existentes à prefeitura do usuário logado se eles forem órfãos
    if (isset($_SESSION['id_prefeitura'])) {
        $pref_id = $_SESSION['id_prefeitura'];
        
        // Verifica se já existem perfis para esta prefeitura. 
        // Se não existirem, vamos "promover" os perfis globais atuais para serem desta prefeitura
        // para que o usuário não perca acesso.
        $count = $pdo->prepare("SELECT COUNT(*) FROM perfis WHERE id_prefeitura = ?");
        $count->execute([$pref_id]);
        if ($count->fetchColumn() == 0) {
            $pdo->prepare("UPDATE perfis SET id_prefeitura = ? WHERE id_prefeitura IS NULL")->execute([$pref_id]);
            echo "Perfis globais vinculados à prefeitura $pref_id.<br>";
        }
    }
    
    echo "Migração concluída com sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
