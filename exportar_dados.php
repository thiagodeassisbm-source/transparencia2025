<?php
require_once 'conexao.php';

// --- Pega os parâmetros da URL ---
$slug_portal = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$formato = $_GET['formato'] ?? 'csv';
$filtros_ativos = $_GET['filtros'] ?? [];

if (!$slug_portal) { die("Seção não especificada."); }

// --- Busca de dados básicos da seção ---
$stmt_portal = $pdo->prepare("SELECT id, nome FROM portais WHERE slug = ?");
$stmt_portal->execute([$slug_portal]);
$secao = $stmt_portal->fetch();
if (!$secao) { die("Seção não encontrada."); }
$id_portal = $secao['id'];

// --- REPLICA A LÓGICA DE FILTRO DO portal.php (SEM PAGINAÇÃO) ---
$sql_base = "FROM registros r";
$sql_where = " WHERE r.id_portal = ?";
$params = [$id_portal];
$stmt_pesquisaveis = $pdo->prepare("SELECT id FROM campos_portal WHERE id_portal = ? AND pesquisavel = 1");
$stmt_pesquisaveis->execute([$id_portal]);
$campos_pesquisaveis_ids = $stmt_pesquisaveis->fetchAll(PDO::FETCH_COLUMN);

foreach ($campos_pesquisaveis_ids as $id_campo_filtro) {
    if (!empty($filtros_ativos[$id_campo_filtro])) {
        $valor_filtro = $filtros_ativos[$id_campo_filtro];
        $sql_where .= " AND EXISTS (SELECT 1 FROM valores_registros vr WHERE vr.id_registro = r.id AND vr.id_campo = ? AND vr.valor LIKE ?)";
        $params[] = $id_campo_filtro;
        $params[] = '%' . $valor_filtro . '%';
    }
}
$sql_registros = "SELECT id " . $sql_base . $sql_where . " ORDER BY r.id DESC";
$stmt_registros = $pdo->prepare($sql_registros);
$stmt_registros->execute($params);
$registros = $stmt_registros->fetchAll(PDO::FETCH_COLUMN);

// --- Monta a tabela de dados completa ---
$stmt_campos_todos = $pdo->prepare("SELECT id, nome_campo FROM campos_portal WHERE id_portal = ? ORDER BY ordem, id");
$stmt_campos_todos->execute([$id_portal]);
$campos_tabela = $stmt_campos_todos->fetchAll(PDO::FETCH_KEY_PAIR);
$id_campos_ordenados = array_keys($campos_tabela);
$headers = array_values($campos_tabela);
$dados_completos = [];
if (!empty($registros)) {
    $placeholders = implode(',', array_fill(0, count($registros), '?'));
    $stmt_valores = $pdo->prepare("SELECT id_registro, id_campo, valor FROM valores_registros WHERE id_registro IN ($placeholders)");
    $stmt_valores->execute($registros);
    $valores_por_registro = [];
    while($row = $stmt_valores->fetch()) { $valores_por_registro[$row['id_registro']][$row['id_campo']] = $row['valor']; }
    foreach ($registros as $id_registro) {
        $linha_formatada = [];
        $valores_do_registro_atual = $valores_por_registro[$id_registro] ?? [];
        foreach ($id_campos_ordenados as $id_campo) {
            $linha_formatada[ $campos_tabela[$id_campo] ] = $valores_do_registro_atual[$id_campo] ?? '';
        }
        $dados_completos[] = $linha_formatada;
    }
}

// --- GERA O ARQUIVO DE SAÍDA DE ACORDO COM O FORMATO ---
$filename = "dados_" . $slug_portal . "_" . date('Y-m-d') . "." . $formato;

switch ($formato) {
    case 'csv':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($dados_completos as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        break;

    case 'xls':
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo '<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />';
        echo "<table><tr>";
        foreach ($headers as $header) { echo "<th>" . htmlspecialchars($header) . "</th>"; }
        echo "</tr>";
        foreach ($dados_completos as $row) {
            echo "<tr>";
            foreach ($row as $cell) { echo "<td>" . htmlspecialchars($cell) . "</td>"; }
            echo "</tr>";
        }
        echo "</table>";
        break;

    case 'json':
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($dados_completos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
    
    // ==========================================================
    // === LÓGICA PARA TXT E XML ADICIONADA ABAIXO ===
    // ==========================================================

    case 'txt':
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        // Para TXT, usamos TAB como separador
        fputcsv($output, $headers, "\t"); 
        foreach ($dados_completos as $row) {
            fputcsv($output, $row, "\t");
        }
        fclose($output);
        break;

    case 'xml':
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $xml = new SimpleXMLElement('<registros/>');
        foreach($dados_completos as $row) {
            $item = $xml->addChild('item');
            foreach($row as $key => $value) {
                // Remove caracteres inválidos para nomes de tag XML
                $key_sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $key));
                $item->addChild($key_sanitized, htmlspecialchars($value));
            }
        }
        echo $xml->asXML();
        break;

    default:
        die("Formato não suportado.");
}

exit();