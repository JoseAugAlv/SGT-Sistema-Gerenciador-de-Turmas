<?php
$cssPagina = 'alunos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$criados = (int) ($resultado['criados'] ?? 0);
$erros   = $resultado['erros'] ?? [];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Resultado
        </div>
        <h1>Resultado da criação em massa</h1>
        <p class="hero-subtitle">
            <?= $criados ?> aluno(s) criado(s) com sucesso
            <?php if (!empty($erros)): ?>
                — <?= count($erros) ?> erro(s) encontrado(s)
            <?php endif; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/alunos/massa">
            <i class="fas fa-layer-group"></i> Criar mais
        </a>
        <a class="button button-primary" href="<?= $basePath ?>/alunos">
            <i class="fas fa-arrow-left"></i> Ver alunos
        </a>
    </div>
</section>

<!-- ============ RESUMO ============ -->
<section class="resultado-resumo">
    <article class="resultado-card sucesso">
        <span class="label">Criados</span>
        <span class="valor"><?= $criados ?></span>
        <small style="color:var(--muted); font-size:11.5px;">
            Email de primeiro acesso enviado
        </small>
    </article>

    <article class="resultado-card <?= !empty($erros) ? 'erro' : '' ?>">
        <span class="label">Erros</span>
        <span class="valor"><?= count($erros) ?></span>
        <small style="color:var(--muted); font-size:11.5px;">
            <?= empty($erros) ? 'Nenhum problema detectado' : 'Veja os detalhes abaixo' ?>
        </small>
    </article>
</section>

<!-- ============ SUCESSO ============ -->
<?php if ($criados > 0): ?>
    <section class="panel mb-16" style="border-left:4px solid var(--green);">
        <div class="panel-header">
            <div>
                <h2>
                    <i class="fas fa-check-circle" style="color:#29946e;"></i>
                    Criados com sucesso
                </h2>
                <p>Os alunos já podem fazer login com as credenciais enviadas por email.</p>
            </div>
        </div>

        <div class="notice notice-blue mt-0">
            <span>i</span>
            <div>
                <strong>Próximos passos</strong>
                <p class="mb-0">
                    Cada aluno recebeu um email com senha temporária e link de confirmação.
                    No primeiro login, ele será guiado a trocar a senha e aceitar os termos LGPD.
                </p>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- ============ ERROS ============ -->
<?php if (!empty($erros)): ?>
    <section class="panel" style="border-left:4px solid var(--red);">
        <div class="panel-header">
            <div>
                <h2>
                    <i class="fas fa-exclamation-triangle" style="color:var(--red);"></i>
                    Erros (<?= count($erros) ?>)
                </h2>
                <p>Estes nomes não foram criados. Veja o motivo de cada um.</p>
            </div>
        </div>

        <ul class="erros-lista">
            <?php foreach ($erros as $e): ?>
                <li>
                    <i class="fas fa-times-circle"></i>
                    <span><?= h($e) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="mt-20 mb-0">
            <a class="button button-secondary" href="<?= $basePath ?>/alunos/massa">
                <i class="fas fa-redo"></i> Tentar novamente
            </a>
            <a class="button button-ghost" href="<?= $basePath ?>/alunos">
                <i class="fas fa-list"></i> Ver alunos existentes
            </a>
        </p>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>