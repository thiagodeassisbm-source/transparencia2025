<footer class="p-3 mt-4 border-top bg-white">
    <div class="container-fluid <?php echo (isset($custom_container_class)) ? $custom_container_class : ''; ?>">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
            <div class="mb-2 mb-md-0">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($prefeitura_titulo ?? 'Portal da Transparência'); ?>. Todos os direitos reservados.
            </div>
            <div class="d-flex align-items-center">
                <span class="me-2">Desenvolvido por |</span>
                <a href="https://www.upgyn.com.br" target="_blank" title="UPGYN Tecnologia">
                    <img src="imagens/logo-up.png" alt="UPGYN" style="height: 35px; filter: grayscale(1); opacity: 0.7;" onmouseover="this.style.filter='none'; this.style.opacity='1'" onmouseout="this.style.filter='grayscale(1)'; this.style.opacity='0.7'">
                </a>
            </div>
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

    // Lógica de Favoritos (se existirem na página)
    const favoriteIcons = document.querySelectorAll('.favorite-icon');
    favoriteIcons.forEach(iconDiv => {
        iconDiv.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const cardId = this.dataset.cardId;
            const starIcon = this.querySelector('i');
            starIcon.classList.toggle('bi-star');
            starIcon.classList.toggle('bi-star-fill');
            starIcon.classList.toggle('text-warning');
            fetch('favoritar_publico.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ card_id: cardId })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    starIcon.classList.toggle('bi-star');
                    starIcon.classList.toggle('bi-star-fill');
                    starIcon.classList.toggle('text-warning');
                }
            }).catch(() => {});
        });
    });
});
</script>
