<?php
/**
 * Importação XML alinhada a gerenciar_tipos_xml (tabela tipos_xml).
 *
 * Prioridade:
 * 1) Tags cadastradas (tag_registro), na ordem definida no SELECT (ORDER BY id).
 * 2) Só se nenhuma tag cadastrada existir no arquivo: detecção automática (fallback).
 */

/**
 * Sanitiza nome de tag para XPath (apenas letras, números, _ e -).
 */
function xml_import_sanitize_tag(string $tag): string
{
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $tag);
}

/**
 * Primeiro elemento "linha" para montar o mapeamento De/Para.
 * 1) Cada tag cadastrada em tipos_xml, em ordem — primeiro match vence (não usa união XPath,
 *    que pegava o primeiro nó no documento e ignorava a prioridade do cadastro).
 * 2) Detecção automática só se nenhuma tag cadastrada existir no XML.
 * 3) Detecção: único bloco-filho com campos folha (arquivo com uma linha).
 */
function xml_import_obter_primeiro_registro(SimpleXMLElement $xml, array $tags_validas): ?SimpleXMLElement
{
    $tags_validas = array_values(array_unique(array_filter(array_map('xml_import_sanitize_tag', $tags_validas))));

    foreach ($tags_validas as $tag) {
        if ($tag === '') {
            continue;
        }
        $found = $xml->xpath('//' . $tag . '[1]');
        if (!empty($found[0])) {
            return $found[0];
        }
    }

    $best = null;
    $bestScore = 0;
    foreach ($xml->xpath('//*') as $node) {
        $counts = [];
        foreach ($node->children() as $c) {
            $n = $c->getName();
            $counts[$n] = ($counts[$n] ?? 0) + 1;
        }
        foreach ($counts as $name => $cnt) {
            if ($cnt < 2) {
                continue;
            }
            $safe = xml_import_sanitize_tag($name);
            if ($safe === '') {
                continue;
            }
            $first = $node->{$safe}[0] ?? null;
            if ($first !== null && count($first->children()) > 0 && $cnt > $bestScore) {
                $bestScore = $cnt;
                $best = $first;
            }
        }
    }
    if ($best !== null) {
        return $best;
    }

    foreach ($xml->xpath('//*') as $node) {
        $counts = [];
        foreach ($node->children() as $c) {
            $n = $c->getName();
            $counts[$n] = ($counts[$n] ?? 0) + 1;
        }
        foreach ($counts as $name => $cnt) {
            if ($cnt !== 1) {
                continue;
            }
            $safe = xml_import_sanitize_tag($name);
            if ($safe === '') {
                continue;
            }
            $first = $node->{$safe}[0] ?? null;
            if ($first !== null && count($first->children()) > 0) {
                return $first;
            }
        }
    }

    return null;
}

/**
 * Lista todos os nós de registro a importar.
 *
 * Se $tag_registro_fixo vier do passo 2 (sessão), usa só essa tag — deve ser a mesma
 * escolhida em obter_primeiro_registro (cadastrada ou fallback automático).
 *
 * Sem tag fixa: percorre tags cadastradas na mesma ordem e devolve o primeiro tipo que existir
 * no XML (evita misturar //Contrato com //Despesa num único fluxo).
 *
 * @param  string|null $tag_registro_fixo  ex.: "relatorio" ou "Contrato"
 * @return array<int, SimpleXMLElement>
 */
function xml_import_listar_registros(SimpleXMLElement $xml, array $tags_validas, ?string $tag_registro_fixo): array
{
    $tag_registro_fixo = $tag_registro_fixo !== null ? xml_import_sanitize_tag($tag_registro_fixo) : '';
    if ($tag_registro_fixo !== '') {
        $rows = $xml->xpath('//' . $tag_registro_fixo);

        return is_array($rows) ? $rows : [];
    }

    $tags_validas = array_values(array_unique(array_filter(array_map('xml_import_sanitize_tag', $tags_validas))));
    foreach ($tags_validas as $tag) {
        if ($tag === '') {
            continue;
        }
        $rows = $xml->xpath('//' . $tag);
        if (!empty($rows)) {
            return $rows;
        }
    }

    return [];
}
