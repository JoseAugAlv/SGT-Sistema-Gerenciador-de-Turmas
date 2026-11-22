<?php
// app/Views/layouts/flashes.php
// Mensagem de retorno após uma ação (sucesso, erro, aviso, info).
// Incluído pelo nav.php e pelo auth_header.php; as views que também o incluem
// usam require_once, então a mensagem aparece uma vez só.
if (!empty($_SESSION['flash'])):
    $flashDados = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $flashTipo = in_array($flashDados['tipo'] ?? '', ['sucesso', 'erro', 'aviso'], true) ? $flashDados['tipo'] : 'info';
    $flashCfg = [
        'sucesso' => ['fas fa-circle-check',         'Pronto'],
        'erro'    => ['fas fa-circle-exclamation',   'Não foi possível concluir'],
        'aviso'   => ['fas fa-triangle-exclamation', 'Atenção'],
        'info'    => ['fas fa-circle-info',          'Informação'],
    ][$flashTipo];
?>
<div class="flash flash-<?= $flashTipo ?>" role="<?= $flashTipo === 'erro' ? 'alert' : 'status' ?>" data-flash>
    <i class="<?= $flashCfg[0] ?>" aria-hidden="true"></i>
    <div class="flash-texto">
        <strong><?= $flashCfg[1] ?></strong>
        <span><?= htmlspecialchars($flashDados['mensagem'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <button type="button" class="flash-fechar" aria-label="Fechar mensagem"
            onclick="this.closest('[data-flash]').remove()">
        <i class="fas fa-xmark" aria-hidden="true"></i>
    </button>
</div>
<?php if ($flashTipo === 'sucesso'): ?>
<script>
setTimeout(function () {
    var f = document.querySelector('.flash-sucesso[data-flash]');
    if (!f) return;
    f.classList.add('saindo');
    setTimeout(function () { f.remove(); }, 300);
}, 6000);
</script>
<?php endif; ?>
<?php endif; ?>
