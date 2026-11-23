<?php
$cssPagina = 'projetos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<section class="hero-section">
    <div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle projeto-meta">
            <a class="projeto-turma" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
                <span class="mini-sigla num" style="<?= h(ViewHelper::estiloTurma($turma)) ?>"><?= h(ViewHelper::siglaTurma($turma['nome'])) ?></span>
                <?= h($turma['nome']) ?>
            </a>
            <span><?= $projeto['modo_avaliacao'] === 'etapa' ? 'Avaliação por etapa' : 'Avaliação geral' ?></span>
            <span>Prazo <?= !empty($projeto['prazo']) ? h(date('d/m/Y', strtotime($projeto['prazo']))) : '—' ?></span>
            <?php if ($projeto['encerrado']): ?>
                <span class="badge badge-red"><i class="fas fa-lock"></i> Encerrado</span>
            <?php else: ?>
                <span class="badge badge-green"><i class="fas fa-play"></i> Em andamento</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/portfolio.pdf" target="_blank" rel="noopener">
            <i class="fas fa-book-bookmark"></i> Portfólio (PDF)
        </a>
        <?php if ($isMaster): ?>
            <a class="button button-secondary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/editar">
                <i class="fas fa-pen"></i> Editar
            </a>
        <?php endif; ?>
        <?php if ($podeEditar && !$projeto['encerrado']): ?>
            <a class="button btn-outline-danger" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/encerrar">
                <i class="fas fa-lock"></i> Encerrar projeto
            </a>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($projeto['descricao'])): ?>
    <section class="panel mb-16">
        <p class="mb-0"><?= nl2br(h($projeto['descricao'])) ?></p>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/_nav.php'; ?>

<?php $cfg = $cfgNotas; require __DIR__ . '/_como_funciona.php'; ?>

<?php if ($podeEditar && !$projeto['encerrado'] && ($totalCriterios === 0 || abs($somaPesos - 10) >= 0.01)): ?>
    <div class="notice notice-warning mt-0">
        <span><i class="fas fa-list-check"></i></span>
        <div>
            <?php if ($totalCriterios === 0): ?>
                <strong>Este projeto ainda não tem critérios.</strong>
                <p>Sem critérios, a turma não pode ser avaliada. Crie os critérios com pesos que somem 10.
                <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios/criar">Criar critério</a></p>
            <?php else: ?>
                <strong>Os pesos dos critérios somam <?= number_format($somaPesos, 2, ',', '.') ?> de 10.</strong>
                <p>A média é ponderada, então ela continua correta. Mas com soma 10 cada peso mostra direto quanto o critério vale na nota.
                <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios">Ver critérios</a></p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($projeto['encerrado']): ?>
    <div class="notice notice-blue mt-0">
        <span>i</span>
        <div>
            <strong>Projeto encerrado</strong>
            <p>
                Encerrado em <?= h($projeto['encerrado_em']) ?><?= !empty($projeto['encerrado_por_nome']) ? ' por ' . h($projeto['encerrado_por_nome']) : '' ?>.
                As edições estão bloqueadas.
            </p>
        </div>
    </div>
<?php endif; ?>

<!-- ============ ETAPAS ============ -->
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Etapas (<?= count($etapas) ?>)</h2>
            <p>
                <?php if ($projeto['modo_avaliacao'] === 'etapa'): ?>
                    Critérios podem ser vinculados a estas etapas.
                <?php else: ?>
                    Etapas servem apenas para controle de progresso.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if (empty($etapas)): ?>
        <p class="text-muted">Nenhuma etapa cadastrada.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Ordem</th>
                    <th>Nome</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Critérios</th>
                    <?php if ($podeEditar && !$projeto['encerrado']): ?>
                        <th>Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($etapas as $e): ?>
                    <tr>
                        <td><?= (int) $e['ordem'] ?></td>
                        <td>
                            <strong><?= h($e['nome']) ?></strong>
                            <?php if (!empty($e['descricao'])): ?>
                                <br><small class="text-muted"><?= h($e['descricao']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($e['data_inicio']) ? h(date('d/m/Y', strtotime($e['data_inicio']))) : '—' ?></td>
                        <td><?= !empty($e['data_fim']) ? h(date('d/m/Y', strtotime($e['data_fim']))) : '—' ?></td>
                        <td>
                            <?php if ((int) $e['total_criterios'] > 0): ?>
                                <span class="badge badge-gray"><?= (int) $e['total_criterios'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($podeEditar && !$projeto['encerrado']): ?>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" action="<?= $basePath ?>/etapas/<?= (int) $e['id'] ?>/mover" class="d-inline">
                                        <?= ViewHelper::csrfField() ?>
                                        <input type="hidden" name="direcao" value="subir">
                                        <button type="submit" class="button small button-ghost" title="Subir">
                                            <i class="fas fa-arrow-up"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= $basePath ?>/etapas/<?= (int) $e['id'] ?>/mover" class="d-inline">
                                        <?= ViewHelper::csrfField() ?>
                                        <input type="hidden" name="direcao" value="descer">
                                        <button type="submit" class="button small button-ghost" title="Descer">
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                    </form>
                                    <a class="button small button-ghost" href="<?= $basePath ?>/etapas/<?= (int) $e['id'] ?>/editar" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form method="POST" action="<?= $basePath ?>/etapas/<?= (int) $e['id'] ?>/excluir" class="d-inline">
                                        <?= ViewHelper::csrfField() ?>
                                        <button type="submit" class="button small btn-outline-danger" title="Excluir"
                                                onclick="return confirm('Excluir etapa? Critérios vinculados perdem o vínculo.')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($podeEditar && !$projeto['encerrado']): ?>
        <fieldset class="mt-20">
            <legend>Adicionar etapa</legend>

            <form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/etapas/criar">
                <?= ViewHelper::csrfField() ?>

                <div class="field-row">
                    <div>
                        <label class="field-label">Nome *</label>
                        <input type="text" name="nome" class="field" maxlength="150" required>
                    </div>
                    <div>
                        <label class="field-label">Descrição</label>
                        <input type="text" name="descricao" class="field" maxlength="500">
                    </div>
                </div>

                <div class="field-row">
                    <div>
                        <label class="field-label">Data início</label>
                        <input type="date" name="data_inicio" class="field">
                    </div>
                    <div>
                        <label class="field-label">Data fim</label>
                        <input type="date" name="data_fim" class="field">
                    </div>
                </div>

                <p class="mt-16">
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-plus"></i> Adicionar etapa
                    </button>
                </p>
            </form>
        </fieldset>
    <?php endif; ?>
</section>

<p class="mt-20">
    <a class="button button-ghost" href="<?= $basePath ?>/projetos?turma_id=<?= (int) $turma['id'] ?>">
        <i class="fas fa-arrow-left"></i> Voltar aos projetos
    </a>
</p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>