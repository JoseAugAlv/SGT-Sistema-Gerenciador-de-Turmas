<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Preferências de Notificação</h1>

<form method="POST" action="<?= $basePath ?>/notificacoes/preferencias/salvar">
    <?= ViewHelper::csrfField() ?>

    <p>
        <label>
            <input type="checkbox" name="receber_email" value="1"
                   <?= !empty($prefs['receber_email']) ? 'checked' : '' ?>>
            Receber emails gerais
        </label>
    </p>

    <p>
        <label>
            <input type="checkbox" name="receber_email_prazos" value="1"
                   <?= !empty($prefs['receber_email_prazos']) ? 'checked' : '' ?>>
            Receber emails sobre prazos
        </label>
    </p>

    <p>
        <label>
            <input type="checkbox" name="receber_email_alertas" value="1"
                   <?= !empty($prefs['receber_email_alertas']) ? 'checked' : '' ?>>
            Receber emails de alertas
        </label>
    </p>

    <p>
        <label>
            <input type="checkbox" name="receber_email_avaliacoes" value="1"
                   <?= !empty($prefs['receber_email_avaliacoes']) ? 'checked' : '' ?>>
            Receber emails de avaliações
        </label>
    </p>

    <p>
        <em>Alertas urgentes ignoram essas preferências e sempre enviam email.</em>
    </p>

    <p>
        <button type="submit"><i class="fas fa-save"></i> Salvar</button>
        <a href="<?= $basePath ?>/notificacoes">Voltar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>