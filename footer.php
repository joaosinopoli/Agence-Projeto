</main>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Inicializa todos os seletores com a classe 'select2-multiple'
        $(document).ready(function() {
            $('.select2-multiple').select2({
                placeholder: 'Selecione uma ou mais opções',
                allowClear: true
            });
        });
    </script>
</body>
</html>