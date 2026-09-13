</main>

<footer class="footer mt-auto py-3 bg-dark text-white-50 text-center small">
    <div class="container">
        &copy; <?= date('Y'); ?> <?= e(APP_NAME); ?> &mdash; <?= e(APP_TAGLINE); ?>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= \requestBase() ?>assets/js/main.js"></script>
</body>
</html>