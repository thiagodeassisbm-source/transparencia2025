<?php
// /favoritar_publico.php
require_once 'conexao.php';
header('Content-Type: application/json');

$dados = json_decode(file_get_contents('php://input'), true);
$card_id = $dados['card_id'] ?? 0;
$ip_usuario = $_SERVER['REMOTE_ADDR']; // Pega o IP do visitante

if ($card_id && !empty($ip_usuario)) {
    try {
        // Verifica se o favorito já existe para este IP
        $stmt_check = $pdo->prepare("SELECT id FROM favoritos_usuarios WHERE id_card = ? AND ip_usuario = ?");
        $stmt_check->execute([$card_id, $ip_usuario]);
        
        if ($stmt_check->fetch()) {
            // Se existe, apaga (desfavoritar)
            $stmt_action = $pdo->prepare("DELETE FROM favoritos_usuarios WHERE id_card = ? AND ip_usuario = ?");
            $novo_status = 0; // Desfavoritado
        } else {
            // Se não existe, insere (favoritar)
            $stmt_action = $pdo->prepare("INSERT INTO favoritos_usuarios (id_card, ip_usuario) VALUES (?, ?)");
            $novo_status = 1; // Favoritado
        }
        $stmt_action->execute([$card_id, $ip_usuario]);

        echo json_encode(['success' => true, 'novo_status' => $novo_status]);
        exit();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}
echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);