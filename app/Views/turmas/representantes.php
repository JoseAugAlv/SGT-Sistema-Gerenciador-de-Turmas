<?php
$cssPagina = 'turmas.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Representantes
        </div>
        <h1><?= h($turma['nome']) ?></h1>
        <p class="hero-subtitle">
            Máximo de <strong>2 representantes</strong> ativos. Um deles sempre deve permanecer na turma.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar à turma
        </a>
    </div>
</section>

<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Atuais (<?= count($representantes) ?>/2)</h2>
        </div>
    </div>

    <?php if (empty($representantes)): ?>
        <p class="text-muted">Nenhum representante nomeado.</p>
    <?php else: ?>
        <div class="turma-membros-lista">
            <?php foreach ($representantes as $r): ?>
                <div class="turma-membro-item">
                    <span class="avatar avatar-indigo"><?= strtoupper(substr($r['nome'], 0, 1)) ?></span>
                    <div class="membro-info">
                        <strong><?= h($r['nome']) ?></strong>
                        <small><?= h($r['email']) ?></small>
                    </div>

                    <?php if (count($representantes) > 1): ?>
                        <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes/remover">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="usuario_id" value="<?= (int) $r['usuario_id'] ?>">
                            <button type="submit" class="button small btn-outline-danger"
                                    onclick="return confirm('Remover este representante?')">
                                <i class="fas fa-user-minus"></i> Remover
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="aviso-ultimo-rep">
                            <i class="fas fa-info-circle"></i>
                            Último representante — não pode ser removido sem nomear outro antes.
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if (count($representantes) < 2): ?>
    <?php
    $alunosSemRep = array_filter($alunos, fn($a) => $a['papel'] !== 'representante');
    ?>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Nomear novo representante</h2>
                <p>Escolha um aluno ativo da turma.</p>
            </div>
        </div>

        <?php if (empty($alunosSemRep)): ?>
            <p class="text-muted">
                Não há alunos disponíveis para nomear.
                <a href="<?= $basePath ?>/alunos">Adicione alunos à turma</a> primeiro.
            </p>
        <?php else: ?>
            <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes/nomear">
                <?= ViewHelper::csrfField() ?>

                <label class="field-label">Aluno</label>
                <select name="usuario_id" class="field" required>
                    <option value="">Selecione</option>
                    <?php foreach ($alunosSemRep as $a): ?>
                        <option value="<?= (int) $a['id'] ?>">
                            <?= h($a['nome']) ?> — <?= h($a['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <p class="mt-16">
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-crown"></i> Nomear representante
                    </button>
                </p>
            </form>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="panel">
        <div class="notice notice-blue mt-0">
            <span>i</span>
            <div>
                <strong>Turma já possui 2 representantes</strong>
                <p>Para nomear um terceiro, remova um dos atuais primeiro.</p>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>