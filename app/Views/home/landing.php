<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section>
    <h1><?= h($appName) ?></h1>
    <p>Organize turmas, projetos, grupos e avaliações em um só lugar.
       Simples para o aluno, poderoso para o professor.</p>

    <p>
        <a href="<?= $basePath ?>/login/cadastrar">
            <i class="fas fa-user-plus"></i> Criar conta
        </a>
        —
        <a href="<?= $basePath ?>/login">
            <i class="fas fa-right-to-bracket"></i> Entrar
        </a>
    </p>
</section>

<hr>

<section>
    <h2>Por que usar o <?= h($appName) ?>?</h2>

    <table border="1" cellpadding="8">
        <tr>
            <td>
                <i class="fas fa-people-group"></i>
                <h3>Grupos e avaliações</h3>
                <p>Cada grupo com seus diretores. Avaliação por diretor, pares,
                   autoavaliação ou coletiva — com média ponderada automática.</p>
            </td>
            <td>
                <i class="fas fa-book"></i>
                <h3>Atas e relatórios</h3>
                <p>Registre reuniões com atividades, participantes e relatórios.
                   Fluxo claro entre diretor e representante.</p>
            </td>
            <td>
                <i class="fas fa-chart-line"></i>
                <h3>Boletim em tempo real</h3>
                <p>Notas calculadas na hora. Ao encerrar o projeto, o boletim
                   é congelado — sem surpresas no final.</p>
            </td>
        </tr>
    </table>
</section>

<hr>

<section>
    <h2>Como funciona</h2>

    <ol>
        <li>Crie sua conta e confirme o email.</li>
        <li>Entre na turma usando o código de acesso.</li>
        <li>Pronto — você já vê projetos, grupos, avaliações e alertas.</li>
    </ol>
</section>

<hr>

<section>
    <h2>Perguntas frequentes</h2>

    <details>
        <summary>Posso estar em várias turmas?</summary>
        <p>Sim. Você pode ser aluno em uma turma, representante em outra e
           diretor de um grupo específico em outra — ao mesmo tempo.</p>
    </details>

    <details>
        <summary>O boletim muda após encerrar o projeto?</summary>
        <p>Não. Ao encerrar, o boletim é congelado. Edições posteriores nas
           avaliações não alteram o boletim.</p>
    </details>

    <details>
        <summary>Como funciona a avaliação por pares?</summary>
        <p>Cada aluno avalia os colegas com conceito e justificativa.
           A justificativa é interna — só representante e diretor veem.</p>
    </details>
</section>

<hr>

<section>
    <h2>Pronto para começar?</h2>
    <p>
        <a href="<?= $basePath ?>/login/cadastrar">
            <i class="fas fa-user-plus"></i> Criar conta
        </a>
    </p>
    <p>
        Ao criar conta você aceita os
        <a href="<?= $basePath ?>/termos">Termos</a> e a
        <a href="<?= $basePath ?>/lgpd">Política de Privacidade</a>.
    </p>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>