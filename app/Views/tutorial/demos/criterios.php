<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Critérios</div>
        <h1>Feira de Ciências 2026</h1>
        <p class="hero-subtitle">4 critério(s)</p>
    </div>
    <div class="hero-actions">
        <a class="button button-primary"><i class="fas fa-plus"></i> Novo critério</a>
    </div>
</section>

<nav class="proj-nav">
    <a><i class="fas fa-list-ol"></i> Etapas</a>
    <a><i class="fas fa-users"></i> Grupos</a>
    <a><i class="fas fa-book"></i> Atas</a>
    <strong><i class="fas fa-clipboard-check"></i> Critérios</strong>
    <a><i class="fas fa-star"></i> Avaliações</a>
    <a><i class="fas fa-sliders"></i> Conceitos</a>
    <a><i class="fas fa-file-alt"></i> Relatórios</a>
</nav>

<div class="crit-resumo">
    <span class="crit-resumo-label">Soma dos pesos:</span>
    <span class="crit-resumo-valor completo">10,00 / 10,00</span>
    <span class="crit-resumo-status completo"><i class="fas fa-check-circle"></i> Completo</span>
</div>

<section class="crit-grid">
    <?php
    $criterios = [
        ['Participação nas reuniões', 'Avaliação de presença e contribuição', 3.00, 'representante', 'todos'],
        ['Pesquisa e desenvolvimento', 'Qualidade da pesquisa entregue', 3.00, 'diretor', 'todos'],
        ['Colaboração com o grupo', 'Avaliação dos colegas', 2.00, 'pares', 'todos'],
        ['Autoavaliação', 'Reflexão sobre o próprio trabalho', 2.00, 'autoavaliacao', 'todos'],
    ];
    foreach ($criterios as $c): ?>
        <article class="panel crit-card">
            <div class="panel-header">
                <div><h2><?= $c[0] ?></h2><p><?= $c[1] ?></p></div>
                <span class="badge badge-green"><i class="fas fa-lock-open"></i> Aberto</span>
            </div>
            <div class="crit-tags">
                <span class="crit-tag tipo-<?= $c[3] ?>"><i class="fas fa-circle-dot"></i> <?= $c[3] ?></span>
            </div>
            <div class="status-line"><strong>Peso:</strong><span><?= number_format($c[2], 2, ',', '.') ?></span></div>
            <div class="status-line"><strong>Prazo:</strong><span>30/11/2026</span></div>
            <div class="status-line"><strong>Avaliações:</strong><span>12</span></div>
            <div class="crit-acoes">
                <button class="button small button-ghost"><i class="fas fa-eye"></i> Detalhes</button>
                <a class="button small button-ghost"><i class="fas fa-pen"></i> Editar</a>
                <button class="button small btn-outline-danger"><i class="fas fa-trash"></i></button>
            </div>
        </article>
    <?php endforeach; ?>
</section>