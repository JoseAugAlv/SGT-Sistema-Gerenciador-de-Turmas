<?php
$cssPagina = 'user.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="user-header">
    <span class="user-avatar-lg">
        <i class="fas fa-key" style="font-size:28px;"></i>
    </span>

    <div class="user-header-info">
        <h1>Alterar senha</h1>
        <span class="user-email">
            Por segurança, informe sua senha atual antes de escolher uma nova.
        </span>
    </div>

    <div class="user-header-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/user">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/user/senha" class="panel" id="form-senha">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Senha atual *</label>
    <div class="auth-password-wrap">
        <input type="password" name="senha_atual" class="field" required autofocus
               autocomplete="current-password" placeholder="Sua senha atual">
        <button type="button" class="auth-password-toggle" aria-label="Mostrar senha">
            <i class="fas fa-eye"></i>
        </button>
    </div>

    <label class="field-label mt-16">Nova senha *</label>
    <div class="auth-password-wrap">
        <input type="password" name="senha_nova" id="nova-senha" class="field"
               minlength="8" required autocomplete="new-password"
               placeholder="Mínimo 8 caracteres">
        <button type="button" class="auth-password-toggle" aria-label="Mostrar senha">
            <i class="fas fa-eye"></i>
        </button>
    </div>

    <!-- Requisitos em tempo real -->
    <div class="senha-requisitos" style="margin-top:12px;">
        <div class="titulo-req">A nova senha precisa ter</div>
        <ul>
            <li data-req="len"><i class="fas fa-circle"></i> Mínimo 8 caracteres</li>
            <li data-req="upper"><i class="fas fa-circle"></i> Pelo menos 1 letra maiúscula</li>
            <li data-req="lower"><i class="fas fa-circle"></i> Pelo menos 1 letra minúscula</li>
            <li data-req="num"><i class="fas fa-circle"></i> Pelo menos 1 número</li>
            <li data-req="special"><i class="fas fa-circle"></i> Pelo menos 1 caractere especial (!@#$%…)</li>
        </ul>

        <div class="senha-forca">
            <div class="senha-forca-barra" id="forca-barra">
                <span></span><span></span><span></span><span></span>
            </div>
            <span class="senha-forca-label" id="forca-label">Aguardando...</span>
        </div>
    </div>

    <label class="field-label mt-16">Confirme a nova senha *</label>
    <div class="auth-password-wrap">
        <input type="password" name="senha_confirmacao" id="confirma-senha" class="field"
               minlength="8" required autocomplete="new-password"
               placeholder="Repita a nova senha">
        <button type="button" class="auth-password-toggle" aria-label="Mostrar senha">
            <i class="fas fa-eye"></i>
        </button>
    </div>
    <small class="field-help" id="match-msg"></small>

    <div class="notice notice-warning mt-16">
        <span>!</span>
        <div>
            <strong>Ao alterar a senha, sua sessão atual continua ativa</strong>
            <p class="mb-0">
                Se você não está reconhecendo este acesso, altere a senha e verifique
                sua caixa de e-mail. Um aviso de alteração é enviado automaticamente.
            </p>
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary" id="btn-salvar-senha" disabled>
            <i class="fas fa-check"></i> Alterar senha
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/user">
            Cancelar
        </a>
    </p>
</form>

<script>
(function () {
    'use strict';

    // ---------- Toggle de senha ----------
    document.querySelectorAll('.auth-password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = this.parentElement.querySelector('input');
            if (!input) return;
            var isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            var icon = this.querySelector('i');
            if (icon) icon.className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    });

    // ---------- Validação em tempo real ----------
    var novaSenha  = document.getElementById('nova-senha');
    var confirma   = document.getElementById('confirma-senha');
    var btnSalvar  = document.getElementById('btn-salvar-senha');
    var matchMsg   = document.getElementById('match-msg');

    var regras = {
        len:     function (s) { return s.length >= 8; },
        upper:   function (s) { return /[A-Z]/.test(s); },
        lower:   function (s) { return /[a-z]/.test(s); },
        num:     function (s) { return /[0-9]/.test(s); },
        special: function (s) { return /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(s); },
    };

    function avaliar() {
        var v = novaSenha.value;

        var pontos = 0;
        Object.keys(regras).forEach(function (chave) {
            var li = document.querySelector('[data-req="' + chave + '"]');
            var ok = regras[chave](v);
            if (ok) { pontos++; li.classList.add('ok'); }
            else    { li.classList.remove('ok'); }
        });

        // Barra de força
        var nivel = 0;
        if (v.length > 0) {
            if (pontos <= 2) nivel = 1;
            else if (pontos === 3) nivel = 2;
            else if (pontos === 4) nivel = 3;
            else nivel = 4;
        }
        var labels = ['Aguardando...', 'Fraca', 'Razoável', 'Boa', 'Forte'];
        var label  = document.getElementById('forca-label');
        label.textContent = labels[nivel];
        label.className = 'senha-forca-label nivel-' + nivel;

        var barras = document.querySelectorAll('#forca-barra span');
        barras.forEach(function (b, i) {
            b.className = (i < nivel) ? ('ativo-' + nivel) : '';
        });

        // Confirmação
        var confere = (v !== '' && confirma.value !== '' && v === confirma.value);
        if (confirma.value === '') {
            matchMsg.textContent = '';
            matchMsg.style.color = '';
        } else if (confere) {
            matchMsg.textContent = 'As senhas coincidem.';
            matchMsg.style.color = '#29946e';
        } else {
            matchMsg.textContent = 'As senhas não coincidem.';
            matchMsg.style.color = 'var(--red)';
        }

        // Habilita botão só quando tudo ok
        btnSalvar.disabled = !(pontos === 5 && confere);
    }

    novaSenha.addEventListener('input', avaliar);
    confirma.addEventListener('input', avaliar);

    // Executa ao carregar (para caso o navegador tenha autofill)
    avaliar();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>