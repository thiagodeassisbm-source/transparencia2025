<?php
/**
 * Correção do Menu Lateral (Sair do Painel) e Dropdown Perfil
 */

header('Content-Type: text/plain; charset=utf-8');
echo "Iniciando correção visual e de botões...\n\n";

// 1. Correção do admin_header.php (Adicionando btn 'Sair do Painel')
$header_file = __DIR__ . '/admin/admin_header.php';
$content_h = file_get_contents($header_file);

if (strpos($content_h, 'Sair do Painel') === false) {
    $find_h = "<?php endif; ?>\n                </ul>\n            <?php endif; ?>";
    $replace_h = "<?php endif; ?>\n                </ul>\n\n                <ul class=\"mt-4\">\n                    <li>\n                        <a href=\"logout.php\" class=\"nav-link text-danger fw-bold\">\n                            <i class=\"bi bi-box-arrow-left\"></i> Sair do Painel\n                        </a>\n                    </li>\n                </ul>\n            <?php endif; ?>";
    
    $content_h = str_replace($find_h, $replace_h, $content_h);
    if (file_put_contents($header_file, $content_h)) {
        echo "✅ admin_header.php atualizado: Botão Sair adicionado ao menu lateral.\n";
    }
} else {
    echo "✅ Botão 'Sair do Painel' já existe em admin_header.php.\n";
}

// 2. Correção do sic_inbox.php (Removendo JS duplo que quebra o dropdown)
$sic_file = __DIR__ . '/admin/sic_inbox.php';
$content_s = file_get_contents($sic_file);

if (strpos($content_s, '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>') !== false) {
    $content_s = str_replace('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>', '', $content_s);
    $content_s = str_replace("</body>\n</html>", "", $content_s);
    
    if (file_put_contents($sic_file, $content_s)) {
        echo "✅ sic_inbox.php atualizado: Bug do clique no perfil (Dropdown) corrigido.\n";
    }
} else {
    echo "✅ sic_inbox.php já estava corrigido.\n";
}

echo "\nPronto! Acesse o painel, atualize a página (F5) e teste o botão do perfil e o Sair do Painel no menu!";
