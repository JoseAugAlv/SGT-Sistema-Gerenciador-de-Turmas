<?php
// app/Views/relatorios/usuarios.php

$tituloPagina = 'Relatório de Usuários - ' . App::getName();
$cssPagina = 'home.css';
$basePath = App::getBasePath();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
?>

<section class="section-block animate-in">
    <div class="container">
        <h1><i class="fas fa-file-alt"></i> Relatório de Usuários</h1>
        <p style="color: var(--color-text);">Total de usuários: <?= count($usuarios ?? []) ?></p>

        <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="<?= $basePath ?>/relatorios/exportar" class="btn btn-primary">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </a>
            <a href="<?= $basePath ?>/relatorios/pdf" class="btn btn-warning">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </a>
            <a href="<?= $basePath ?>/" class="btn">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="table-wrapper" style="margin-top: 1.5rem; overflow-x: auto; background: white; border-radius: 12px; box-shadow: var(--shadow-sm);">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th style="padding: 0.8rem 1rem; text-align: left;">ID</th>
                        <th style="padding: 0.8rem 1rem; text-align: left;">Nome</th>
                        <th style="padding: 0.8rem 1rem; text-align: left;">Email</th>
                        <th style="padding: 0.8rem 1rem; text-align: left;">Perfil</th>
                        <th style="padding: 0.8rem 1rem; text-align: left;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td style="padding: 0.8rem 1rem;">#<?= $u['id_usuario'] ?></td>
                        <td style="padding: 0.8rem 1rem;"><strong><?= htmlspecialchars($u['nome']) ?></strong></td>
                        <td style="padding: 0.8rem 1rem;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding: 0.8rem 1rem;">
                            <span class="status-badge perfil-<?= $u['id_perfil'] ?? 4 ?>">
                                <?= htmlspecialchars($u['nome_perfil'] ?? 'Usuario') ?>
                            </span>
                        </td>
                        <td style="padding: 0.8rem 1rem;">
                            <span class="status-badge <?= $u['ativo'] ? 'ativo' : 'inativo' ?>">
                                <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>