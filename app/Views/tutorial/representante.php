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
        <h1><i class="fas fa-user-tie"></i> Guia do Representante</h1>
        <p class="hero-subtitle">Como coordenar sua turma.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/tutorial">
            <i class="fas fa-arrow-left"></i> Todos os tutoriais
        </a>
    </div>
</section>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>1. Como você vira representante</h2>
        </div>
    </div>
    <p>
        O <strong>master</strong> nomeia até 2 representantes por turma.
        Você não se autonomeia. Depois que você é nomeado, o cargo é seu até que o master o remova
        — mas a turma nunca fica sem representante: o último só sai se outro for nomeado antes.
    </p>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>2. Gerenciar grupos e diretores</h2>
            <p>Criação e organização dos grupos da turma.</p>
        </div>
    </div>
    <ol>
        <li>Abra um projeto → aba <strong>Grupos</strong>.</li>
        <li>Clique em <strong>Novo grupo</strong>. Escolha modo <em>individual</em> ou <em>coletiva</em>.</li>
        <li>Abra o grupo → adicione membros com <strong>Adicionar membro</strong>.</li>
        <li>Clique em <strong>Gerenciar diretores</strong> → escolha um membro para ser diretor.</li>
        <li>Para trocar um diretor: basta remover o antigo e nomear outro. O novo diretor <strong>herda</strong> as atas pendentes e pode editar avaliações antigas.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>3. Criar etapas e critérios</h2>
        </div>
    </div>
    <ol>
        <li><strong>Etapas</strong>: abra o projeto, role até "Etapas", clique em <strong>Adicionar etapa</strong>. Use as setas para reordenar.</li>
        <li><strong>Critérios</strong>: aba <strong>Critérios</strong> → <strong>Novo critério</strong>. Defina nome, peso, tipo de avaliação e prazo.</li>
        <li>O tipo pode ser: <em>diretor, representante, pares, autoavaliação, coletiva</em> ou <em>misto</em>.</li>
        <li>O sistema avisa quando a soma dos pesos passa ou não chega a 10.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>4. Avaliar a turma em massa</h2>
            <p>Avaliação mais rápida e eficiente.</p>
        </div>
    </div>
    <ol>
        <li>Abra o projeto → <strong>Avaliações</strong> → <strong>Representante</strong>.</li>
        <li>Você verá uma tabela com todos os alunos da turma.</li>
        <li>Critérios do tipo <em>representante</em> e <em>pares</em> ficam disponíveis para você lançar.</li>
        <li>Use <strong>Todos MB</strong>, <strong>Todos B</strong> ou valores rápidos para preencher mais rápido.</li>
        <li>Marque <strong>Limpar e Salvar</strong> se quiser apagar tudo que você lançou antes e recomeçar.</li>
        <li>Clique em <strong>Salvar</strong>.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>5. Criar e validar atas</h2>
        </div>
    </div>
    <ol>
        <li>Abra o projeto → aba <strong>Atas</strong> → <strong>Nova ata</strong>.</li>
        <li>Escolha os grupos (pode marcar vários) e envie. Cada grupo recebe uma ata própria.</li>
        <li>Quando um diretor preencher e finalizar, a ata aparece como <strong>aguardando validação</strong>.</li>
        <li>Abra a ata → revise → clique em <strong>Validar</strong> (vira "revisada") ou <strong>Excluir</strong>.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>6. Enviar alertas</h2>
        </div>
    </div>
    <ol>
        <li>Abra a turma → clique em <strong>Alertas</strong> → <strong>Novo alerta</strong>.</li>
        <li>Escolha o <strong>escopo</strong>: turma inteira, um projeto específico ou um grupo específico.</li>
        <li>Marque <strong>Urgente</strong> se quiser que o email chegue mesmo para quem desativou notificações.</li>
        <li>Envie.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>7. Relatórios e boletins</h2>
        </div>
    </div>
    <ol>
        <li>Abra o projeto → aba <strong>Relatórios</strong>.</li>
        <li>Escolha <strong>Relatório Geral do Projeto</strong> para ver todos os alunos × critérios com ranking.</li>
        <li>Exporte em <strong>PDF</strong> ou <strong>Excel</strong>.</li>
        <li>Para ver o boletim de um aluno específico, use <strong>Boletim individual</strong>.</li>
    </ol>
    <div class="notice notice-blue">
        <span>i</span>
        <div>
            <strong>Encerramento congela boletim</strong>
            <p>Ao encerrar um projeto, o boletim de todos os alunos é congelado. Edições posteriores nas avaliações não mudam o boletim.</p>
        </div>
    </div>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>8. Configuração de conceito</h2>
        </div>
    </div>
    <ol>
        <li>Abra o projeto → aba <strong>Conceitos</strong>.</li>
        <li>Ajuste as faixas: <strong>I</strong>, <strong>R</strong>, <strong>B</strong>, <strong>MB</strong>.</li>
        <li>Defina também a <strong>visibilidade de boletins</strong>:
            <ul>
                <li><strong>turma</strong>: diretor/aluno veem qualquer aluno da turma</li>
                <li><strong>grupo</strong>: veem apenas alunos do próprio grupo</li>
                <li><strong>proprio</strong>: veem apenas o próprio boletim</li>
            </ul>
        </li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>9. Materiais</h2>
        </div>
    </div>
    <p>
        Você e os diretores podem cadastrar, usar e comprar materiais em
        <strong>Materiais</strong>. O sistema recalcula o preço médio ponderado
        automaticamente a cada compra.
    </p>
</article>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>