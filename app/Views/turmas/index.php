<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <h1>Turmas</h1>
        <p class="hero-subtitle">
            <?php if ($isMaster): ?>
                Todas as turmas do sistema.
            <?php else: ?>
                Turmas em que você participa.
            <?php endif; ?>
        </p>
    </div>

    <div class="hero-actions">
        <?php if ($isMaster): ?>
            <a class="button button-primary" href="<?= $basePath ?>/turmas/criar">
                <i class="fas fa-plus"></i> Nova turma
            </a>
        <?php endif; ?>
    </div>
</section>

<?php if (empty($turmas)): ?>
    <section class="panel" style="text-align:center; padding:36px 20px;">
        <h2 style="margin-bottom:8px;">
            <?php if ($isMaster): ?>
                Nenhuma turma cadastrada
            <?php else: ?>
                Você ainda não está em nenhuma turma
            <?php endif; ?>
        </h2>
        <p style="color:var(--muted); max-width:480px; margin:0 auto 18px;">
            <?php if ($isMaster): ?>
                Crie a primeira turma clicando no botão abaixo.
            <?php else: ?>
                Peça o código de acesso da turma para o professor ou representante e entre por ele.
            <?php endif; ?>
        </p>
        <?php if ($isMaster): ?>
            <a class="button button-primary" href="<?= $basePath ?>/turmas/criar">
                <i class="fas fa-plus"></i> Criar turma
            </a>
        <?php else: ?>
            <a class="button button-primary" href="<?= $basePath ?>/turmas/entrar">
                <i class="fas fa-right-to-bracket"></i> Entrar em uma turma com código
            </a>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Lista (<?= count($turmas) ?>)</h2>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Código</th>
                    <th>Alunos</th>
                    <?php if ($isMaster): ?>
                        <th>Reps</th>
                        <th>Status</th>
                    <?php else: ?>
                        <th>Meu papel</th>
                    <?php endif; ?>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($turmas as $t): ?>
                    <tr>
                        <td>
                            <strong><?= h($t['nome']) ?></strong>
                        </td>
                        <td><code><?= h($t['codigo_acesso']) ?></code></td>
                        <td><?= (int) ($t['total_alunos'] ?? 0) ?></td>

                        <?php if ($isMaster): ?>
                            <td><?= (int) ($t['total_reps'] ?? 0) ?>/2</td>
                            <td>
                                <?php if (!empty($t['bloqueada'])): ?>
                                    <span class="badge badge-red"><i class="fas fa-ban"></i> Bloqueada</span>
                                <?php else: ?>
                                    <span class="badge badge-green"><i class="fas fa-check"></i> Ativa</span>
                                <?php endif; ?>
                            </td>
                        <?php else: ?>
                            <td>
                                <span class="badge badge-gray"><?= h($t['meu_papel'] ?? 'aluno') ?></span>
                            </td>
                        <?php endif; ?>

                        <td>
                            <a class="button small button-primary" href="<?= $basePath ?>/turmas/<?= (int) $t['id'] ?>">
                                <i class="fas fa-arrow-right"></i> Abrir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <?php if ($isMaster): ?>
        <section class="panel" style="margin-top:16px;">
            <div class="panel-header">
                <div>
                    <h2>Entrar em uma turma pelo código</h2>
                    <p>Se você recebeu um código de acesso e quer ingressar manualmente.</p>
                </div>
            </div>
            <p>
                <a class="button button-secondary" href="<?= $basePath ?>/turmas/entrar">
                    <i class="fas fa-right-to-bracket"></i> Entrar com código
                </a>
            </p>
        </section>
    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>