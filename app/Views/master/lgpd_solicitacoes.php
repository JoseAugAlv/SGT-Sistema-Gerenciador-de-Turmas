<?php
$tituloPagina = 'Solicitações LGPD — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$solicitacoes = $solicitacoes ?? [];

$badgeStatus = function (string $s): string {
    return match ($s) {
        'pendente'    => '<span class="status-badge pendente"><i class="fas fa-hourglass-half"></i> Pendente</span>',
        'processando' => '<span class="status-badge processando"><i class="fas fa-gear"></i> Processando</span>',
        'concluida'   => '<span class="status-badge concluida"><i class="fas fa-check"></i> Concluída</span>',
        'negada'      => '<span class="status-badge negada"><i class="fas fa-xmark"></i> Negada</span>',
        default       => '<span class="status-badge">' . h($s) . '</span>',
    };
};

$tipoLabel = fn(string $t) => match ($t) {
    'exportacao' => '<i class="fas fa-download"></i> Exportação',
    'exclusao'   => '<i class="fas fa-trash"></i> Exclusão',
    default      => h($t),
};
?>

<section class="master-section">
    <div class="master-content">

        <header class="master-hero">
            <div>
                <h1><i class="fas fa-shield-halved"></i> Solicitações LGPD</h1>
                <p class="master-subtitle">
                    <i class="fas fa-scale-balanced"></i>
                    Analise pedidos de exportação e exclusão de dados pessoais.
                </p>
            </div>
            <?php
            $pendentes = count(array_filter($solicitacoes, fn($s) => $s['status'] === 'pendente'));
            ?>
            <span class="badge-count" style="<?= $pendentes > 0 ? 'background: rgba(212,139,28,.16); color: var(--orange);' : '' ?>">
                <i class="fas fa-hourglass-half"></i> <?= $pendentes ?> pendente(s)
            </span>
        </header>

        <?php if (empty($solicitacoes)): ?>
            <div class="empty-state">
                <i class="fas fa-shield-halved"></i>
                <h3>Nenhuma solicitação registrada</h3>
                <p>Quando um usuário solicitar exportação ou exclusão de dados, ela aparecerá aqui.</p>
            </div>
        <?php else: ?>
            <div class="master-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Usuário</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Motivo / Processado em</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitacoes as $s): ?>
                            <tr>
                                <td><small><?= h($s['created_at']) ?></small></td>

                                <td>
                                    <strong><?= h($s['usuario_nome']) ?></strong>
                                    <small><?= h($s['usuario_email']) ?></small>
                                </td>

                                <td><?= $tipoLabel($s['tipo']) ?></td>

                                <td><?= $badgeStatus($s['status']) ?></td>

                                <td>
                                    <?php if (!empty($s['motivo_negacao'])): ?>
                                        <small style="color: var(--red);">
                                            <strong>Motivo:</strong> <?= h($s['motivo_negacao']) ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if (!empty($s['processado_em'])): ?>
                                        <small>
                                            <i class="fas fa-clock"></i>
                                            <?= h($s['processado_em']) ?>
                                            <?php if (!empty($s['processado_por_nome'])): ?>
                                                · <?= h($s['processado_por_nome']) ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php elseif (empty($s['motivo_negacao'])): ?>
                                        <small style="color: var(--muted);">—</span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align:right; white-space:nowrap;">
                                    <?php if ($s['status'] === 'pendente'): ?>
                                        <form method="POST"
                                              action="<?= $basePath ?>/master/lgpd/<?= (int) $s['id'] ?>/aprovar"
                                              style="display:inline;">
                                            <?= ViewHelper::csrfField() ?>
                                            <button type="submit" class="btn btn-sm btn-new"
                                                    onclick="return confirm('<?= $s['tipo'] === 'exclusao' ? 'Aprovar e EXECUTAR a exclusão? O usuário será anonimizado permanentemente.' : 'Aprovar esta solicitação?' ?>')">
                                                <i class="fas fa-check"></i> Aprovar
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="<?= $basePath ?>/master/lgpd/<?= (int) $s['id'] ?>/negar"
                                              style="display:inline-block; margin-left:6px;">
                                            <?= ViewHelper::csrfField() ?>
                                            <input type="text" name="motivo" required
                                                   placeholder="Motivo da recusa"
                                                   style="padding:6px 9px; border:1px solid var(--border); border-radius:7px; background:var(--surface); color:var(--text); font-size:12px; min-width:160px;">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-xmark"></i> Negar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--muted); font-size:12px;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="master-voltar">
            <a class="btn" href="<?= $basePath ?>/master"
               style="padding:9px 14px; border-radius:9px; border:1px solid var(--border); color:var(--muted); text-decoration:none; font-size:13px; font-weight:700;">
                <i class="fas fa-arrow-left"></i> Voltar ao painel
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>