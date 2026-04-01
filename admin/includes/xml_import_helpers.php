<?php
/**
 * Funções para importação XML quando a estrutura não bate com tipos_xml cadastrados.
 * Ex.: xml/relatorio-receitas.xml usa <relatorio> repetido, não <Receita>.
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
 * 1) Tags cadastradas em tipos_xml (//Tag[1])
 * 2) Detecção automática: ancestral com 2+ filhos homônimos (lista de registros)
 * 3) Detecção: único bloco-filho com campos folha (arquivo com uma linha)
 */
function xml_import_obter_primeiro_registro(SimpleXMLElement $xml, array $tags_validas): ?SimpleXMLElement
{
    $tags_validas = array_filter(array_map('xml_import_sanitize_tag', $tags_validas));

    if (!empty($tags_validas)) {
        $parts = [];
        foreach ($tags_validas as $tag) {
            if ($tag !== '') {
                $parts[] = '//' . $tag . '[1]';
            }
        }
        if ($parts !== []) {
            $found = $xml->xpath(implode(' | ', $parts));
            if (!empty($found[0])) {
                return $found[0];
            }
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
 * Lista todos os nós de registro a importar (mesma tag detectada no passo 2 ou tipos_xml).
 *
 * @param  string|null $tag_registro_fixo  ex.: "relatorio" vindo da sessão após detecção
 * @return array<int, SimpleXMLElement>
 */
function xml_import_listar_registros(SimpleXMLElement $xml, array $tags_validas, ?string $tag_registro_fixo): array
{
    $tag_registro_fixo = $tag_registro_fixo !== null ? xml_import_sanitize_tag($tag_registro_fixo) : '';
    if ($tag_registro_fixo !== '') {
        $rows = $xml->xpath('//' . $tag_registro_fixo);

        return is_array($rows) ? $rows : [];
    }

    $tags_validas = array_filter(array_map('xml_import_sanitize_tag', $tags_validas));
    if ($tags_validas === []) {
        return [];
    }
    $xpath_query = '//' . implode(' | //', $tags_validas);
    $rows = $xml->xpath($xpath_query);

    return is_array($rows) ? $rows : [];
}
