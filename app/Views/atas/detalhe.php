<?php
$cssPagina = ['projetos.css', 'atas.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
require_once __DIR__ . '/../../Models/Ata.php';

$statusBadge = match ($ata['status']) {
    'pendente'   => '<span class="ata-status pendente"><i class="fas fa-hourglass-half"></i> Pendente</span>',
    'preenchida' => '<span class="ata-status preenchida"><i class="fas fa-pen"></i> Preenchida</span>',
    'revisada'   => '<span class="ata-status revisada"><i class="fas fa-check-circle"></i> Revisada</span>',
    default      => h($ata['status']),
};

$presencaBadge = function (?string $p): string {
    return match ($p) {
        'sim'         => '<span class="badge badge-green"><i class="fas fa-check"></i> Presente</span>',
        'nao'         => '<span class="badge badge-red"><i class="fas fa-times"></i> Ausente</span>',
        'justificado' => '<span class="badge badge-yellow"><i class="fas fa-info-circle"></i> Justificado</span>',
        default       => '<span class="badge badge-gray">—</span>',
    };
};

$presencaClasse = function (?string $p): string {
    return match ($p) {
        'sim'         => 'presente',
        'nao'         => 'ausente',
        'justificado' => 'justificado',
        default       => '',
    };
};
?>

<?php
$etapaAtual = ['pendente' => 1, 'preenchida' => 2, 'revisada' => 3][$ata['status']] ?? 1;
$horario = '';
if (!empty($ata['horario_inicio']) || !empty($ata['horario_fim'])) {
    $horario = ' · ' . substr((string) ($ata['horario_inicio'] ?? '?'), 0, 5) . ' às ' . substr((string) ($ata['horario_fim'] ?? '?'), 0, 5);
}
?>
<section class="hero-section">
    <div>
        <h1><?= h($ata['titulo']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/projetos/<?= (int) $ata['projeto_id'] ?>"><?= h($ata['projeto_nome']) ?></a>
            · Grupo <a href="<?= $basePath ?>/grupos/<?= (int) $ata['grupo_id'] ?>"><?= h($ata['grupo_nome']) ?></a>
        </p>
    </div>
    <div class="hero-actions">
        <?php if ($podePreencher): ?>
            <a class="button button-primary" href="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/preencher">
                <i class="fas fa-pen"></i> Preencher ata
            </a>
        <?php endif; ?>
        <?php if ($podeValidar && $ata['status'] === 'preenchida'): ?>
            <a class="button button-primary" href="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/validar-form">
                <i class="fas fa-check"></i> Revisar e validar
            </a>
        <?php endif; ?>
        <?php if ($podeValidar && $ata['status'] === 'revisada' && empty($projeto['encerrado'])): ?>
            <?php require __DIR__ . '/_devolver.php'; ?>
        <?php endif; ?>
        <?php if ($podeExcluir): ?>
            <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/excluir" class="d-inline">
                <?= ViewHelper::csrfField() ?>
                <button type="submit" class="button btn-outline-danger"
                        onclick="return confirm('Excluir esta ata? As atividades e relatórios dela também serão apagados.')">
                    <i class="fas fa-trash"></i> Excluir
                </button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<!-- ============ ETAPAS ============ -->
<ol class="ata-etapas" aria-label="Situação da ata">
    <li class="<?= $etapaAtual > 1 ? 'feita' : 'atual' ?>">
        <strong>Criada</strong>
        <small><?= $etapaAtual === 1 ? 'aguardando o diretor preencher' : 'pelo representante' ?></small>
    </li>
    <li class="<?= $etapaAtual > 2 ? 'feita' : ($etapaAtual === 2 ? 'atual' : '') ?>">
        <strong>Preenchida</strong>
        <small><?= $etapaAtual === 2 ? 'aguardando validação' : ($etapaAtual > 2 ? 'pelo diretor' : 'atividades e presenças') ?></small>
    </li>
    <li class="<?= $etapaAtual === 3 ? 'atual' : '' ?>">
        <strong>Validada</strong>
        <small><?= $etapaAtual === 3 ? 'ata concluída' : 'pelo representante' ?></small>
    </li>
</ol>

<!-- ============ DADOS ============ -->
<dl class="ata-meta">
    <div><dt>Reunião</dt><dd><?= ViewHelper::data($ata['data_ata']) ?><?= h($horario) ?></dd></div>
    <div><dt>Preencher até</dt><dd><?= ViewHelper::data($ata['prazo_preenchimento'] ?? null) ?></dd></div>
    <div><dt>Diretor</dt><dd><?= h($ata['diretor_nome'] ?? '—') ?></dd></div>
    <div><dt>Representante</dt><dd><?= h($ata['representante_nome'] ?? '—') ?></dd></div>
    <div><dt>Criada em</dt><dd><?= ViewHelper::data($ata['created_at'] ?? null, true) ?></dd></div>
</dl>
<?php if (!empty($ata['descricao'])): ?>
    <p class="ata-descricao"><?= nl2br(h($ata['descricao'])) ?></p>
<?php endif; ?>

<!-- ============ PARTICIPANTES ============ -->
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Participantes (<?= count($participantes) ?>)</h2>
            <p>
                <?php if ($podePreencher): ?>
                    Ajuste a presença de cada membro. Justificativa é obrigatória para "Ausente" e "Justificado".
                <?php else: ?>
                    Quem estava presente na reunião.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if (empty($participantes)): ?>
        <p class="text-muted">Nenhum participante registrado.</p>
    <?php elseif ($podePreencher): ?>
        <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/participantes">
            <?= ViewHelper::csrfField() ?>

            <div class="ata-participantes">
                <?php foreach ($participantes as $p): ?>
                    <div class="ata-participante <?= $presencaClasse($p['presente']) ?>">
                        <span class="avatar avatar-blue">
                            <?= strtoupper(substr($p['usuario_nome'], 0, 1)) ?>
                        </span>
                        <div class="pinfo">
                            <strong><?= h($p['usuario_nome']) ?></strong>
                        </div>
                        <div class="presenca-controls">
                            <select name="presenca[<?= (int) $p['aluno_id'] ?>]" class="field">
                                <option value="sim"         <?= $p['presente'] === 'sim'         ? 'selected' : '' ?>>Presente</option>
                                <option value="nao"         <?= $p['presente'] === 'nao'         ? 'selected' : '' ?>>Ausente</option>
                                <option value="justificado" <?= $p['presente'] === 'justificado' ? 'selected' : '' ?>>Justificado</option>
                            </select>
                            <input type="text"
                                   class="field"
                                   name="justificativa[<?= (int) $p['aluno_id'] ?>]"
                                   value="<?= h($p['justificativa'] ?? '') ?>"
                                   maxlength="500"
                                   placeholder="Justificativa">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="mt-16">
                <button type="submit" class="button button-primary">
                    <i class="fas fa-check"></i> Salvar presenças
                </button>
            </p>
        </form>
    <?php else: ?>
        <div class="ata-participantes">
            <?php foreach ($participantes as $p): ?>
                <div class="ata-participante <?= $presencaClasse($p['presente']) ?>">
                    <span class="avatar avatar-blue">
                        <?= strtoupper(substr($p['usuario_nome'], 0, 1)) ?>
                    </span>
                    <div class="pinfo">
                        <strong><?= h($p['usuario_nome']) ?></strong>
                        <?php if (!empty($p['justificativa'])): ?>
                            <small>Justificativa: <?= h($p['justificativa']) ?></small>
                        <?php endif; ?>
                    </div>
                    <?= $presencaBadge($p['presente']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ============ ATIVIDADES ============ -->
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Atividades (<?= count($atividades) ?>)</h2>
            <p>O que foi feito na reunião.</p>
        </div>
    </div>

    <?php if (empty($atividades)): ?>
        <p class="text-muted">Nenhuma atividade registrada.</p>
    <?php else: ?>
        <div class="ata-atividades">
            <?php foreach ($atividades as $a): ?>
                <article class="ata-atividade">
                    <div class="ata-atividade-header">
                        <strong><?= h($a['nome']) ?></strong>
                        <?php if ($podePreencher): ?>
                            <form method="POST" action="<?= $basePath ?>/atividades/<?= (int) $a['id'] ?>/excluir" class="d-inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="button small btn-outline-danger"
                                        onclick="return confirm('Excluir atividade?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($a['descricao'])): ?>
                        <p class="ata-atividade-desc"><?= nl2br(h($a['descricao'])) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($a['participantes'])): ?>
                        <div class="ata-atividade-participantes">
                            <?php foreach ($a['participantes'] as $p): ?>
                                <span class="participante-tag">
                                    <i class="fas fa-user"></i>&nbsp;<?= h($p['usuario_nome']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($podePreencher): ?>
        <div class="ata-add-form">
            <h3><i class="fas fa-plus-circle"></i> Adicionar atividade</h3>

            <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/atividade">
                <?= ViewHelper::csrfField() ?>

                <label class="field-label">Nome *</label>
                <input type="text" name="nome" class="field" maxlength="200" required>

                <label class="field-label">Descrição</label>
                <textarea name="descricao" class="field" rows="2" maxlength="2000"></textarea>

                <?php if (!empty($alunosDoGrupo) || !empty($alunosOutros)): ?>
                    <label class="field-label">Participantes</label>

                    <?php if (!empty($alunosDoGrupo)): ?>
                        <p class="text-muted mb-8" style="font-size:11.5px;">
                            <i class="fas fa-users"></i> <strong>Alunos do grupo</strong>
                        </p>
                        <div class="ata-atividade-participantes mb-12">
                            <?php foreach ($alunosDoGrupo as $a): ?>
                                <label class="participante-tag grupo" style="cursor:pointer;">
                                    <input type="checkbox" name="participantes[]" value="<?= (int) $a['id'] ?>" style="margin-right:6px;">
                                    <?= h($a['nome']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($alunosOutros)): ?>
                        <p class="text-muted mb-8" style="font-size:11.5px;">
                            <i class="fas fa-user-friends"></i> <strong>Outros alunos da turma</strong>
                        </p>
                        <div class="ata-atividade-participantes">
                            <?php foreach ($alunosOutros as $a): ?>
                                <label class="participante-tag turma" style="cursor:pointer;">
                                    <input type="checkbox" name="participantes[]" value="<?= (int) $a['id'] ?>" style="margin-right:6px;">
                                    <?= h($a['nome']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <p class="mt-16">
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-plus"></i> Adicionar atividade
                    </button>
                </p>
            </form>
        </div>
    <?php endif; ?>
</section>

<!-- ============ RELATÓRIOS ============ -->
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Relatórios internos (<?= count($relatorios) ?>)</h2>
            <p>Ocorrências, decisões e encaminhamentos internos.</p>
        </div>
    </div>

    <?php if (empty($relatorios)): ?>
        <p class="text-muted">Nenhum relatório registrado.</p>
    <?php else: ?>
        <div class="ata-relatorios">
            <?php foreach ($relatorios as $r): ?>
                <article class="ata-relatorio <?= h($r['tipo_relatorio']) ?>">
                    <div class="ata-relatorio-header">
                        <strong><?= h($r['titulo'] ?: '(sem título)') ?></strong>
                        <span class="ata-relatorio-tipo <?= h($r['tipo_relatorio']) ?>">
                            <?= h($r['tipo_relatorio']) ?>
                            <?php if ($r['tema']): ?>
                                · <?= h(Ata::TEMAS_RELATORIO[$r['tema']] ?? $r['tema']) ?>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="ata-relatorio-conteudo"><?= nl2br(h($r['conteudo'])) ?></div>

                    <div class="ata-relatorio-footer">
                        <span>
                            <i class="fas fa-user"></i>
                            <?= h($r['autor_nome']) ?>
                            — <?= ViewHelper::data($r['created_at'], true) ?>
                        </span>

                        <?php if (!empty($r['participantes'])): ?>
                            <span class="participantes">
                                <i class="fas fa-users"></i>
                                <?php foreach ($r['participantes'] as $p): ?>
                                    <span class="participante-tag"><?= h($p['usuario_nome']) ?></span>
                                <?php endforeach; ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($podePreencher): ?>
                            <form method="POST" action="<?= $basePath ?>/relatorios/<?= (int) $r['id'] ?>/excluir" class="d-inline" style="margin-left:auto;">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="button small btn-outline-danger"
                                        onclick="return confirm('Excluir relatório?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($podePreencher): ?>
        <div class="ata-add-form">
            <h3><i class="fas fa-plus-circle"></i> Adicionar relatório interno</h3>

            <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/relatorio">
                <?= ViewHelper::csrfField() ?>

                <label class="field-label">Título</label>
                <input type="text" name="titulo" class="field" maxlength="200">

                <div class="field-row">
                    <div>
                        <label class="field-label">Tipo *</label>
                        <select name="tipo_relatorio" class="field" required>
                            <option value="ocorrencia">Ocorrência</option>
                            <option value="decisao">Decisão</option>
                            <option value="encaminhamento">Encaminhamento</option>
                            <option value="observacao">Observação</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Tema</label>
                        <select name="tema" class="field">
                            <option value="">(sem tema)</option>
                            <?php foreach (Ata::TEMAS_RELATORIO as $chave => $label): ?>
                                <option value="<?= h($chave) ?>"><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label class="field-label">Conteúdo *</label>
                <textarea name="conteudo" class="field" rows="3" maxlength="3000" required></textarea>

                <?php if (!empty($alunosDoGrupo) || !empty($alunosOutros)): ?>
                    <label class="field-label">Participantes mencionados</label>

                    <?php if (!empty($alunosDoGrupo)): ?>
                        <p class="text-muted mb-8" style="font-size:11.5px;">
                            <i class="fas fa-users"></i> <strong>Alunos do grupo</strong>
                        </p>
                        <div class="ata-atividade-participantes mb-12">
                            <?php foreach ($alunosDoGrupo as $a): ?>
                                <label class="participante-tag grupo" style="cursor:pointer;">
                                    <input type="checkbox" name="participantes[]" value="<?= (int) $a['id'] ?>" style="margin-right:6px;">
                                    <?= h($a['nome']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($alunosOutros)): ?>
                        <p class="text-muted mb-8" style="font-size:11.5px;">
                            <i class="fas fa-user-friends"></i> <strong>Outros alunos da turma</strong>
                        </p>
                        <div class="ata-atividade-participantes">
                            <?php foreach ($alunosOutros as $a): ?>
                                <label class="participante-tag turma" style="cursor:pointer;">
                                    <input type="checkbox" name="participantes[]" value="<?= (int) $a['id'] ?>" style="margin-right:6px;">
                                    <?= h($a['nome']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <p class="mt-16">
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-plus"></i> Adicionar relatório
                    </button>
                </p>
            </form>
        </div>
    <?php endif; ?>
</section>

<!-- ============ FINALIZAR ============ -->
<?php if ($podePreencher): ?>
    <section class="panel mb-16">
        <div class="panel-header">
            <div>
                <h2>Finalizar ata</h2>
                <p>
                    Depois de finalizada, a ata vai para o representante validar.
                    Certifique-se de ter adicionado pelo menos 1 atividade.
                </p>
            </div>
        </div>

        <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/finalizar">
            <?= ViewHelper::csrfField() ?>
            <button type="submit" class="button button-primary"
                    onclick="return confirm('Finalizar ata? Após finalizada, você não poderá editar até o representante reabrir.')">
                <i class="fas fa-flag-checkered"></i> Finalizar ata
            </button>
        </form>
    </section>
<?php endif; ?>

<p class="mt-20">
    <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $ata['projeto_id'] ?>/atas">
        <i class="fas fa-arrow-left"></i> Voltar às atas
    </a>
</p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>