<?php
$cssPagina = 'alunos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Turmas do aluno (query extra)
$pdo = Database::getConnection();
$stmt = $pdo->prepare("
    SELECT t.id, t.nome, t.codigo_acesso, tu.papel, tu.ativo, tu.entrou_em, tu.saiu_em
    FROM turma_usuarios tu
    INNER JOIN turmas t ON t.id = tu.turma_id
    WHERE tu.usuario_id = ?
    ORDER BY tu.ativo DESC, t.nome ASC
");
$stmt->execute([(int) $aluno['id']]);
$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Grupos do aluno
        </div>
        <h1><?= h($aluno['nome']) ?></h1>
        <div class="aluno-hero-meta">
            <span><?= h($aluno['email']) ?></span>
            <span><?= count($grupos) ?> grupo(s) — <?= count($diretorias) ?> diretoria(s)</span>
        </div>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/alunos/<?= (int) $aluno['id'] ?>/editar">
            <i class="fas fa-pen"></i> Editar aluno
        </a>
        <a class="button button-ghost" href="<?= $basePath ?>/alunos">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<!-- ============ DIREtorias ATIVAS ============ -->
<?php if (!empty($diretorias)): ?>
    <section class="panel mb-16" style="border-left:4px solid var(--orange);">
        <div class="panel-header">
            <div>
                <h2>
                    <i class="fas fa-crown" style="color:var(--orange);"></i>
                    É diretor ativo em (<?= count($diretorias) ?>)
                </h2>
                <p>Grupos que este aluno lidera no momento.</p>
            </div>
        </div>

        <div class="diretorias-grid">
            <?php foreach ($diretorias as $d): ?>
                <article class="panel aluno-grupo-card">
                    <div class="aluno-grupo-header">
                        <div>
                            <h3><?= h($d['grupo_nome']) ?></h3>
                            <p>
                                <a href="<?= $basePath ?>/projetos/<?= (int) $d['projeto_id'] ?>">
                                    <?= h($d['projeto_nome']) ?>
                                </a>
                            </p>
                        </div>
                        <span class="badge badge-yellow">
                            <i class="fas fa-crown"></i> Diretor
                        </span>
                    </div>

                    <div class="aluno-grupo-meta">
                        <div class="meta-item">
                            <span class="meta-label">Turma</span>
                            <span class="meta-valor"><?= h($d['turma_nome']) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Nomeado em</span>
                            <span class="meta-valor"><?= h($d['nomeado_em']) ?></span>
                        </div>
                    </div>

                    <p class="mb-0">
                        <a class="button small button-ghost full-width"
                           href="<?= $basePath ?>/grupos/<?= (int) $d['grupo_id'] ?>">
                            <i class="fas fa-arrow-right"></i> Abrir grupo
                        </a>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- ============ GRUPOS EM QUE PARTICIPA ============ -->
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Grupos em que participa (<?= count($grupos) ?>)</h2>
            <p>Inclui vínculos ativos e removidos (com data de saída).</p>
        </div>
    </div>

    <?php if (empty($grupos)): ?>
        <p class="text-muted">Este aluno ainda não foi adicionado a nenhum grupo.</p>
    <?php else: ?>
        <div class="diretorias-grid">
            <?php foreach ($grupos as $g): ?>
                <?php
                $ativo = empty($g['saiu_em']);
                $classe = $ativo ? '' : ($g['projeto_encerrado'] ? 'encerrado' : 'removido');
                ?>
                <article class="panel aluno-grupo-card <?= $classe ?>">
                    <div class="aluno-grupo-header">
                        <div>
                            <h3>
                                <a href="<?= $basePath ?>/grupos/<?= (int) $g['grupo_id'] ?>">
                                    <?= h($g['grupo_nome']) ?>
                                </a>
                            </h3>
                            <p>
                                <a href="<?= $basePath ?>/projetos/<?= (int) $g['projeto_id'] ?>">
                                    <?= h($g['projeto_nome']) ?>
                                </a>
                            </p>
                        </div>

                        <?php if (!$ativo): ?>
                            <span class="badge badge-gray">Removido</span>
                        <?php elseif ($g['projeto_encerrado']): ?>
                            <span class="badge badge-red">Projeto encerrado</span>
                        <?php else: ?>
                            <span class="badge badge-green">Ativo</span>
                        <?php endif; ?>
                    </div>

                    <div class="aluno-grupo-meta">
                        <div class="meta-item">
                            <span class="meta-label">Turma</span>
                            <span class="meta-valor"><?= h($g['turma_nome']) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Entrou em</span>
                            <span class="meta-valor"><?= h($g['entrou_em']) ?></span>
                        </div>
                        <?php if (!$ativo): ?>
                            <div class="meta-item">
                                <span class="meta-label">Saiu em</span>
                                <span class="meta-valor"><?= h($g['saiu_em']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <span class="meta-label">Diretor(a)</span>
                            <span class="meta-valor">
                                <?php if ($g['sou_diretor']): ?>
                                    <span class="badge badge-yellow">
                                        <i class="fas fa-crown"></i> Sim
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ============ TURMAS DO ALUNO ============ -->
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Turmas (<?= count($turmas) ?>)</h2>
            <p>Vínculos do aluno com as turmas do sistema.</p>
        </div>
    </div>

    <?php if (empty($turmas)): ?>
        <p class="text-muted">Nenhuma turma.</p>
    <?php else: ?>
        <div class="alunos-wrapper">
            <table class="alunos-tabela">
                <thead>
                    <tr>
                        <th>Turma</th>
                        <th>Código</th>
                        <th>Papel</th>
                        <th>Status</th>
                        <th>Entrou em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($turmas as $t): ?>
                        <tr>
                            <td><strong><?= h($t['nome']) ?></strong></td>
                            <td>
                                <code class="code-inline"><?= h($t['codigo_acesso']) ?></code>
                            </td>
                            <td>
                                <?php if ($t['papel'] === 'representante'): ?>
                                    <span class="badge badge-yellow">
                                        <i class="fas fa-crown"></i> Representante
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Aluno</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['ativo']): ?>
                                    <span class="badge badge-green">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Removido</span>
                                <?php endif; ?>
                            </td>
                            <td><?= h($t['entrou_em']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>