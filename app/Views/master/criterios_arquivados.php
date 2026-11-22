<?php
$tituloPagina = 'Critérios Arquivados — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$arquivados = $arquivados ?? [];
?>

<section class="master-section">
    <div class="master-content">

        <header class="master-hero">
            <div>
                <h1><i class="fas fa-archive"></i> Critérios Arquivados</h1>
                <p class="master-subtitle">
                    <i class="fas fa-clock-rotate-left"></i>
                    Critérios excluídos com avaliações ficam disponíveis por <strong>30 dias</strong>
                    para restauração.
                </p>
            </div>
            <span class="badge-count">
                <i class="fas fa-box-archive"></i> <?= count($arquivados) ?> arquivado(s)
            </span>
        </header>

        <?php if (empty($arquivados)): ?>
            <div class="empty-state">
                <i class="fas fa-archive"></i>
                <h3>Nenhum critério arquivado</h3>
                <p>Quando um critério com avaliações for excluído, ele aparecerá aqui por 30 dias.</p>
            </div>
        <?php else: ?>
            <div class="master-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Critério</th>
                            <th>Projeto / Turma</th>
                            <th>Arquivado em</th>
                            <th>Expira em</th>
                            <th>Por</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($arquivados as $a): ?>
                            <?php
                            $expirado = !empty($a['expira_em']) && strtotime($a['expira_em']) < time();
                            $dados    = json_decode($a['dados_json'] ?? '{}', true) ?: [];
                            $nome     = $dados['criterio']['nome'] ?? '(sem nome)';
                            $individuais = count($dados['individuais'] ?? []);
                            $coletivas   = count($dados['coletivas'] ?? []);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= h($nome) ?></strong>
                                    <small>
                                        <?= $individuais ?> avaliação(ões) individual(is)
                                        · <?= $coletivas ?> coletiva(s)
                                    </small>
                                </td>
                                <td>
                                    <strong><?= h($a['projeto_nome']) ?></strong>
                                    <small><?= h($a['turma_nome']) ?></small>
                                </td>
                                <td><small><?= h($a['arquivado_em']) ?></small></td>
                                <td>
                                    <?php if ($expirado): ?>
                                        <span class="status-badge negada">
                                            <i class="fas fa-xmark"></i> Expirado
                                        </span>
                                        <small><?= h($a['expira_em']) ?></small>
                                    <?php else: ?>
                                        <span class="status-badge ativo">
                                            <i class="fas fa-clock"></i> <?= h($a['expira_em']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= h($a['arquivado_por_nome'] ?? '—') ?></small></td>
                                <td style="text-align:right;">
                                    <?php if (!$expirado): ?>
                                        <form method="POST"
                                              action="<?= $basePath ?>/master/criterios-arquivados/<?= (int) $a['id'] ?>/restaurar"
                                              style="display:inline;">
                                            <?= ViewHelper::csrfField() ?>
                                            <button type="submit" class="btn btn-sm btn-edit"
                                                    onclick="return confirm('Restaurar este critério e todas as suas avaliações?')">
                                                <i class="fas fa-rotate-left"></i> Restaurar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--muted); font-size:12px;">
                                            Janela de restauração expirada
                                        </span>
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