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
        <h1><i class="fas fa-crown"></i> Guia do Diretor</h1>
        <p class="hero-subtitle">Como liderar e avaliar seu grupo.</p>
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
            <h2>1. Encontrar meus grupos</h2>
            <p>Onde ver os grupos que você lidera.</p>
        </div>
    </div>
    <ol>
        <li>Menu lateral → <strong>Turmas</strong> → abra a turma.</li>
        <li>Clique em <strong>Ver projetos</strong> → abra o projeto.</li>
        <li>Clique na aba <strong>Grupos</strong>.</li>
        <li>Os grupos em que você é diretor aparecem com seu nome e uma <strong>coroa</strong>.</li>
    </ol>
    <div class="notice notice-blue">
        <span>i</span>
        <div>
            <strong>Você pode dirigir vários grupos</strong>
            <p>Um mesmo projeto pode ter vários grupos e você pode ser diretor em mais de um — não precisa escolher.</p>
        </div>
    </div>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>2. Avaliar membros do grupo</h2>
            <p>Avaliação por diretor.</p>
        </div>
    </div>
    <ol>
        <li>Abra o projeto → <strong>Avaliações</strong> → <strong>Diretor</strong>.</li>
        <li>Se você dirige mais de um grupo, escolha qual grupo avaliar.</li>
        <li>Você verá uma tabela: cada linha é um membro, cada coluna é um critério.</li>
        <li>Escolha o conceito e o valor será preenchido automaticamente.</li>
        <li>Use os botões de <strong>preenchimento rápido</strong> se quiser aplicar o mesmo conceito a todos.</li>
        <li>Clique em <strong>Salvar</strong>.</li>
    </ol>
    <div class="notice notice-blue">
        <span>i</span>
        <div>
            <strong>Você não pode se autoavaliar</strong>
            <p>Sua própria linha fica bloqueada. Se você for o único diretor do grupo, o representante da turma vai avaliar você automaticamente.</p>
        </div>
    </div>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>3. Preencher atas</h2>
            <p>Registrar as reuniões do grupo.</p>
        </div>
    </div>
    <ol>
        <li>No painel inicial, se houver atas pendentes, elas aparecem em destaque.</li>
        <li>Ou acesse o projeto → aba <strong>Atas</strong> → clique na ata pendente.</li>
        <li><strong>Adicione atividades</strong>: nome, descrição e participantes.</li>
        <li><strong>Adicione relatórios</strong>: tipo (ocorrência, decisão, encaminhamento, observação), tema e conteúdo.</li>
        <li>Clique em <strong>Finalizar ata</strong> quando terminar.</li>
    </ol>
    <p>
        Depois de finalizada, a ata vai para o representante validar.
        Você só pode editar enquanto ela estiver <strong>pendente</strong>.
    </p>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>4. Cadastrar e usar materiais</h2>
            <p>Controle de estoque do projeto.</p>
        </div>
    </div>
    <ol>
        <li>Abra o projeto → aba <strong>Materiais</strong>.</li>
        <li>Clique em <strong>Novo material</strong> e preencha: nome, unidade, quantidade, preço.</li>
        <li>Para retirar, clique em <strong>Usar</strong> e informe a quantidade.</li>
        <li>Para registrar uma compra, clique em <strong>Comprar</strong> — o preço médio é recalculado automaticamente.</li>
        <li>Para ver o histórico completo, clique em <strong>Ver movimentações</strong>.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>5. Ver as notas do meu grupo</h2>
            <p>Como está o desempenho da equipe.</p>
        </div>
    </div>
    <ol>
        <li>Abra o projeto → <strong>Relatórios</strong> → <strong>Relatório Geral do Projeto</strong>.</li>
        <li>Você verá todos os alunos, os critérios e as médias.</li>
        <li>O ranking no final mostra as maiores médias.</li>
    </ol>
    <div class="notice notice-blue">
        <span>i</span>
        <div>
            <strong>Visibilidade de boletins</strong>
            <p>O representante pode configurar se diretores veem notas de toda a turma, apenas do seu grupo, ou apenas as próprias. Verifique com ele.</p>
        </div>
    </div>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>6. Notificações importantes</h2>
        </div>
    </div>
    <ul>
        <li>Nova ata para preencher</li>
        <li>Atas reatribuídas quando você assume um grupo</li>
        <li>Novos alertas da turma</li>
        <li>Prazos próximos de critérios</li>
    </ul>
</article>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>