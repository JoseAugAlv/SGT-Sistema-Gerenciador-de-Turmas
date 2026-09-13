<?php
// app/Views/usuarios/criar.php

$tituloPagina = 'Cadastrar Usuário - ' . App::getName();
$cssPagina = 'usuarios.css';
$basePath = App::getBasePath();
$appName = App::getName();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['role'], [1, 2])) {
    header('Location: ' . $basePath . '/');
    exit;
}

if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    echo '<div class="flash-' . $flash['tipo'] . '">' . htmlspecialchars($flash['mensagem']) . '</div>';
    unset($_SESSION['flash']);
}

$perfis = $this->usuario->getPerfis() ?? [];
?>

<section class="usuarios-section animate-in">
    <div class="container">
        <div class="usuarios-content">

            <!-- Hero -->
            <div class="usuarios-hero">
                <div>
                    <h1><i class="fas fa-user-plus" style="color: var(--color-impact-green);"></i> Cadastrar Usuário</h1>
                    <p class="usuarios-subtitle">
                        <i class="fas fa-info-circle"></i> Preencha os dados para criar um novo usuário
                    </p>
                </div>
                <span class="badge-count">
                    <i class="fas fa-users"></i> Novo cadastro
                </span>
            </div>

            <!-- Formulário -->
            <form action="<?= $basePath ?>/usuarios/salvar" method="POST" class="usuarios-form" onsubmit="return validarFormulario()">
                <?= ViewHelper::csrfField() ?>
                
                <div class="form-grid">
                    <!-- Nome -->
                    <div class="form-group form-group-full">
                        <label for="nome">Nome do Usuário <span class="required">*</span></label>
                        <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: João Silva" required>
                        <span class="form-help">Nome completo do usuário.</span>
                    </div>

                    <!-- Email -->
                    <div class="form-group form-group-full">
                        <label for="email">E-mail <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="usuario@email.com" required>
                        <span class="form-help">E-mail válido do usuário.</span>
                    </div>

                    <!-- Telefone -->
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="text" id="telefone" name="telefone" class="form-control" placeholder="(11) 99999-8888" maxlength="15">
                    </div>

                    <!-- Data de Nascimento -->
                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control">
                    </div>

                    <!-- Perfil -->
                    <div class="form-group">
                        <label for="id_perfil">Perfil <span class="required">*</span></label>
                        <select id="id_perfil" name="id_perfil" class="form-control" required>
                            <option value="">Selecione</option>
                            <?php foreach ($perfis as $perfil): ?>
                                <option value="<?= $perfil['id_perfil'] ?>">
                                    <?= htmlspecialchars($perfil['perfil']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Senha -->
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" class="form-control" placeholder="Deixe em branco para gerar automática" minlength="6">
                        <span class="form-help">Mínimo 6 caracteres.</span>
                    </div>
                </div>

                <!-- Botões -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-new">
                        <i class="fas fa-save"></i> Cadastrar Usuário
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
document.addEventListener('DOMContentLoaded', function() {
    // Máscara de telefone
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
});

function validarFormulario() {
    var nome = document.getElementById('nome').value.trim();
    if (nome.length < 3) {
        Swal.fire({ icon: 'error', title: 'Nome inválido!', text: 'Digite o nome completo (mínimo 3 caracteres).', confirmButtonColor: '#dc3545' });
        return false;
    }
    
    var email = document.getElementById('email').value.trim();
    if (!email) {
        Swal.fire({ icon: 'error', title: 'E-mail obrigatório!', text: 'Digite um e-mail válido.', confirmButtonColor: '#dc3545' });
        return false;
    }
    
    var idPerfil = document.getElementById('id_perfil').value;
    if (!idPerfil) {
        Swal.fire({ icon: 'error', title: 'Perfil não selecionado!', text: 'Selecione um perfil para o usuário.', confirmButtonColor: '#dc3545' });
        return false;
    }
    
    return true;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>