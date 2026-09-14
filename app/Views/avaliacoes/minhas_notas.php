<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Minhas notas</h2>

<?php if (empty($boletim['linhas'])): ?>
    <p>Este projeto ainda não tem critérios definidos.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Critério</th>
                <th>Tipo</th>
                <th>Peso</th>
                <th>Nota</th>
                <th>Conceito</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($boletim['linhas'] as $l): ?>
                <tr>
                    <td><?= h($l['criterio']['nome']) ?></td>
                    <td><code><?= h($l['criterio']['tipo_avaliacao']) ?></code></td>
                    <td><?= number_format((float) $l['criterio']['peso'], 2, ',', '.') ?></td>
                    <td>
                        <?= $l['nota'] === null ? '—' : number_format($l['nota'], 2, ',', '.') ?>
                    </td>
                    <td>
                        <?php if ($l['conceito'] !== null): ?>
                            <strong><?= h($l['conceito']) ?></strong>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Média ponderada</th>
                <th><?= $boletim['media_ponderada'] === null ? '—' : number_format($boletim['media_ponderada'], 2, ',', '.') ?></th>
                <th><?= h($boletim['conceito_final'] ?? '—') ?></th>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>