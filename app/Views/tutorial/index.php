<?php
$cssPagina = 'tutorial.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="tut-hero">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Tutorial
        </div>
        <h1>Aprenda o sistema passo a passo</h1>
        <p>
            Escolha o guia do seu papel ou explore as telas em miniatura com dados fictícios.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-primary" href="<?= $basePath ?>/tutorial/exemplos">
            <i class="fas fa-images"></i> Ver exemplos de telas
        </a>
    </div>
</section>

<!-- ---------- Guias por papel ---------- -->
<section class="tut-cards">
    <article class="tut-card">
        <span class="tut-card-icon purple"><i class="fas fa-user-graduate"></i></span>
        <h2>Guia do Aluno</h2>
        <p>
            Como entrar em uma turma, ver projetos, fazer autoavaliação,
            avaliar colegas e acompanhar suas notas.
        </p>
        <div class="tut-card-foot">
            <span><i class="fas fa-book-open"></i> 7 tópicos</span>
            <a class="button small button-primary" href="<?= $basePath ?>/tutorial/aluno">
                Abrir <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </article>

    <article class="tut-card">
        <span class="tut-card-icon blue"><i class="fas fa-crown"></i></span>
        <h2>Guia do Diretor</h2>
        <p>
            Como preencher atas, avaliar membros do grupo, cadastrar materiais
            e acompanhar o desempenho da equipe.
        </p>
        <div class="tut-card-foot">
            <span><i class="fas fa-book-open"></i> 6 tópicos</span>
            <a class="button small button-primary" href="<?= $basePath ?>/tutorial/diretor">
                Abrir <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </article>

    <article class="tut-card">
        <span class="tut-card-icon green"><i class="fas fa-user-tie"></i></span>
        <h2>Guia do Representante</h2>
        <p>
            Como coordenar grupos, criar critérios, avaliar em massa, validar
            atas, enviar alertas e gerar relatórios.
        </p>
        <div class="tut-card-foot">
            <span><i class="fas fa-book-open"></i> 9 tópicos</span>
            <a class="button small button-primary" href="<?= $basePath ?>/tutorial/representante">
                Abrir <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </article>
</section>

<!-- ---------- Callout de destaque ---------- -->
<div class="tut-callout info">
    <i class="fas fa-circle-info"></i>
    <div>
        <strong>Telas de exemplo disponíveis</strong>
        <p class="mb-0">
            Quer ver como cada tela se parece antes de usar? Acesse a
            <a href="<?= $basePath ?>/tutorial/exemplos" style="color:inherit; font-weight:700; text-decoration:underline;">
                galeria de exemplos
            </a>
            e explore miniaturas com dados fictícios — sem precisar estar logado em uma turma.
        </p>
    </div>
</div>

<!-- ---------- FAQ ---------- -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num"><i class="fas fa-question"></i></span>
        <div>
            <h2>Perguntas frequentes</h2>
        </div>
    </div>

    <details>
        <summary>Posso estar em mais de uma turma ao mesmo tempo?</summary>
        <p class="mb-0">
            Sim. Você pode ser aluno em uma turma, representante em outra e diretor
            de um grupo específico em outra — tudo simultaneamente.
        </p>
    </details>

    <details>
        <summary>Como faço para entrar em uma turma?</summary>
        <p class="mb-0">
            Peça o código de acesso para o professor ou representante, acesse
            <strong>Turmas → Entrar com código</strong> e digite o código.
        </p>
    </details>

    <details>
        <summary>Posso ver as notas dos outros alunos?</summary>
        <p class="mb-0">
            Aluno comum vê apenas as próprias notas. Representantes e diretores
            veem conforme a configuração de visibilidade definida pelo representante.
        </p>
    </details>

    <details>
        <summary>Quando o boletim fica congelado?</summary>
        <p class="mb-0">
            Quando o projeto é encerrado. Depois disso, edições nas avaliações
            não alteram o boletim daquele projeto.
        </p>
    </details>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>