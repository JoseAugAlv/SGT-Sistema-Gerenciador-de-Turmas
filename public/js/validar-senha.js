/**
 * Valida se a senha é forte (apenas se não estiver vazia)
 */
function validarSenhaCompleta(senha) {
    var erros = [];
    
    // Se a senha estiver vazia, não valida (permite manter a senha atual)
    if (senha === '') {
        return { valida: true, erros: [] };
    }
    
    if (senha.length < 6) {
        erros.push('Mínimo 6 caracteres');
    }
    if (!/[A-Z]/.test(senha)) {
        erros.push('Pelo menos 1 letra maiuscula');
    }
    if (!/[a-z]/.test(senha)) {
        erros.push('Pelo menos 1 letra minuscula');
    }
    if (!/[0-9]/.test(senha)) {
        erros.push('Pelo menos 1 numero');
    }
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(senha)) {
        erros.push('Pelo menos 1 caractere especial (!@#$%^&*)');
    }
    
    return {
        valida: erros.length === 0,
        erros: erros
    };
}

/**
 * Valida o formulário de senha
 */
function validarSenha() {
    var senha = document.getElementById('senha').value;
    var senhaConfirm = document.getElementById('senha_confirm').value;
    
    // Se a senha estiver vazia, não valida (mantém a senha atual)
    if (senha === '' && senhaConfirm === '') {
        return true;
    }
    
    // Validar se as senhas são iguais
    if (senha !== senhaConfirm) {
        alert('As senhas nao coincidem!');
        return false;
    }
    
    // Validar força da senha
    var resultado = validarSenhaCompleta(senha);
    
    if (!resultado.valida) {
        alert('A senha nao atende aos requisitos:\n\n' + resultado.erros.join('\n'));
        return false;
    }
    
    return true;
}

/**
 * Atualiza os requisitos da senha em tempo real
 */
function atualizarRequisitosSenha() {
    var senha = document.getElementById('senha').value;
    var requisitos = document.getElementById('requisitos_senha');
    
    if (!requisitos) return;
    
    // Se a senha estiver vazia, esconde os requisitos
    if (senha === '') {
        requisitos.innerHTML = '';
        return;
    }
    
    var html = '<ul style="list-style:none; padding:0; font-size:13px; margin:5px 0;">';
    
    // Tamanho minimo
    var ok = senha.length >= 6;
    html += '<li style="color:' + (ok ? 'green' : 'red') + ';">' + (ok ? '[OK]' : '[X]') + ' Minimo 6 caracteres</li>';
    
    // Letra maiuscula
    ok = /[A-Z]/.test(senha);
    html += '<li style="color:' + (ok ? 'green' : 'red') + ';">' + (ok ? '[OK]' : '[X]') + ' Pelo menos 1 letra maiuscula</li>';
    
    // Letra minuscula
    ok = /[a-z]/.test(senha);
    html += '<li style="color:' + (ok ? 'green' : 'red') + ';">' + (ok ? '[OK]' : '[X]') + ' Pelo menos 1 letra minuscula</li>';
    
    // Numero
    ok = /[0-9]/.test(senha);
    html += '<li style="color:' + (ok ? 'green' : 'red') + ';">' + (ok ? '[OK]' : '[X]') + ' Pelo menos 1 numero</li>';
    
    // Caractere especial
    ok = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(senha);
    html += '<li style="color:' + (ok ? 'green' : 'red') + ';">' + (ok ? '[OK]' : '[X]') + ' Pelo menos 1 caractere especial (!@#$%^&*)</li>';
    
    html += '</ul>';
    requisitos.innerHTML = html;
}

/**
 * Inicializa a validacao de senha quando o DOM estiver pronto
 */
document.addEventListener('DOMContentLoaded', function() {
    var campoSenha = document.getElementById('senha');
    var campoConfirm = document.getElementById('senha_confirm');
    var form = document.querySelector('form[onsubmit*="validarSenha"]');
    
    // Se o formulario nao tiver onsubmit, adiciona
    if (form && !form.onsubmit) {
        form.onsubmit = validarSenha;
    }
    
    // Adiciona evento keyup para mostrar requisitos em tempo real
    if (campoSenha) {
        campoSenha.addEventListener('keyup', atualizarRequisitosSenha);
        // Executa uma vez para mostrar os requisitos iniciais
        atualizarRequisitosSenha();
    }
});