<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

function badgeStatus(string $s): string {
    return match ($s) {
        'pendente'   => '<i class="fas fa-hourglass-half" style="color:#c80"></i> Pendente',
        'preenchida' => '<i class="fas fa-pen" style="color:#06f"></i> Preenchida',
        'revisada'   => '<i class="fas fa-check-circle" style="color:#0a0"></i> Revisada',
        default      => htmlspecialchars($s),
    };
}
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Atas</h2>

<?php if ($modo === 'rep'): ?>

    <p>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/atas/criar">
            <i class="fas fa-plus"></i> Nova ata
        </a>
    </p>

    <h3>Todas as atas (<?= count($atas['todas']) ?>)</h3>

    <?php if (empty($atas['todas'])): ?>
        <p>Nenhuma ata cadastrada.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Grupo</th>
                    <th>Data</th>
                    <th>Diretor</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($atas['todas'] as $a): ?>
                    <tr>
                        <td>#<?= (int) $a['id'] ?></td>
                        <td><?= h($a['titulo']) ?></td>
                        <td><?= h($a['grupo_nome']) ?></td>
                        <td><?= h($a['data_ata']) ?></td>
                        <td><?= h($a['diretor_nome'] ?? '—') ?></td>
                        <td><?= badgeStatus($a['status']) ?></td>
                        <td>
                            <a href="<?= $basePath ?>/atas/<?= (int) $a['id'] ?>" title="Abrir">
                                <i class="fas fa-eye"></i> Abrir
                            </a>

                            <?php if ($a['status'] !== 'revisada'): ?>
                                —
                                <a href="<?= $basePath ?>/atas/<?= (int) $a['id'] ?>/editar" title="Editar">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                            <?php endif; ?>

                            <?php if ($a['status'] === 'preenchida'): ?>
                                —
                                <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $a['id'] ?>/validar" style="display:inline">
                                    <?= ViewHelper::csrfField() ?>
                                    <button type="submit" title="Validar"
                                            onclick="return confirm('Validar esta ata?')">
                                        <i class="fas fa-check"></i> Validar
                                    </button>
                                </form>
                            <?php endif; ?>

                            —
                            <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $a['id'] ?>/excluir" style="display:inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" title="Excluir" style="color:#b00"
                                        onclick="return confirm('Excluir esta ata?')">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php elseif ($modo === 'diretor'): ?>

    <h3>Pendentes para mim (<?= count($atas['pendentes']) ?>)</h3>
    <?php if (empty($atas['pendentes'])): ?>
        <p>Nenhuma ata pendente.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Grupo</th>
                    <th>Data</th>
                    <th>Prazo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($atas['pendentes'] as $a): ?>
                    <tr>
                        <td>#<?= (int) $a['id'] ?></td>
                        <td><?= h($a['titulo']) ?></td>
                        <td><?= h($a['grupo_nome']) ?></td>
                        <td><?= h($a['data_ata']) ?></td>
                        <td><?= h($a['prazo_preenchimento'] ?? '—') ?></td>
                        <td>
                            <a href="<?= $basePath ?>/atas/<?= (int) $a['id'] ?>">
                                <i class="fas fa-pen"></i> Preencher
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php else: ?>

    <h3>Atas em que participei (<?= count($atas['minhas']) ?>)</h3>
    <?php if (empty($atas['minhas'])): ?>
        <p>Você não participou de nenhuma ata.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Grupo</th>
                    <th>Data</th>
                    <th>Presença</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($atas['minhas'] as $a): ?>
                    <tr>
                        <td>#<?= (int) $a['id'] ?></td>
                        <td><?= h($a['titulo']) ?></td>
                        <td><?= h($a['grupo_nome']) ?></td>
                        <td><?= h($a['data_ata']) ?></td>
                        <td><?= h($a['presente']) ?></td>
                        <td>
                            <a href="<?= $basePath ?>/atas/<?= (int) $a['id'] ?>">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>