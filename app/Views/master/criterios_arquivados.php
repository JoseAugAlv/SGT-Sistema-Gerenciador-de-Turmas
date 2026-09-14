<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Critérios Arquivados</h1>
<p><a href="<?= $basePath ?>/master">Voltar ao painel</a></p>

<p>Critérios excluídos com avaliações ficam disponíveis por <strong>30 dias</strong> para restauração.</p>

<?php if (empty($arquivados)): ?>
    <p>Nenhum critério arquivado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Arquivado em</th>
                <th>Expira em</th>
                <th>Projeto</th>
                <th>Turma</th>
                <th>Por</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($arquivados as $a): ?>
                <?php
                $expirado = !empty($a['expira_em']) && strtotime($a['expira_em']) < time();
                $dados    = json_decode($a['dados_json'], true) ?: [];
                $nome     = $dados['criterio']['nome'] ?? '(sem nome)';
                ?>
                <tr>
                    <td><?= h($a['arquivado_em']) ?></td>
                    <td>
                        <?= h($a['expira_em']) ?>
                        <?php if ($expirado): ?>
                            <strong style="color:#b00">(expirado)</strong>
                        <?php endif; ?>
                    </td>
                    <td><?= h($a['projeto_nome']) ?></td>
                    <td><?= h($a['turma_nome']) ?></td>
                    <td><?= h($a['arquivado_por_nome'] ?? '—') ?></td>
                    <td>
                        <strong><?= h($nome) ?></strong><br>
                        <small>
                            <?= count($dados['individuais'] ?? []) ?> avaliações |
                            <?= count($dados['coletivas'] ?? []) ?> coletivas
                        </small>
                        <?php if (!$expirado): ?>
                            <form method="POST" action="<?= $basePath ?>/master/criterios-arquivados/<?= (int) $a['id'] ?>/restaurar" style="display:inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" onclick="return confirm('Restaurar este critério e suas avaliações?')">
                                    <i class="fas fa-rotate-left"></i> Restaurar
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>