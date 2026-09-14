<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h1>Diretores — <?= h($grupo['nome']) ?></h1>
<p>Projeto: <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a></p>

<p><a href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>">
    <i class="fas fa-arrow-left"></i> Voltar ao grupo
</a>
—
<a href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/historico">
    <i class="fas fa-history"></i> Ver histórico
</a></p>

<hr>

<h2>Diretores ativos (<?= count($diretores) ?>)</h2>

<?php if (empty($diretores)): ?>
    <p>Nenhum diretor nomeado.</p>
<?php else: ?>
    <ul>
        <?php foreach ($diretores as $d): ?>
            <li>
                <i class="fas fa-crown"></i> <strong><?= h($d['nome']) ?></strong>
                (<?= h($d['email']) ?>)
                <?php if ($podeEditar): ?>
                    <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/remover" style="display:inline">
                        <?= ViewHelper::csrfField() ?>
                        <input type="hidden" name="usuario_id" value="<?= (int) $d['usuario_id'] ?>">
                        <button type="submit" onclick="return confirm('Remover este diretor? Avaliações antigas continuam existindo. Atas pendentes serão reatribuídas.')">
                            <i class="fas fa-user-minus"></i> Remover
                        </button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($podeEditar): ?>
    <hr>
    <h2>Nomear novo diretor</h2>

    <?php
    $ativosIds = array_map(fn($d) => (int) $d['usuario_id'], $diretores);
    $candidatos = array_filter($membros, fn($m) => !in_array((int) $m['usuario_id'], $ativosIds, true));
    ?>

    <?php if (empty($candidatos)): ?>
        <p>Nenhum membro disponível para nomear (todos já são diretores ou o grupo não tem membros).</p>
    <?php else: ?>
        <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/nomear">
            <?= ViewHelper::csrfField() ?>
            <p><label>Membro do grupo<br>
                <select name="usuario_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($candidatos as $c): ?>
                        <option value="<?= (int) $c['usuario_id'] ?>"><?= h($c['nome']) ?> — <?= h($c['email']) ?></option>
                    <?php endforeach; ?>
                </select></label></p>
            <p>
                <button type="submit"><i class="fas fa-crown"></i> Nomear como diretor</button>
            </p>
        </form>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>