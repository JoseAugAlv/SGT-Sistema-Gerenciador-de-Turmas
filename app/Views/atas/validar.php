<?php
$cssPagina = ['projetos.css', 'atas.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

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

<section class="hero-section">
    <div>
        <h1><?= h($ata['titulo']) ?></h1>
        <p class="hero-subtitle">
            Revisar e validar · Grupo <?= h($ata['grupo_nome']) ?>
            · reunião de <?= ViewHelper::data($ata['data_ata']) ?>
            <?php if (!empty($ata['diretor_nome'])): ?> · preenchida por <?= h($ata['diretor_nome']) ?><?php endif; ?>
        </p>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<ol class="ata-etapas" aria-label="Situação da ata">
    <li class="feita"><strong>Criada</strong><small>pelo representante</small></li>
    <li class="feita"><strong>Preenchida</strong><small>pelo diretor</small></li>
    <li class="atual"><strong>Validada</strong><small>falta a sua revisão</small></li>
</ol>
<?php if (!empty($ata['descricao'])): ?>
    <p class="ata-descricao"><?= nl2br(h($ata['descricao'])) ?></p>
<?php endif; ?>

<!-- ============ PARTICIPANTES ============ -->
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Participantes (<?= count($participantes) ?>)</h2>
        </div>
    </div>

    <?php if (empty($participantes)): ?>
        <p class="text-muted">Nenhum participante registrado.</p>
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
        </div>
    </div>

    <?php if (empty($atividades)): ?>
        <p class="text-muted">Nenhuma atividade registrada pelo diretor.</p>
    <?php else: ?>
        <div class="ata-atividades">
            <?php foreach ($atividades as $a): ?>
                <article class="ata-atividade">
                    <div class="ata-atividade-header">
                        <strong><?= h($a['nome']) ?></strong>
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
</section>

<!-- ============ RELATÓRIOS ============ -->
<?php if (!empty($relatorios)): ?>
    <section class="panel mb-16">
        <div class="panel-header">
            <div>
                <h2>Relatórios internos (<?= count($relatorios) ?>)</h2>
            </div>
        </div>

        <div class="ata-relatorios">
            <?php foreach ($relatorios as $r): ?>
                <article class="ata-relatorio <?= h($r['tipo_relatorio']) ?>">
                    <div class="ata-relatorio-header">
                        <strong><?= h($r['titulo'] ?: '(sem título)') ?></strong>
                        <span class="ata-relatorio-tipo <?= h($r['tipo_relatorio']) ?>">
                            <?= h($r['tipo_relatorio']) ?>
                            <?php if ($r['tema']): ?>
                                · <?= h(\Ata::TEMAS_RELATORIO[$r['tema']] ?? $r['tema']) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="ata-relatorio-conteudo"><?= nl2br(h($r['conteudo'])) ?></div>
                    <div class="ata-relatorio-footer">
                        <span>
                            <i class="fas fa-user"></i>
                            <?= h($r['autor_nome']) ?> — <?= ViewHelper::data($r['created_at'], true) ?>
                        </span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- ============ AÇÕES ============ -->
<div class="aval-barra-salvar ata-decisao">
    <span class="aval-status">Confira presenças, atividades e relatórios antes de validar.</span>
    <?php require __DIR__ . '/_devolver.php'; ?>
    <a class="button button-ghost" href="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/editar"><i class="fas fa-pen"></i> Editar dados</a>
    <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/excluir" class="d-inline">
        <?= ViewHelper::csrfField() ?>
        <button type="submit" class="button btn-outline-danger" onclick="return confirm('Excluir esta ata? Atividades e relatórios também serão apagados.')"><i class="fas fa-trash"></i> Excluir</button>
    </form>
    <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/validar" class="d-inline">
        <?= ViewHelper::csrfField() ?>
        <button type="submit" class="button button-primary" onclick="return confirm('Validar esta ata? O diretor será avisado.')"><i class="fas fa-check"></i> Validar ata</button>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>