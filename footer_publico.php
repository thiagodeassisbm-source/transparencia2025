<footer class="p-3 mt-4 border-top bg-white">
    <div class="container-fluid <?php echo (isset($custom_container_class)) ? $custom_container_class : ''; ?>">
        <div class="text-center small text-muted">
            &copy; <?php echo (function_exists('get_config_global') ? get_config_global($pdo, 'copyright_ano', date('Y')) : date('Y')); ?> - Desenvolvido por <a href="<?php echo (function_exists('get_config_global') ? get_config_global($pdo, 'copyright_dev_site', 'https://www.upgyn.com.br') : 'https://www.upgyn.com.br'); ?>" target="_blank" class="fw-bold text-decoration-none" style="color: #0d6efd;"><?php echo (function_exists('get_config_global') ? get_config_global($pdo, 'copyright_dev_nome', 'UpGyn') : 'UpGyn'); ?></a> | <?php echo (function_exists('get_config_global') ? get_config_global($pdo, 'copyright_texto', 'Todos os Direitos Reservados') : 'Todos os Direitos Reservados'); ?>.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const btnIncrease = document.getElementById('font-increase');
    const btnReset = document.getElementById('font-reset');
    const btnDecrease = document.getElementById('font-decrease');
    const btnContrast = document.getElementById('contrast-toggle');

    let currentFontSize = parseInt(localStorage.getItem('fontSize') || 16);
    let highContrast = localStorage.getItem('highContrast') === 'true';

    function applySettings() {
        body.style.fontSize = currentFontSize + 'px';
        if (highContrast) { body.classList.add('high-contrast'); } 
        else { body.classList.remove('high-contrast'); }
    }

    if(btnIncrease) { btnIncrease.addEventListener('click', function() { if (currentFontSize < 24) { currentFontSize += 2; localStorage.setItem('fontSize', currentFontSize); applySettings(); } }); }
    if(btnDecrease) { btnDecrease.addEventListener('click', function() { if (currentFontSize > 12) { currentFontSize -= 2; localStorage.setItem('fontSize', currentFontSize); applySettings(); } }); }
    if(btnReset) { btnReset.addEventListener('click', function() { currentFontSize = 16; localStorage.removeItem('fontSize'); applySettings(); }); }
    if(btnContrast) { btnContrast.addEventListener('click', function() { highContrast = !highContrast; localStorage.setItem('highContrast', highContrast); applySettings(); }); }
    
    applySettings();

    // --- NOVA LÓGICA DE FAVORITOS (LOCAL STORAGE) ---
    // Inicializa o array de IDs favoritados a partir do LocalStorage
    let favoritosLocal = JSON.parse(localStorage.getItem('favoritos_maquina')) || [];
    
    function atualizarBadge() {
        const badge = document.getElementById('badge-favoritos-count');
        if (badge) {
            badge.textContent = favoritosLocal.length;
        }
    }
    atualizarBadge();

    const favoriteIcons = document.querySelectorAll('.favorite-icon');
    
    // Na leitura inicial dos ícones, podemos garantir que os visuais batem com o local storage
    favoriteIcons.forEach(iconDiv => {
        const cardId = parseInt(iconDiv.dataset.cardId);
        const starIcon = iconDiv.querySelector('i');
        
        // Se estiver no local storage, forçamos o ícone aceso
        if (favoritosLocal.includes(cardId)) {
            starIcon.classList.remove('bi-star');
            starIcon.classList.add('bi-star-fill', 'text-warning');
        }

        iconDiv.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            
            const cid = parseInt(this.dataset.cardId);
            const isFav = favoritosLocal.includes(cid);
            
            // Toggle local
            if (isFav) {
                favoritosLocal = favoritosLocal.filter(id => id !== cid);
                starIcon.classList.remove('bi-star-fill', 'text-warning');
                starIcon.classList.add('bi-star');
            } else {
                favoritosLocal.push(cid);
                starIcon.classList.remove('bi-star');
                starIcon.classList.add('bi-star-fill', 'text-warning');
            }
            
            localStorage.setItem('favoritos_maquina', JSON.stringify(favoritosLocal));
            atualizarBadge();
            
            // Grava em Cookie também para o PHP ler (1 ano de validade)
            document.cookie = "maquina_favoritos=" + JSON.stringify(favoritosLocal) + "; path=/; max-age=31536000";

            // Envia para o backend para fins estatísticos
            fetch('<?php echo $base_url; ?>favoritar_publico.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ card_id: cid })
            }).catch(() => {});
        });
    });
});
</script>
