<?php
// app/Views/usuarios/editar.php

$tituloPagina = 'Editar Usuário - ' . App::getName();
$cssPagina = 'usuarios.css';
$basePath = App::getBasePath();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['role'], [1, 2])) {
    header('Location: ' . $basePath . '/');
    exit;
}

if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    echo '<div class="flash-' . $flash['tipo'] . '">' . htmlspecialchars($flash['mensagem']) . '</div>';
    unset($_SESSION['flash']);
}

if (!isset($usuario) || empty($usuario)) {
    echo '<div class="flash-erro">Usuário não encontrado.</div>';
    echo '<a href="' . $basePath . '/usuarios" class="btn">Voltar</a>';
    require_once __DIR__ . '/../layouts/footer.php';
    exit;
}

$perfis = $this->usuario->getPerfis() ?? [];
?>

<section class="usuarios-section animate-in">
    <div class="container">
        <div class="usuarios-content">

            <!-- Hero -->
            <div class="usuarios-hero">
                <div>
                    <h1><i class="fas fa-user-edit" style="color: var(--color-impact-green);"></i> Editar Usuário</h1>
                    <p class="usuarios-subtitle">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($usuario['nome'] ?? '') ?>
                        <?php if (!empty($usuario['nome_perfil'])): ?>
                            <span class="perfil-badge"><?= htmlspecialchars($usuario['nome_perfil']) ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                <span class="badge-count">
                    <i class="fas fa-hashtag"></i> ID #<?= $usuario['id_usuario'] ?? '' ?>
                </span>
            </div>

            <!-- Formulário -->
            <form action="<?= $basePath ?>/usuarios/atualizar" method="POST" class="usuarios-form">
                <?= ViewHelper::csrfField() ?>
                <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?? '' ?>">
                
                <div class="form-grid">
                    <!-- Nome -->
                    <div class="form-group form-group-full">
                        <label for="nome">Nome completo <span class="required">*</span></label>
                        <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group form-group-full">
                        <label for="email">E-mail <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                    </div>
                    
                    <!-- Telefone -->
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="text" id="telefone" name="telefone" class="form-control" value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>" maxlength="15">
                    </div>
                    
                    <!-- Data de Nascimento -->
                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" value="<?= $usuario['data_nascimento'] ?? '' ?>">
                    </div>
                    
                    <!-- Perfil -->
                    <div class="form-group">
                        <label for="id_perfil">Perfil <span class="required">*</span></label>
                        <select id="id_perfil" name="id_perfil" class="form-control" required>
                            <?php foreach ($perfis as $perfil): ?>
                                <option value="<?= $perfil['id_perfil'] ?>" <?= (isset($usuario['id_perfil']) && $usuario['id_perfil'] == $perfil['id_perfil']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($perfil['perfil']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="ativo" value="1" <?= isset($usuario['ativo']) && $usuario['ativo'] ? 'checked' : '' ?>>
                            Usuário ativo
                        </label>
                    </div>

                    <!-- Senha -->
                    <div class="form-group form-group-full">
                        <label for="senha">Nova Senha</label>
                        <input type="password" id="senha" name="senha" class="form-control" placeholder="Deixe em branco para manter a atual" minlength="6">
                        <span class="form-help">Mínimo 6 caracteres.</span>
                    </div>
                </div>

                <!-- Botões -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-new">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a href="<?= $basePath ?>/usuarios" class="btn">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>

            <!-- Voltar -->
            <div class="usuarios-voltar">
                <a href="<?= $basePath ?>/usuarios" class="btn">
                    <i class="fas fa-arrow-left"></i> Voltar para Usuários
                </a>
            </div>

        </div>
    </div>
</section>

<script>
document.getElementById('telefone').addEventListener('input', function(e) {
    let valor = e.target.value.replace(/\D/g, '');
    if (valor.length > 11) valor = valor.substring(0, 11);
    if (valor.length > 10) {
        valor = valor.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
    } else if (valor.length > 6) {
        valor = valor.replace(/^(\d{2})(\d{4})(\d+)$/, '($1) $2-$3');
    } else if (valor.length > 2) {
        valor = valor.replace(/^(\d{2})(\d+)/, '($1) $2');
    } else if (valor.length > 0) {
        valor = valor.replace(/^(\d+)/, '($1');
    }
    e.target.value = valor;
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>