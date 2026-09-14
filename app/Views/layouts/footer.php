<?php
// app/Views/layouts/footer.php
$basePath = App::getBasePath();
$appName  = App::getName();
?>
<hr>
<footer>
    <small>
        &copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?>
        — <a href="<?= $basePath ?>/sobre">Sobre</a>
        — <a href="<?= $basePath ?>/termos">Termos</a>
    </small>
</footer>
</body>
</html>