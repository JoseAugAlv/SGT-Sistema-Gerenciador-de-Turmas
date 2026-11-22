<?php
$tituloPagina = 'Editar Usuário — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

if (empty($usuario)) {
    header('Location: ' . $basePath . '/usuarios');
    exit;
}
?>

<section class="master-section">
    <div class="master-content">

        <header class="master-hero">
            <div>
                <h1><i class="fas fa-user-pen"></i> Editar Usuário</h1>
                <p class="master-subtitle">
                    <i class="fas fa-user"></i>
                    <?= h($usuario['nome']) ?>
                    <span class="status-badge perfil-<?= $usuario['tipo'] === 'master' ? '1' : '4' ?>"
                          style="margin-left:8px;">
                        <?= h(ucfirst($usuario['tipo'])) ?>
                    </span>
                </p>
            </div>
            <span class="badge-count">
                <i class="fas fa-hashtag"></i> ID #<?= (int) $usuario['id'] ?>
            </span>
        </header>

        <form method="POST" action="<?= $basePath ?>/usuarios/atualizar" class="master-box box-primary">
            <?= ViewHelper::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">

            <div style="display:grid; grid-template-columns:1fr; gap:16px;">

                <div class="field-group">
                    <label class="field-label" for="nome">Nome completo *</label>
                    <input type="text" id="nome" name="nome" class="field"
                           maxlength="150" required
                           value="<?= h($usuario['nome']) ?>">
                </div>

                <div class="field-group">
                    <label class="field-label">Email (não editável)</label>
                    <input type="email" class="field"
                           value="<?= h($usuario['email']) ?>" disabled>
                </div>

                <div class="field-group">
                    <label class="field-label" for="tipo">Tipo de acesso *</label>
                    <select id="tipo" name="tipo" class="field" required>
                        <option value="aluno"  <?= $usuario['tipo'] === 'aluno'  ? 'selected' : '' ?>>Aluno</option>
                        <option value="master" <?= $usuario['tipo'] === 'master' ? 'selected' : '' ?>>Master</option>
                    </select>
                </div>

                <div class="field-group">
                    <label class="field-label" for="senha">Nova senha (opcional)</label>
                    <input type="password" id="senha" name="senha" class="field"
                           minlength="8" placeholder="Deixe em branco para manter a atual">
                </div>

                <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text);">
                    <input type="checkbox" name="ativo" value="1"
                           <?= !empty($usuario['ativo']) ? 'checked' : '' ?>>
                    <span>Usuário ativo (desmarcar bloqueia o acesso)</span>
                </label>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn-new">
                    <i class="fas fa-save"></i> Salvar alterações
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