<footer class="text-center p-3 bg-light mt-4">
    &copy; <?php echo date('Y'); ?> - Desenvolvido por <a href="https://www.upgyn.com.br" target="_blank" style="text-decoration: none; color: inherit;">UpGyn</a> | Todos os Direitos Reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    const btnIncrease = document.getElementById('font-increase');
    const btnReset = document.getElementById('font-reset');
    const btnDecrease = document.getElementById('font-decrease');
    const btnContrast = document.getElementById('contrast-toggle');

    let currentFontSize = parseInt(localStorage.getItem('adminFontSize') || 16);
    let highContrast = localStorage.getItem('adminHighContrast') === 'true';

    function applySettings() {
        body.style.fontSize = currentFontSize + 'px';
        if (highContrast) { body.classList.add('high-contrast'); } 
        else { body.classList.remove('high-contrast'); }
    }

    if(btnIncrease) {
        btnIncrease.addEventListener('click', function() {
            if (currentFontSize < 22) {
                currentFontSize += 2;
                localStorage.setItem('adminFontSize', currentFontSize);
                applySettings();
            }
        });
    }
    if(btnDecrease) {
        btnDecrease.addEventListener('click', function() {
            if (currentFontSize > 12) {
                currentFontSize -= 2;
                localStorage.setItem('adminFontSize', currentFontSize);
                applySettings();
            }
        });
    }
    if(btnReset) {
        btnReset.addEventListener('click', function() {
            currentFontSize = 16;
            localStorage.removeItem('adminFontSize');
            applySettings();
        });
    }
    if(btnContrast) {
        btnContrast.addEventListener('click', function() {
            highContrast = !highContrast;
            localStorage.setItem('adminHighContrast', highContrast);
            applySettings();
        });
    }
    
    applySettings();
});

// Script para inicializar os tooltips do Bootstrap em todas as páginas
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl) });
</script>