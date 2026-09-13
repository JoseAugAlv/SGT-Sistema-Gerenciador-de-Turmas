<?php
// app/Views/usuarios/index.php

$tituloPagina = 'Gerenciar Usuários - ' . App::getName();
$cssPagina = 'usuarios.css';
$basePath = App::getBasePath();
$role = $_SESSION['usuario']['role'] ?? 0;

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
?>

<section class="usuarios-section animate-in">
    <div class="container">
        <div class="usuarios-content">

            <!-- Hero -->
            <div class="usuarios-hero">
                <div>
                    <h1><i class="fas fa-users-cog"></i> Gerenciar Usuários</h1>
                    <p class="usuarios-subtitle">
                        <i class="fas fa-user-shield"></i> 
                        <?php if ($role == 1): ?>
                            Master - Controle total do sistema
                        <?php elseif ($role == 2): ?>
                            Admin - Gerencie usuários e atribua perfis
                        <?php endif; ?>
                    </p>
                </div>
                <span class="badge-count">
                    <i class="fas fa-users"></i> <?= $total ?? 0 ?> usuário(s)
                </span>
            </div>

            <!-- Ações -->
            <div class="usuarios-actions">
                <?php if (in_array($role, [1, 2])): ?>
                    <a href="<?= $basePath ?>/usuarios/criar" class="btn btn-new">
                        <i class="fas fa-user-plus"></i> Novo Usuário
                    </a>
                <?php endif; ?>
            </div>

            <!-- Lista de Usuários -->
            <div class="usuarios-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Verificado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>#<?= $usuario['id_usuario'] ?></td>
                                <td><strong><?= htmlspecialchars($usuario['nome']) ?></strong></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td>
                                    <span class="status-badge perfil-<?= $usuario['id_perfil'] ?>">
                                        <?= htmlspecialchars($usuario['nome_perfil'] ?? 'Usuario') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $usuario['ativo'] ? 'ativo' : 'inativo' ?>">
                                        <?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $usuario['email_verificado'] ? 'verificado' : 'pendente' ?>">
                                        <?= $usuario['email_verificado'] ? '✅' : '⏳' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (in_array($role, [1, 2])): ?>
                                            <a href="<?= $basePath ?>/usuarios/editar?id=<?= $usuario['id_usuario'] ?>" class="btn btn-sm btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($role == 1): ?>
                                            <a href="<?= $basePath ?>/usuarios/excluir?id=<?= $usuario['id_usuario'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja desativar este usuário?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if (isset($paginationHtml)): ?>
                <div class="pagination-container">
                    <?= $paginationHtml ?>
                </div>
            <?php endif; ?>

            <!-- Voltar -->
            <div class="usuarios-voltar">
                <a href="<?= $basePath ?>/" class="btn">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>