<?php
$cssPagina = 'alunos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Alunos
        </div>
        <h1><?= $isMaster ? 'Todos os alunos' : 'Alunos das minhas turmas' ?></h1>
        <p class="hero-subtitle">
            <?php if ($isMaster): ?>
                Visão geral do sistema — <?= count($alunos) ?> aluno(s)
            <?php else: ?>
                <?= count($alunos) ?> aluno(s) nas <?= count($minhasTurmas) ?> turma(s) em que você participa
            <?php endif; ?>
        </p>
    </div>

    <?php if ($podeGerenciar): ?>
        <div class="hero-actions">
            <?php if ($isMaster): ?>
                <a class="button button-secondary" href="<?= $basePath ?>/alunos/massa">
                    <i class="fas fa-layer-group"></i> Criar em massa
                </a>
                <a class="button button-secondary" href="<?= $basePath ?>/alunos/criar">
                    <i class="fas fa-user-plus"></i> Novo aluno
                </a>
            <?php endif; ?>
            <a class="button button-primary" href="<?= $basePath ?>/alunos/convidar">
                <i class="fas fa-envelope"></i> Convidar por email
            </a>
        </div>
    <?php endif; ?>
</section>

<?php if (empty($alunos)): ?>
    <section class="panel aluno-empty">
        <span class="icon-large text-muted"><i class="fas fa-user-graduate"></i></span>
        <h2>Nenhum aluno encontrado</h2>
        <p>
            <?php if ($isMaster): ?>
                Ainda não há alunos cadastrados no sistema.
            <?php elseif (!empty($minhasTurmas)): ?>
                As turmas em que você participa ainda não têm alunos ativos.
            <?php else: ?>
                Você ainda não está em nenhuma turma.
            <?php endif; ?>
        </p>

        <?php if ($podeGerenciar && !empty($minhasTurmas)): ?>
            <a class="button button-primary" href="<?= $basePath ?>/alunos/convidar">
                <i class="fas fa-envelope"></i> Convidar alunos por email
            </a>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Lista (<?= count($alunos) ?>)</h2>
                <p>
                    <?php if ($podeGerenciar): ?>
                        Você pode editar, ver grupos e ativar/desativar alunos.
                    <?php else: ?>
                        Modo leitura — apenas representantes podem alterar dados.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="alunos-wrapper">
            <table class="alunos-tabela">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Grupos</th>
                        <th>Status</th>
                        <?php if ($podeGerenciar): ?>
                            <th>Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alunos as $a): ?>
                        <?php
                        $aid        = (int) $a['id'];
                        $meusGrupos = $gruposPorAluno[$aid] ?? [];
                        ?>
                        <tr>
                            <td>
                                <div class="aluno-cell">
                                    <span class="avatar avatar-blue">
                                        <?= strtoupper(substr($a['nome'], 0, 1)) ?>
                                    </span>
                                    <div class="aluno-cell-info">
                                        <strong><?= h($a['nome']) ?></strong>
                                        <?php if ($podeGerenciar): ?>
                                            <small><?= h($a['email']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="turmas-badges">
                                    <?php if (empty($meusGrupos)): ?>
                                        <span class="turma-badge vazio">Sem grupo</span>
                                    <?php else: ?>
                                        <?php foreach ($meusGrupos as $g): ?>
                                            <span class="turma-badge"
                                                  title="Projeto: <?= h($g['projeto_nome']) ?>">
                                                <?= h($g['grupo_nome']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <?php if ($a['ativo']): ?>
                                    <span class="badge badge-green">
                                        <i class="fas fa-check"></i> Ativo
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-red">
                                        <i class="fas fa-ban"></i> Inativo
                                    </span>
                                <?php endif; ?>
                            </td>

                            <?php if ($podeGerenciar): ?>
                                <td>
                                    <div class="aluno-acoes">
                                        <a class="button small button-ghost"
                                           href="<?= $basePath ?>/alunos/<?= $aid ?>/editar"
                                           title="Editar aluno">
                                            <i class="fas fa-pen"></i> Editar
                                        </a>
                                        <a class="button small button-ghost"
                                           href="<?= $basePath ?>/alunos/<?= $aid ?>/grupos"
                                           title="Ver grupos">
                                            <i class="fas fa-users"></i> Grupos
                                        </a>
                                        <form method="POST"
                                              action="<?= $basePath ?>/alunos/<?= $aid ?>/alternar-ativo">
                                            <?= ViewHelper::csrfField() ?>
                                            <button type="submit"
                                                    class="button small <?= $a['ativo'] ? 'btn-outline-danger' : 'button-ghost' ?>"
                                                    onclick="return confirm('<?= $a['ativo'] ? 'Desativar' : 'Ativar' ?> este aluno?')">
                                                <i class="fas <?= $a['ativo'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                                <?= $a['ativo'] ? 'Desativar' : 'Ativar' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>