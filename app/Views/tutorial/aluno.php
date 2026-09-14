<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Tutorial
        </div>
        <h1><i class="fas fa-user-graduate"></i> Guia do Aluno</h1>
        <p class="hero-subtitle">Tudo o que você precisa para usar o sistema.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/tutorial">
            <i class="fas fa-arrow-left"></i> Todos os tutoriais
        </a>
    </div>
</section>

<!-- ============================================================ -->
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>1. Entrar em uma turma</h2>
            <p>Como se juntar a uma turma pelo código de acesso.</p>
        </div>
    </div>
    <ol>
        <li>Peça o <strong>código de acesso</strong> para o professor ou representante da turma.</li>
        <li>No menu lateral, clique em <strong>Turmas</strong>.</li>
        <li>Clique em <strong>Entrar em uma turma com código</strong>.</li>
        <li>Digite o código e confirme.</li>
        <li>Pronto — você já verá os projetos, grupos e avaliações da turma.</li>
    </ol>
    <div class="notice notice-blue">
        <span>i</span>
        <div>
            <strong>Dica</strong>
            <p>Você pode estar em várias turmas ao mesmo tempo. Basta repetir o processo para cada código.</p>
        </div>
    </div>
</article>

<!-- ============================================================ -->
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>2. Ver meus projetos</h2>
            <p>Onde acompanhar os projetos das suas turmas.</p>
        </div>
    </div>
    <ol>
        <li>Menu lateral → <strong>Turmas</strong>.</li>
        <li>Clique em <strong>Abrir</strong> na turma desejada.</li>
        <li>Clique em <strong>Ver projetos</strong>.</li>
        <li>Cada projeto tem suas etapas, grupos, critérios e avaliações.</li>
    </ol>
    <p>
        Ao entrar em um projeto, você verá um <strong>menu de abas</strong>:
        Etapas, Grupos, Atas, Critérios, Avaliações e Relatórios.
    </p>
</article>

<!-- ============================================================ -->
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>3. Fazer autoavaliação</h2>
            <p>Quando o representante cria um critério de autoavaliação.</p>
        </div>
    </div>
    <ol>
        <li>Abra o projeto desejado.</li>
        <li>Clique na aba <strong>Avaliações</strong> → <strong>Autoavaliação</strong>.</li>
        <li>Para cada critério, escolha um conceito (<strong>I</strong>, <strong>R</strong>, <strong>B</strong> ou <strong>MB</strong>).</li>
        <li>O valor numérico é preenchido automaticamente; você pode ajustar se quiser.</li>
        <li>Clique em <strong>Salvar</strong>.</li>
    </ol>
</article>

<!-- ============================================================ -->
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>4. Avaliar colegas (avaliação por pares)</h2>
            <p>Quando o critério é do tipo "pares".</p>
        </div>
    </div>
    <ol>
        <li>Abra o projeto → aba <strong>Avaliações</strong> → <strong>Pares</strong>.</li>
        <li>Você verá todos os outros alunos da turma.</li>
        <li>Para cada colega, escolha um conceito e escreva uma <strong>justificativa obrigatória</strong>.</li>
        <li>Clique em <strong>Salvar</strong>.</li>
    </ol>
    <div class="notice notice-blue">
        <span>i</span>
        <div>
            <strong>Sua justificativa é privada</strong>
            <p>Somente o representante e o diretor do grupo conseguem ver o que você escreveu. O colega avaliado não vê.</p>
        </div>
    </div>
</article>

<!-- ============================================================ -->
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>5. Ver minhas notas</h2>
            <p>Seu boletim em tempo real.</p>
        </div>
    </div>
    <ol>
        <li>Abra o projeto.</li>
        <li>Clique em <strong>Avaliações</strong> → <strong>Minhas Notas</strong>.</li>
        <li>Você verá todos os critérios, com peso, sua nota e o conceito.</li>
        <li>No rodapé, a média ponderada e o conceito final.</li>
    </ol>
    <div class="notice notice-blue">
        <span>i</span>
        <div>
            <strong>Boletim congelado</strong>
            <p>Quando o professor (ou representante) encerra o projeto, o boletim é congelado e não muda mais.</p>
        </div>
    </div>
</article>

<!-- ============================================================ -->
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>6. Ver atas em que participei</h2>
            <p>Histórico das reuniões do seu grupo.</p>
        </div>
    </div>
    <ol>
        <li>Abra o projeto → aba <strong>Atas</strong>.</li>
        <li>Você verá apenas as atas em que foi marcado como participante.</li>
        <li>Clique em <strong>Ver</strong> para consultar atividades e sua presença.</li>
    </ol>
</article>

<!-- ============================================================ -->
<article class="panel">
    <div class="panel-header">
        <div>
            <h2>7. Notificações e preferências</h2>
            <p>Fique por dentro sem ser incomodado.</p>
        </div>
    </div>
    <ol>
        <li>Clique no <strong>sino</strong> (canto superior direito) para ver suas notificações.</li>
        <li>Em <strong>Notificações → Preferências</strong>, escolha quais emails deseja receber.</li>
        <li><strong>Alertas urgentes</strong> ignoram suas preferências e sempre chegam.</li>
    </ol>
</article>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>