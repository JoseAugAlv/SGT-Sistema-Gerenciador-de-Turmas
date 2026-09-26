<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <h1>Alunos</h1>
        <p class="hero-subtitle">
            <?= $isMaster ? 'Todos os alunos do sistema.' : 'Alunos das suas turmas.' ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/alunos/massa">
            <i class="fas fa-layer-group"></i> Criar em massa
        </a>
        <a class="button button-primary" href="<?= $basePath ?>/alunos/criar">
            <i class="fas fa-user-plus"></i> Novo aluno
        </a>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Lista (<?= count($alunos) ?>)</h2>
        </div>
    </div>

    <?php if (empty($alunos)): ?>
        <p style="color:var(--muted);">Nenhum aluno cadastrado.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Turmas</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alunos as $a): ?>
                    <tr>
                        <td><?= h($a['nome']) ?></td>
                        <td><?= h($a['email']) ?></td>
                        <td><?= h($a['turmas'] ?? '—') ?></td>
                        <td>
                            <?php if ($a['ativo']): ?>
                                <span class="badge badge-green">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-red">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="button small button-ghost" href="<?= $basePath ?>/alunos/<?= (int) $a['id'] ?>/editar">
                                <i class="fas fa-pen"></i> Editar
                            </a>
                            <a class="button small button-ghost" href="<?= $basePath ?>/alunos/<?= (int) $a['id'] ?>/grupos">
                                <i class="fas fa-users"></i> Grupos
                            </a>
                            <form method="POST" action="<?= $basePath ?>/alunos/<?= (int) $a['id'] ?>/alternar-ativo" style="display:inline;">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="button small button-ghost"
                                        onclick="return confirm('<?= $a['ativo'] ? 'Desativar' : 'Ativar' ?> este aluno?')">
                                    <?= $a['ativo'] ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>