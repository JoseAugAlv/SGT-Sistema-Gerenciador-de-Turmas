<?php
$tituloPagina = 'Cadastrar Usuário — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="master-section">
    <div class="master-content">

        <header class="master-hero">
            <div>
                <h1><i class="fas fa-user-plus"></i> Cadastrar Usuário</h1>
                <p class="master-subtitle">
                    <i class="fas fa-circle-info"></i>
                    A senha pode ser informada ou gerada automaticamente.
                </p>
            </div>
            <span class="badge-count">
                <i class="fas fa-plus"></i> Novo cadastro
            </span>
        </header>

        <form method="POST" action="<?= $basePath ?>/usuarios/salvar" class="master-box box-primary">
            <?= ViewHelper::csrfField() ?>

            <div class="field-row" style="display:grid; grid-template-columns:1fr; gap:16px;">

                <div class="field-group">
                    <label class="field-label" for="nome">Nome completo *</label>
                    <input type="text" id="nome" name="nome" class="field"
                           maxlength="150" required autofocus
                           placeholder="Ex: João da Silva">
                </div>

                <div class="field-group">
                    <label class="field-label" for="email">Email *</label>
                    <input type="email" id="email" name="email" class="field"
                           required placeholder="usuario@email.com">
                </div>

                <div class="field-group">
                    <label class="field-label" for="tipo">Tipo de acesso *</label>
                    <select id="tipo" name="tipo" class="field" required>
                        <option value="aluno">Aluno</option>
                        <option value="master">Master</option>
                    </select>
                    <small class="field-help">
                        Aluno vê apenas suas turmas. Master tem acesso total.
                    </small>
                </div>

                <div class="field-group">
                    <label class="field-label" for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="field"
                           minlength="8" placeholder="Deixe em branco para gerar automática">
                    <small class="field-help">
                        Mínimo 8 caracteres com maiúscula, minúscula, número e símbolo.
                    </small>
                </div>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn-new">
                    <i class="fas fa-save"></i> Cadastrar usuário
                </button>
                <a class="btn" href="<?= $basePath ?>/usuarios"
                   style="padding:9px 14px; border-radius:9px; border:1px solid var(--border); color:var(--muted); text-decoration:none; font-size:13px; font-weight:700;">
                    Cancelar
                </a>
            </div>
        </form>

    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>