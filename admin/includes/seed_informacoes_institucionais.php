<?php
/**
 * Garante categoria "Informações Institucionais" e seções padrão (FAQ + Estrutura)
 * para a prefeitura, com cards na home e permissão do perfil informado.
 *
 * @return bool true se criou ou alterou algo (para recarregar permissões na sessão)
 */
function ensure_informacoes_institucionais(PDO $pdo, int $prefId, int $perfilId): bool
{
    if ($prefId <= 0) {
        return false;
    }

    $mudou = false;

    $nome_cat = 'Informações Institucionais';
    $stmt = $pdo->prepare('SELECT id FROM categorias WHERE id_prefeitura = ? AND nome = ?');
    $stmt->execute([$prefId, $nome_cat]);
    $id_cat = $stmt->fetchColumn();

    if (!$id_cat) {
        $mx = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM categorias WHERE id_prefeitura = ?');
        $mx->execute([$prefId]);
        $ord = (int) $mx->fetchColumn();
        $pdo->prepare('INSERT INTO categorias (nome, id_prefeitura, ordem) VALUES (?, ?, ?)')
            ->execute([$nome_cat, $prefId, $ord]);
        $id_cat = (int) $pdo->lastInsertId();
        $mudou = true;
    } else {
        $id_cat = (int) $id_cat;
    }

    $secoes = [
        ['nome' => 'Perguntas Frequentes', 'slug' => 'perguntas-frequentes'],
        ['nome' => 'Estrutura Organizacional', 'slug' => 'estrutura-organizacional'],
    ];

    foreach ($secoes as $sec) {
        $st = $pdo->prepare('SELECT id FROM portais WHERE id_prefeitura = ? AND slug = ?');
        $st->execute([$prefId, $sec['slug']]);
        $pid = $st->fetchColumn();

        if (!$pid) {
            $pdo->prepare(
                'INSERT INTO portais (nome, descricao, slug, id_categoria, id_prefeitura) VALUES (?, ?, ?, ?, ?)'
            )->execute([$sec['nome'], '', $sec['slug'], $id_cat, $prefId]);
            $pid = (int) $pdo->lastInsertId();
            $mudou = true;

            if ($perfilId > 0) {
                $check = $pdo->prepare('SELECT id FROM permissoes_perfil WHERE id_perfil = ? AND recurso = ?');
                $check->execute([$perfilId, 'form_' . $pid]);
                if (!$check->fetch()) {
                    $pdo->prepare(
                        'INSERT INTO permissoes_perfil (id_perfil, recurso, p_ver, p_lancar, p_editar, p_excluir) VALUES (?, ?, 1, 1, 1, 1)'
                    )->execute([$perfilId, 'form_' . $pid]);
                }
            }
        } else {
            $pid = (int) $pid;
        }

        $chk = $pdo->prepare('SELECT id FROM cards_informativos WHERE id_secao = ? LIMIT 1');
        $chk->execute([$pid]);
        if (!$chk->fetchColumn()) {
            $pdo->prepare(
                'INSERT INTO cards_informativos (id_categoria, id_secao, link_url, titulo, subtitulo, caminho_icone, tipo_icone, ordem, id_prefeitura)
                 VALUES (?, ?, NULL, ?, ?, ?, ?, 0, ?)'
            )->execute([
                $id_cat,
                $pid,
                $sec['nome'],
                $sec['nome'],
                'bi-building',
                'bootstrap',
                $prefId,
            ]);
            $mudou = true;
        }
    }

    return $mudou;
}
