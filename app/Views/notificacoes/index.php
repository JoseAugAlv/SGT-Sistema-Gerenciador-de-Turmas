<?php
$cssPagina = 'notificacoes.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

/**
 * Ícone e rótulo por tipo de notificação.
 */
function notifIcone(string $tipo): string {
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

function notifLabel(string $tipo): string {
    return match ($tipo) {
        'alerta'    => 'Alerta',
        'prazo'     => 'Prazo',
        'avaliacao' => 'Avaliação',
        'papel'     => 'Papel',
        'ata'       => 'Ata',
        'material'  => 'Material',
        'lgpd'      => 'LGPD',
        default     => 'Sistema',
    };
}

function notifClasse(string $tipo): string {
    $validos = ['alerta','prazo','avaliacao','papel','ata','material','lgpd'];
    return in_array($tipo, $validos, true) ? $tipo : 'padrao';
}

// Conta não lidas na lista atual
$totalLista     = count($notificacoes);
$totalNaoLidas  = $naoLidas ?? 0;
?>

<div id="notif-area" data-atualizar="30">
<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Notificações
        </div>
        <h1>Suas notificações</h1>
        <p class="hero-subtitle">
            <?php if ($totalNaoLidas > 0): ?>
                Você tem <strong><?= $totalNaoLidas ?></strong>
                notificação(ões) não lida(s).
            <?php else: ?>
                Tudo em dia — nenhuma notificação pendente.
            <?php endif; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/notificacoes/preferencias">
            <i class="fas fa-sliders"></i> Preferências
        </a>
    </div>
</section>

<!-- ============ TOOLBAR ============ -->
<div class="notif-toolbar">
    <div class="filtros">
        <a class="button small <?= $filtro === null || $filtro === '' ? 'button-primary' : 'button-ghost' ?>"
           href="<?= $basePath ?>/notificacoes">
            Todas
        </a>
        <a class="button small <?= $filtro === 'nao_lidas' ? 'button-primary' : 'button-ghost' ?>"
           href="<?= $basePath ?>/notificacoes?filtro=nao_lidas">
            Não lidas
            <?php if ($totalNaoLidas > 0): ?>
                <span style="opacity:.7;">(<?= $totalNaoLidas ?>)</span>
            <?php endif; ?>
        </a>
    </div>

    <div class="acoes">
        <span class="notif-contador <?= $totalNaoLidas === 0 ? 'vazio' : '' ?>">
            <i class="fas fa-circle-dot"></i>
            <strong><?= $totalNaoLidas ?></strong> não lida(s)
        </span>

        <?php if ($totalNaoLidas > 0): ?>
            <form method="POST" action="<?= $basePath ?>/notificacoes/marcar-todas-lidas" class="d-inline" data-parcial="#notif-area">
                <?= ViewHelper::csrfField() ?>
                <button type="submit" class="button small button-ghost">
                    <i class="fas fa-check-double"></i> Marcar todas como lidas
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- ============ LISTA ============ -->
<?php if (empty($notificacoes)): ?>
    <section class="notif-empty">
        <i class="icone fas fa-bell-slash"></i>
        <h2>Nenhuma notificação</h2>
        <p>
            <?php if ($filtro === 'nao_lidas'): ?>
                Você não tem notificações não lidas.
                <br><a href="<?= $basePath ?>/notificacoes">Ver todas as notificações</a>
            <?php else: ?>
                Quando houver prazos, avaliações, alertas ou solicitações de material
                envolvendo você, elas aparecerão aqui.
            <?php endif; ?>
        </p>
    </section>
<?php else: ?>
    <section class="notif-lista">
        <?php foreach ($notificacoes as $n): ?>
            <?php
            $tipo   = $n['tipo'] ?? 'padrao';
            $classe = notifClasse($tipo);
            $lida   = !empty($n['lida']);
            ?>
            <article class="notif-item <?= $lida ? 'lida' : 'nao-lida' ?>">
                <span class="notif-icon <?= $classe ?>">
                    <i class="<?= notifIcone($tipo) ?>"></i>
                </span>

                <div class="notif-body">
                    <div class="notif-topo">
                        <span class="notif-titulo"><?= h($n['titulo']) ?></span>
                        <span class="notif-tipo <?= $classe ?>"><?= notifLabel($tipo) ?></span>
                    </div>

                    <div class="notif-mensagem">
                        <?= nl2br(h($n['mensagem'])) ?>
                    </div>

                    <div class="notif-meta">
                        <span>
                            <i class="far fa-clock"></i>
                            <?= h($n['created_at']) ?>
                        </span>
                        <?php if (!$lida): ?>
                            <span>
                                <span class="nao-lida-dot"></span>
                                Não lida
                            </span>
                        <?php elseif (!empty($n['lida_em'])): ?>
                            <span>
                                <i class="fas fa-check"></i>
                                Lida em <?= h($n['lida_em']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="notif-acoes">
                    <?php if (!empty($n['link'])): ?>
                        <a class="button small button-primary"
                           href="<?= $basePath . h($n['link']) ?>">
                            <i class="fas fa-arrow-right"></i> Abrir
                        </a>
                    <?php endif; ?>

                    <?php if (!$lida): ?>
                        <form method="POST" action="<?= $basePath ?>/notificacoes/marcar-lida" class="d-inline" data-parcial="#notif-area">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                            <input type="hidden" name="voltar" value="<?= h($_SERVER['REQUEST_URI'] ?? '/notificacoes') ?>">
                            <button type="submit" class="button small button-ghost">
                                <i class="fas fa-check"></i> Marcar como lida
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>