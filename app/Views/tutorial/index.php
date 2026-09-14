<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Guia do sistema
        </div>
        <h1>Tutorial</h1>
        <p class="hero-subtitle">
            Aprenda a usar o <?= h($appName) ?> passo a passo.
        </p>
    </div>
</section>

<section class="section-heading">
    <div>
        <h2>Tutoriais disponíveis</h2>
        <p>Escolha o tutorial do seu papel no sistema.</p>
    </div>
</section>

<section class="component-grid">

    <!-- ============ ALUNO (sempre visível) ============ -->
    <article class="panel">
        <div class="panel-header">
            <div>
                <h2><i class="fas fa-user-graduate"></i> Aluno</h2>
                <p>Para quem participa das turmas, projetos e avaliações.</p>
            </div>
        </div>

        <ul>
            <li>Como entrar em uma turma com código</li>
            <li>Ver meus projetos e grupos</li>
            <li>Fazer autoavaliação</li>
            <li>Avaliar colegas (avaliação por pares)</li>
            <li>Ver minhas notas</li>
            <li>Ver atas em que participei</li>
            <li>Notificações e preferências</li>
        </ul>

        <a class="button button-primary full-width" href="<?= $basePath ?>/tutorial/aluno">
            <i class="fas fa-book-open"></i> Abrir tutorial
        </a>
    </article>

    <!-- ============ DIRETOR (só se for diretor) ============ -->
    <?php if ($isDiretor): ?>
        <article class="panel">
            <div class="panel-header">
                <div>
                    <h2><i class="fas fa-crown"></i> Diretor</h2>
                    <p>Para quem lidera um grupo dentro de um projeto.</p>
                </div>
            </div>

            <ul>
                <li>Ver meu grupo e meus colegas</li>
                <li>Avaliar os membros do grupo</li>
                <li>Preencher atas (atividades + relatórios)</li>
                <li>Cadastrar e usar materiais</li>
                <li>Ver notas do grupo</li>
                <li>Notificações e pendências</li>
            </ul>

            <a class="button button-primary full-width" href="<?= $basePath ?>/tutorial/diretor">
                <i class="fas fa-book-open"></i> Abrir tutorial
            </a>
        </article>
    <?php endif; ?>

    <!-- ============ REPRESENTANTE (só se for rep) ============ -->
    <?php if ($isRep): ?>
        <article class="panel">
            <div class="panel-header">
                <div>
                    <h2><i class="fas fa-user-tie"></i> Representante</h2>
                    <p>Para quem representa a turma e coordena grupos.</p>
                </div>
            </div>

            <ul>
                <li>Gerenciar grupos e diretores</li>
                <li>Criar e editar etapas, critérios e conceitos</li>
                <li>Avaliar a turma em massa</li>
                <li>Criar atas e validar as preenchidas</li>
                <li>Enviar alertas</li>
                <li>Gerar relatórios e boletins</li>
                <li>Cadastrar e controlar materiais</li>
            </ul>

            <a class="button button-primary full-width" href="<?= $basePath ?>/tutorial/representante">
                <i class="fas fa-book-open"></i> Abrir tutorial
            </a>
        </article>
    <?php endif; ?>

    <!-- ============ MASTER (só se for master) ============ -->
    <?php if ($isMaster): ?>
        <article class="panel">
            <div class="panel-header">
                <div>
                    <h2><i class="fas fa-shield-halved"></i> Master</h2>
                    <p>Para o administrador global do sistema.</p>
                </div>
            </div>

            <ul>
                <li>Criar turmas e cursos/períodos</li>
                <li>Bloquear e desbloquear turmas</li>
                <li>Nomear e remover representantes</li>
                <li>Gerenciar critérios arquivados</li>
                <li>Auditoria e backup</li>
                <li>Aprovar/negar solicitações LGPD</li>
            </ul>

            <a class="button button-primary full-width" href="<?= $basePath ?>/tutorial/master">
                <i class="fas fa-book-open"></i> Abrir tutorial
            </a>
        </article>
    <?php endif; ?>

</section>

<!-- ============ FAQ (só para logado) ============ -->
<?php if (!empty($_SESSION['usuario'])): ?>
    <section class="panel" style="margin-top:20px;">
        <div class="panel-header">
            <div>
                <h2><i class="fas fa-circle-question"></i> Perguntas frequentes</h2>
            </div>
        </div>

        <details>
            <summary>Posso estar em mais de uma turma ao mesmo tempo?</summary>
            <p>Sim. Você pode ser aluno em uma turma, representante em outra e diretor de um grupo específico em outra — tudo simultaneamente.</p>
        </details>

        <details>
            <summary>Como faço para entrar em uma turma?</summary>
            <p>Peça o código de acesso para o professor ou representante, acesse <strong>Turmas → Entrar com código</strong> e digite o código.</p>
        </details>

        <details>
            <summary>Posso ver as notas dos outros alunos?</summary>
            <p>Aluno comum vê apenas as próprias notas. Representantes, diretores e master veem conforme configuração de visibilidade definida pelo representante em <strong>Configuração de Conceito</strong>.</p>
        </details>

        <details>
            <summary>Quando é que o boletim fica congelado?</summary>
            <p>Quando o projeto é encerrado. Depois disso, edições nas avaliações não alteram o boletim daquele projeto.</p>
        </details>

        <details>
            <summary>O que é avaliação por pares?</summary>
            <p>Cada aluno avalia os colegas da turma com conceito e justificativa. A justificativa é interna — apenas representante e diretor veem.</p>
        </details>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>