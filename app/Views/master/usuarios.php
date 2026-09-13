<?php
// app/Views/master/usuarios.php

$tituloPagina = 'Master Usuários - ' . App::getName();
$cssPagina = 'master.css';
$basePath = App::getBasePath();
$appName = App::getName();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] != 1) {
    header('Location: ' . $basePath . '/');
    exit;
}

if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    echo '<div class="flash-' . $flash['tipo'] . '">' . htmlspecialchars($flash['mensagem']) . '</div>';
    unset($_SESSION['flash']);
}

$pdo = Database::getConnection();
$perfis = $pdo->query("SELECT * FROM perfil ORDER BY id_perfil")->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="master-section animate-in">
    <div class="container">
        <div class="master-content">

            <!-- Hero -->
            <div class="master-hero">
                <div>
                    <h1><i class="fas fa-users-cog" style="color: #2563eb;"></i> Gerenciar Usuários</h1>
                    <p class="master-subtitle">
                        <i class="fas fa-user-shield"></i> Visualize e atribua perfis a todos os usuários do sistema
                    </p>
                </div>
                <span class="badge-count">
                    <i class="fas fa-users"></i> <?= count($usuarios ?? []) ?> usuário(s)
                </span>
            </div>

            <!-- Ações -->
            <div class="master-box box-primary">
                <div class="box-actions">
                    <a href="<?= $basePath ?>/usuarios/criar" class="btn btn-new">
                        <i class="fas fa-user-plus"></i> Cadastrar Usuário
                    </a>
                    <span class="box-info-text">
                        <i class="fas fa-info-circle"></i> Atribua perfis aos usuários
                    </span>
                </div>
            </div>

            <!-- Lista de Usuários -->
            <?php if (empty($usuarios)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>Nenhum usuário cadastrado</h3>
                    <p>Comece cadastrando o primeiro usuário do sistema.</p>
                    <a href="<?= $basePath ?>/usuarios/criar" class="btn btn-new">
                        <i class="fas fa-user-plus"></i> Cadastrar Usuário
                    </a>
                </div>
            <?php else: ?>
                <div class="master-table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Perfil</th>
                                <th>Status</th>
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
                                        <span class="status-badge perfil-<?= $usuario['id_perfil'] ?? 4 ?>">
                                            <?= htmlspecialchars($usuario['nome_perfil'] ?? 'Usuario') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $usuario['ativo'] ? 'ativo' : 'inativo' ?>">
                                            <?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="abrirModalAtribuir(<?= $usuario['id_usuario'] ?>, '<?= addslashes($usuario['nome']) ?>', '<?= $usuario['id_perfil'] ?? 4 ?>')" class="btn btn-sm btn-primary">
                                            <i class="fas fa-user-tag"></i> Atribuir Perfil
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="master-info">
                    <i class="fas fa-info-circle"></i> 
                    Total de usuários: <strong><?= count($usuarios) ?></strong>
                    <?php 
                    $ativos = array_filter($usuarios, function($u) { return $u['ativo'] == 1; });
                    ?>
                    | Ativos: <strong><?= count($ativos) ?></strong>
                    | Inativos: <strong><?= count($usuarios) - count($ativos) ?></strong>
                </div>
            <?php endif; ?>

            <!-- Voltar -->
            <div class="master-voltar">
                <a href="<?= $basePath ?>/master" class="btn">
                    <i class="fas fa-arrow-left"></i> Voltar para Área Master
                </a>
            </div>

        </div>
    </div>
</section>

<!-- Modal para atribuir perfil -->
<div id="modalAtribuir" class="master-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h2><i class="fas fa-user-tag" style="color: #2563eb;"></i> Atribuir Perfil</h2>
            <button onclick="fecharModal()" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p><strong>Usuário:</strong> <span id="modalUsuarioNome"></span></p>
            <form action="<?= $basePath ?>/usuarios/atribuir-perfil" method="POST">
                <?= ViewHelper::csrfField() ?>
                <input type="hidden" id="modalIdUsuario" name="id_usuario">
                
                <div class="form-group">
                    <label for="modalIdPerfil">Perfil</label>
                    <select id="modalIdPerfil" name="id_perfil" class="form-control" required>
                        <option value="">Selecione</option>
                        <?php foreach ($perfis as $perfil): ?>
                            <option value="<?= $perfil['id_perfil'] ?>">
                                <?= htmlspecialchars($perfil['perfil']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-new">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                    <button type="button" onclick="fecharModal()" class="btn">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalAtribuir(id, nome, idPerfil) {
    document.getElementById('modalIdUsuario').value = id;
    document.getElementById('modalUsuarioNome').textContent = nome;
    document.getElementById('modalIdPerfil').value = idPerfil || 4;
    document.getElementById('modalAtribuir').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function fecharModal() {
    document.getElementById('modalAtribuir').classList.remove('active');
    document.body.style.overflow = 'auto';
}

document.getElementById('modalAtribuir').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModal();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>