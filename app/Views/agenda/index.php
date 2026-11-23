<?php
// app/Views/agenda/index.php
// Página da agenda (também na barra inferior do celular): eventos das turmas e pessoais.
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$semDia = $ctx['semDia'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Agenda</div>
        <h1>Agenda</h1>
        <p class="hero-subtitle">Provas, entregas, tarefas e reuniões das suas turmas, mais o que você anotar só para você.</p>
    </div>
    <div class="hero-actions agenda-atalhos" aria-hidden="true">
        <small class="txt-mudo"><kbd>N</kbd> novo · <kbd>T</kbd> hoje · <kbd>←</kbd><kbd>→</kbd> navegar · <kbd>M</kbd>/<kbd>S</kbd>/<kbd>L</kbd> visão</small>
    </div>
</section>

<?php require __DIR__ . '/_componente.php'; ?>

<?php if ($semDia && ($ctx['podePessoal'] || $ctx['turmasDia'])): ?>
    <p class="field-help mt-16"><i class="fas fa-circle-info"></i>
        <?= count($semDia) === 1 ? 'A turma ' . h($semDia[0]['nome']) . ' não aparece aqui porque o plano dela' : count($semDia) . ' turmas não aparecem aqui porque o plano delas' ?>
        não inclui o Dia a dia (incluso a partir do Anual).</p>
<?php endif; ?>

<style>
.agenda-atalhos kbd { display: inline-block; min-width: 18px; padding: 0 4px; border: 1px solid var(--border); border-bottom-width: 2px; border-radius: 4px; font: 11px/16px inherit; text-align: center; background: var(--surface); }
@media (max-width: 760px) { .agenda-atalhos { display: none; } }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
