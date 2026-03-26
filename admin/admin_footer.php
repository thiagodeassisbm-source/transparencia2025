        </main>
        
        <footer class="admin-footer text-center p-3 bg-white border-top mt-auto">
            <div class="container-fluid">
                <span class="text-muted small">&copy; <?php echo date('Y'); ?> - Desenvolvido por <a href="https://www.upgyn.com.br" target="_blank" class="fw-bold text-decoration-none" style="color: var(--primary-color);">UpGyn</a> | Todos os Direitos Reservados.</span>
            </div>
        </footer>
    </div> <!-- .admin-main -->
</div> <!-- .admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Inicializar tooltips e popovers se necessário
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

</body>
</html>
