<?php
$cssPagina = 'notificacoes.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Garante que as chaves existam
$prefs = array_merge([
    'receber_email'           => 0,
    'receber_email_prazos'    => 0,
    'receber_email_alertas'   => 0,
    'receber_email_avaliacoes'=> 0,
], $prefs ?? []);
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Preferências
        </div>
        <h1>Preferências de notificação</h1>
        <p class="hero-subtitle">
            Escolha quais emails você quer receber.
            Você sempre verá as notificações dentro do sistema.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/notificacoes">
            <i class="fas fa-arrow-left"></i> Voltar às notificações
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/notificacoes/preferencias/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <div class="panel-header">
        <div>
            <h2>Emails que deseja receber</h2>
            <p>Desative categorias específicas ou todos os emails.</p>
        </div>
    </div>

    <div class="pref-grid">
        <label class="pref-item">
            <input type="checkbox" name="receber_email" value="1"
                   <?= !empty($prefs['receber_email']) ? 'checked' : '' ?>>
            <div class="pref-item-info">
                <strong>Receber emails gerais</strong>
                <small>
                    Chave mestra. Se desativado, você não receberá nenhum email
                    do sistema — apenas notificações internas.
                </small>
            </div>
        </label>

        <label class="pref-item">
            <input type="checkbox" name="receber_email_prazos" value="1"
                   <?= !empty($prefs['receber_email_prazos']) ? 'checked' : '' ?>>
            <div class="pref-item-info">
                <strong>Prazos de avaliação</strong>
                <small>
                    Avisos quando um critério estiver próximo do vencimento
                    (até 3 dias antes).
                </small>
            </div>
        </label>

        <label class="pref-item">
            <input type="checkbox" name="receber_email_alertas" value="1"
                   <?= !empty($prefs['receber_email_alertas']) ? 'checked' : '' ?>>
            <div class="pref-item-info">
                <strong>Alertas da turma</strong>
                <small>
                    Avisos enviados pelo representante ou master para a turma,
                    projetos ou grupos específicos.
                </small>
            </div>
        </label>

        <label class="pref-item">
            <input type="checkbox" name="receber_email_avaliacoes" value="1"
                   <?= !empty($prefs['receber_email_avaliacoes']) ? 'checked' : '' ?>>
            <div class="pref-item-info">
                <strong>Novas avaliações</strong>
                <small>
                    Avisos quando você receber uma avaliação em algum critério.
                </small>
            </div>
        </label>
    </div>

    <div class="notice notice-blue mt-0">
        <span>i</span>
        <div>
            <strong>Alertas urgentes sempre chegam</strong>
            <p class="mb-0">
                Alertas marcados como <strong>urgentes</strong> pelo representante ou master
                ignoram essas preferências e são enviados para todos os destinatários,
                mesmo que o email esteja desativado.
            </p>
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar preferências
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/notificacoes">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>