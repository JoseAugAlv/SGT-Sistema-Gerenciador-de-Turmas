<?php
$cssPagina = ['projetos.css', 'grupos.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Diretores
        </div>
        <h1><?= h($grupo['nome']) ?></h1>
        <p class="hero-subtitle">
            Projeto: <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar ao grupo
        </a>
        <a class="button button-secondary" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/historico">
            <i class="fas fa-history"></i> Ver histórico
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Diretores ativos (<?= count($diretores) ?>)</h2>
        </div>
    </div>

    <?php if (empty($diretores)): ?>
        <p class="text-muted">Nenhum diretor nomeado.</p>
    <?php else: ?>
        <div class="diretores-lista">
            <?php foreach ($diretores as $d): ?>
                <div class="diretor-item">
                    <span class="avatar"><?= strtoupper(substr($d['nome'], 0, 1)) ?></span>
                    <div class="diretor-info">
                        <strong><?= h($d['nome']) ?></strong>
                        <small><?= h($d['email']) ?></small>
                    </div>

                    <?php if ($podeEditar): ?>
                        <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/remover">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="usuario_id" value="<?= (int) $d['usuario_id'] ?>">
                            <button type="submit" class="button small btn-outline-danger"
                                    onclick="return confirm('Remover este diretor? Avaliações antigas continuam existindo. Atas pendentes serão reatribuídas.')">
                                <i class="fas fa-user-minus"></i> Remover
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($podeEditar): ?>
    <?php
    $ativosIds = array_map(fn($d) => (int) $d['usuario_id'], $diretores);
    $candidatos = array_filter($membros, fn($m) => !in_array((int) $m['usuario_id'], $ativosIds, true));
    ?>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Nomear novo diretor</h2>
                <p>Escolha um membro ativo do grupo.</p>
            </div>
        </div>

        <?php if (empty($candidatos)): ?>
            <p class="text-muted">
                Nenhum membro disponível para nomear (todos já são diretores ou o grupo não tem membros).
            </p>
        <?php else: ?>
            <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/nomear">
                <?= ViewHelper::csrfField() ?>

                <label class="field-label">Membro do grupo</label>
                <select name="usuario_id" class="field" required>
                    <option value="">Selecione</option>
                    <?php foreach ($candidatos as $c): ?>
                        <option value="<?= (int) $c['usuario_id'] ?>">
                            <?= h($c['nome']) ?> — <?= h($c['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <p class="mt-16">
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-crown"></i> Nomear como diretor
                    </button>
                </p>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>