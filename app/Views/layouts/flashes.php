<?php
// app/Views/layouts/flashes.php
if (!empty($_SESSION['flash'])):
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $prefixo = match ($f['tipo'] ?? 'info') {
        'sucesso' => '[OK]',
        'erro'    => '[ERRO]',
        'aviso'   => '[AVISO]',
        default   => '[INFO]',
    };
?>
<div>
    <strong><?= $prefixo ?></strong>
    <?= htmlspecialchars($f['mensagem'] ?? '') ?>
</div>
<?php endif; ?>