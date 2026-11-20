</main>

<footer class="bg-body-tertiary border-top py-3 mt-auto">
    <div class="container small text-body-secondary d-flex flex-wrap justify-content-between gap-2">
        <span>Clínica veterinaria Carlitos &amp; Snoopy · Práctica UD1 PHP · 2º DAM</span>
        <span>Base de datos: <?= DB_DRIVER === 'pgsql' ? 'PostgreSQL (Supabase)' : 'MySQL' ?></span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="js/app.js"></script>
</body>
</html>
