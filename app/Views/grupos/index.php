<?php
$cssPagina = ['projetos.css', 'grupos.css', 'alunos.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Grupos
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <?= count($grupos) ?> grupo(s) neste projeto.
        </p>
    </div>
    <div class="hero-actions">
        <?php if ($podeEditar && !$projeto['encerrado']): ?>
            <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos/criar">
                <i class="fas fa-plus"></i> Novo grupo
            </a>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<!-- ============================================================ -->
<!-- Grid de grupos                                               -->
<!-- ============================================================ -->
<?php if (empty($grupos)): ?>
    <section class="panel empty-state">
        <span class="icon-large text-muted"><i class="fas fa-users"></i></span>
        <h2>Nenhum grupo cadastrado</h2>
        <p>
            <?php if ($podeEditar && !$projeto['encerrado']): ?>
                Crie o primeiro grupo deste projeto clicando no botão abaixo.
            <?php else: ?>
                Aguarde o representante criar os grupos.
            <?php endif; ?>
        </p>
        <?php if ($podeEditar && !$projeto['encerrado']): ?>
            <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos/criar">
                <i class="fas fa-plus"></i> Criar grupo
            </a>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="grupos-grid">
        <?php foreach ($grupos as $g): ?>
            <article class="panel grupo-card">
                <div class="panel-header">
                    <div>
                        <h2><?= h($g['nome']) ?></h2>
                        <p>
                            Modo: <code><?= h($g['modo_avaliacao_grupo']) ?></code>
                            / <code><?= h($g['modo_avaliacao_por']) ?></code>
                        </p>
                    </div>
                </div>

                <div class="status-line">
                    <strong><?= (int) $g['total_membros'] ?></strong>
                    <span>membro(s)</span>
                </div>

                <div class="status-line">
                    <strong><?= (int) $g['total_diretores'] ?></strong>
                    <span>diretor(es)</span>
                </div>

                <a class="button button-primary full-width" href="<?= $basePath ?>/grupos/<?= (int) $g['id'] ?>">
                    <i class="fas fa-arrow-right"></i> Abrir grupo
                </a>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<!-- ============================================================ -->
<!-- Tabela: todos os alunos e seus grupos                        -->
<!-- ============================================================ -->
<section class="panel mt-20">
    <div class="panel-header">
        <div>
            <h2>Alunos e seus grupos</h2>
            <p>
                Todos os <?= count($alunosComGrupos) ?> aluno(s) da turma e em quais grupos deste projeto estão.
            </p>
        </div>
    </div>

    <?php if (empty($alunosComGrupos)): ?>
        <p class="text-muted mb-0">Nenhum aluno ativo na turma.</p>
    <?php else: ?>
        <div class="alunos-wrapper">
            <table class="alunos-tabela">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Grupos neste projeto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alunosComGrupos as $a): ?>
                        <?php
                        $gruposAluno = !empty($a['grupos_nomes'])
                            ? array_filter(array_map('trim', explode(',', $a['grupos_nomes'])))
                            : [];
                        ?>
                        <tr>
                            <td>
                                <div class="aluno-cell">
                                    <span class="avatar avatar-blue"><?= strtoupper(substr($a['nome'], 0, 1)) ?></span>
                                    <div class="aluno-cell-info">
                                        <strong><?= h($a['nome']) ?></strong>
                                        <small><?= h($a['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (empty($gruposAluno)): ?>
                                    <span class="turma-badge vazio">Sem grupo</span>
                                <?php else: ?>
                                    <div class="turmas-badges">
                                        <?php foreach ($gruposAluno as $gn): ?>
                                            <span class="turma-badge"><?= h($gn) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>