<?php
$cssPagina = ['projetos.css', 'relatorios.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Relatórios
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
            — escolha o relatório que deseja visualizar.
        </p>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<section class="relatorios-grid">
    <article class="panel relatorio-card">
        <div class="panel-header">
            <div>
                <h2><i class="fas fa-table"></i> Relatório Geral do Projeto</h2>
                <p>
                    Matriz alunos × critérios com notas, média ponderada,
                    conceito final e ranking.
                </p>
            </div>
        </div>

        <div class="status-line">
            <strong>Todos os alunos</strong>
            <span>da turma</span>
        </div>

        <a class="button button-primary full-width"
           href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/geral">
            <i class="fas fa-arrow-right"></i> Abrir relatório geral
        </a>
    </article>

    <?php if (!empty($meuBoletim)): ?>
        <article class="panel relatorio-card">
            <div class="panel-header">
                <div>
                    <h2><i class="fas fa-file-lines"></i> Meu Boletim</h2>
                    <p>
                        Seu desempenho individual por critério, com peso,
                        nota e conceito final.
                    </p>
                </div>
            </div>

            <div class="status-line">
                <strong>Individual</strong>
                <span>apenas o seu boletim</span>
            </div>

            <a class="button button-primary full-width"
               href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/boletim">
                <i class="fas fa-arrow-right"></i> Ver meu boletim
            </a>
        </article>
    <?php endif; ?>
</section>

<div class="notice notice-blue">
    <span>i</span>
    <div>
        <strong>Sobre os relatórios</strong>
        <p class="mb-0">
            O <strong>relatório geral</strong> é visível apenas para representantes e master.
            O <strong>boletim individual</strong> é visível para você, para o representante,
            para o diretor do seu grupo (conforme configuração de visibilidade) e para o master.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>