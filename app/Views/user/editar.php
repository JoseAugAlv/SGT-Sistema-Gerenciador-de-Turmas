<?php
$cssPagina = 'user.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$iniciais = '?';
if (!empty($user['nome'])) {
    $partes = preg_split('/\s+/', trim($user['nome']));
    $ini    = strtoupper(substr($partes[0] ?? '', 0, 1));
    if (count($partes) > 1) $ini .= strtoupper(substr(end($partes), 0, 1));
    $iniciais = $ini ?: '?';
}
?>

<section class="user-header">
    <span class="user-avatar-lg"><?= h($iniciais) ?></span>

    <div class="user-header-info">
        <h1>Editar perfil</h1>
        <span class="user-email">
            Atualize seus dados pessoais. O email não pode ser alterado.
        </span>
    </div>

    <div class="user-header-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/user">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/user/atualizar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Nome completo *</label>
    <input type="text" name="nome" class="field" maxlength="150"
           value="<?= h($user['nome']) ?>" required autofocus
           placeholder="Seu nome completo">

    <label class="field-label">Email (não editável)</label>
    <input type="email" class="field" value="<?= h($user['email']) ?>" disabled>
    <small class="field-help">
        Para alterar seu email entre em contato com o administrador.
    </small>

    <div class="field-row">
        <div>
            <label class="field-label">Telefone</label>
            <input type="text" name="telefone" class="field" maxlength="20"
                   value="<?= h($user['telefone'] ?? '') ?>"
                   placeholder="(11) 99999-8888">
        </div>
        <div>
            <label class="field-label">Data de nascimento</label>
            <input type="date" name="data_nascimento" class="field"
                   value="<?= h($user['data_nascimento'] ?? '') ?>">
        </div>
    </div>

    <div class="notice notice-blue mt-16">
        <span>i</span>
        <div>
            <strong>Suas informações ficam protegidas</strong>
            <p class="mb-0">
                Alterações aqui <strong>não afetam</strong> seu histórico de turmas,
                avaliações, atas ou movimentações de materiais.
            </p>
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar alterações
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/user">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>