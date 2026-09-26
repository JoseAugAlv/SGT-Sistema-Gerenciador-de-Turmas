<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <h1><i class="fas fa-users"></i> Grupos do Aluno</h1>
        <p class="hero-subtitle">
            <strong><?= h($aluno['nome']) ?></strong> —
            <?= h($aluno['email']) ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/alunos/<?= (int) $aluno['id'] ?>/editar">
            <i class="fas fa-pen"></i> Editar aluno
        </a>
        <a class="button button-secondary" href="<?= $basePath ?>/alunos">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<!-- ============ DIREtorias ativas ============ -->
<?php if (!empty($diretorias)): ?>
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2><i class="fas fa-crown" style="color:var(--orange);"></i> É diretor ativo em (<?= count($diretorias) ?>)</h2>
                <p>Grupos que este aluno lidera no momento.</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Projeto</th>
                    <th>Turma</th>
                    <th>Nomeado em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diretorias as $d): ?>
                    <tr>
                        <td><?= h($d['grupo_nome']) ?></td>
                        <td><?= h($d['projeto_nome']) ?></td>
                        <td><?= h($d['turma_nome']) ?></td>
                        <td><?= h($d['nomeado_em']) ?></td>
                        <td>
                            <a class="button small button-ghost" href="<?= $basePath ?>/grupos/<?= (int) $d['grupo_id'] ?>">
                                <i class="fas fa-arrow-right"></i> Abrir grupo
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>

<!-- ============ Grupos em que participa ============ -->
<section class="panel" style="margin-top:16px;">
    <div class="panel-header">
        <div>
            <h2>Grupos em que participa (<?= count($grupos) ?>)</h2>
            <p>
                Inclui vínculos ativos e removidos (com data de saída).
            </p>
        </div>
    </div>

    <?php if (empty($grupos)): ?>
        <p style="color:var(--muted);">
            Este aluno ainda não foi adicionado a nenhum grupo.
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Projeto</th>
                    <th>Turma</th>
                    <th>Entrou em</th>
                    <th>Saiu em</th>
                    <th>Status</th>
                    <th>Diretor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grupos as $g): ?>
                    <?php $ativo = empty($g['saiu_em']); ?>
                    <tr>
                        <td>
                            <a href="<?= $basePath ?>/grupos/<?= (int) $g['grupo_id'] ?>">
                                <?= h($g['grupo_nome']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?= $basePath ?>/projetos/<?= (int) $g['projeto_id'] ?>">
                                <?= h($g['projeto_nome']) ?>
                            </a>
                            <?php if ($g['projeto_encerrado']): ?>
                                <span class="badge badge-gray">encerrado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($g['turma_nome']) ?></td>
                        <td><?= h($g['entrou_em']) ?></td>
                        <td><?= h($g['saiu_em'] ?? '—') ?></td>
                        <td>
                            <?php if ($ativo): ?>
                                <span class="badge badge-green">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-gray">Removido</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($g['sou_diretor']): ?>
                                <span class="badge badge-yellow"><i class="fas fa-crown"></i> Sim</span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<!-- ============ Turmas do aluno ============ -->
<?php
$pdo = Database::getConnection();
$stmt = $pdo->prepare("
    SELECT t.id, t.nome, t.codigo_acesso, tu.papel, tu.ativo, tu.entrou_em
    FROM turma_usuarios tu
    INNER JOIN turmas t ON t.id = tu.turma_id
    WHERE tu.usuario_id = ?
    ORDER BY tu.ativo DESC, t.nome ASC
");
$stmt->execute([(int) $aluno['id']]);
$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="panel" style="margin-top:16px;">
    <div class="panel-header">
        <div>
            <h2>Turmas (<?= count($turmas) ?>)</h2>
        </div>
    </div>

    <?php if (empty($turmas)): ?>
        <p style="color:var(--muted);">Nenhuma turma.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Turma</th>
                    <th>Código</th>
                    <th>Papel</th>
                    <th>Ativo</th>
                    <th>Entrou em</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($turmas as $t): ?>
                    <tr>
                        <td><?= h($t['nome']) ?></td>
                        <td><code><?= h($t['codigo_acesso']) ?></code></td>
                        <td><?= h($t['papel']) ?></td>
                        <td>
                            <?php if ($t['ativo']): ?>
                                <span class="badge badge-green">Sim</span>
                            <?php else: ?>
                                <span class="badge badge-gray">Não</span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($t['entrou_em']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>