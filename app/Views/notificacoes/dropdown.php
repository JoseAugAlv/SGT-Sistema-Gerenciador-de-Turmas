<?php
// app/Views/notificacoes/dropdown.php
// Renderizado dentro do nav (AJAX). Não deve incluir header/footer.

if (empty($ultimas)): ?>
    <div class="notif-dropdown-vazio">
        <i class="fas fa-bell-slash"></i>
        Nenhuma notificação
    </div>
<?php return; endif; ?>

<?php
function dropdownIcone(string $tipo): string {
    return match ($tipo) {
        'alerta'    => 'fas fa-bullhorn',
        'prazo'     => 'fas fa-clock',
        'avaliacao' => 'fas fa-star',
        'papel'     => 'fas fa-user-tag',
        'ata'       => 'fas fa-book',
        'material'  => 'fas fa-boxes',
        'lgpd'      => 'fas fa-shield-halved',
        default     => 'fas fa-bell',
    };
}
function dropdownClasse(string $tipo): string {
    $v = ['alerta','prazo','avaliacao','papel','ata','material','lgpd'];
    return in_array($tipo, $v, true) ? $tipo : 'padrao';
}
?>

<div class="notif-dropdown-header">
    <strong>Notificações recentes</strong>
    <a class="ver-todas" href="<?= $basePath ?>/notificacoes">Ver todas</a>
</div>

<ul class="notif-dropdown-lista">
    <?php foreach ($ultimas as $n): ?>
        <?php
        $tipo   = $n['tipo'] ?? 'padrao';
        $classe = dropdownClasse($tipo);
        $lida   = !empty($n['lida']);
        $link   = !empty($n['link']) ? $basePath . $n['link'] : $basePath . '/notificacoes';
        ?>
        <li>
            <a class="notif-dropdown-item <?= $lida ? '' : 'nao-lida' ?>" href="<?= h($link) ?>">
                <span class="notif-dropdown-icon <?= $classe ?>">
                    <i class="<?= dropdownIcone($tipo) ?>"></i>
                </span>

                <div class="notif-dropdown-info">
                    <strong><?= h($n['titulo']) ?></strong>
                    <span><?= h(mb_substr($n['mensagem'], 0, 90)) ?><?= mb_strlen($n['mensagem']) > 90 ? '…' : '' ?></span>
                    <small><?= h($n['created_at']) ?></small>
                </div>

                <?php if (!$lida): ?>
                    <span class="notif-dropdown-dot"></span>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="notif-dropdown-footer">
    <a href="<?= $basePath ?>/notificacoes/preferencias">
        <i class="fas fa-sliders"></i> Preferências de notificação
    </a>
</div>