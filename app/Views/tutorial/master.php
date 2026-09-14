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
        <h1><i class="fas fa-shield-halved"></i> Guia do Master</h1>
        <p class="hero-subtitle">Administração global do sistema.</p>
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
            <h2>1. Criar e configurar turmas</h2>
        </div>
    </div>
    <ol>
        <li>Menu lateral → <strong>Turmas</strong> → <strong>Nova turma</strong>.</li>
        <li>Informe código da escola, ano/módulo, curso, período e um código de acesso.</li>
        <li>O nome da turma é montado automaticamente: <code>{escola}-{ano}-{curso}-{período}</code>.</li>
        <li>Antes de criar, cadastre cursos e períodos em <strong>Cursos e Períodos</strong>.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>2. Nomear representantes</h2>
        </div>
    </div>
    <ol>
        <li>Abra a turma → <strong>Gerenciar representantes</strong>.</li>
        <li>Escolha um aluno entre os ativos e clique em <strong>Nomear</strong>.</li>
        <li>Máximo de <strong>2 representantes</strong> por turma.</li>
        <li>Não é possível remover o último representante sem nomear outro antes.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>3. Bloquear e desbloquear turmas</h2>
        </div>
    </div>
    <p>
        Ao bloquear uma turma, qualquer usuário não-master que tentar acessá-la
        vê a tela "Turma bloqueada". Útil para questões administrativas ou
        inadimplência.
    </p>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>4. Criar projetos</h2>
        </div>
    </div>
    <ol>
        <li>Abra a turma → <strong>Ver projetos</strong> → <strong>Novo projeto</strong>.</li>
        <li>Escolha modo <em>etapa</em> (critérios podem ser vinculados a etapas) ou <em>cronograma</em> (etapas apenas para controle).</li>
        <li>Depois, o representante da turma se encarrega das etapas, grupos e critérios.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>5. Critérios arquivados</h2>
        </div>
    </div>
    <ol>
        <li>Menu lateral → <strong>Master → Critérios arquivados</strong>.</li>
        <li>Critérios excluídos com avaliações ficam disponíveis por <strong>30 dias</strong>.</li>
        <li>Clique em <strong>Restaurar</strong> para trazê-los de volta com todas as avaliações originais.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>6. Auditoria</h2>
        </div>
    </div>
    <ol>
        <li>Menu lateral → <strong>Master → Auditoria</strong>.</li>
        <li>Filtre por usuário, ação, tabela ou período.</li>
        <li>Clique em <strong>Detalhe</strong> para ver os dados antes/depois da alteração.</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>7. Backup</h2>
        </div>
    </div>
    <ol>
        <li>Menu lateral → <strong>Master → Backup</strong>.</li>
        <li>Clique em <strong>Gerar novo backup</strong>. O sistema usa o <code>mysqldump</code> do XAMPP.</li>
        <li>Baixe ou exclua backups antigos pela lista.</li>
    </ol>
    <div class="notice notice-blue">
        <span>i</span>
        <div>
            <strong>Se o backup falhar</strong>
            <p>Confirme que <code>C:\xampp\mysql\bin\mysqldump.exe</code> existe. Se o XAMPP estiver em outro caminho, edite <code>MasterController::gerarBackup()</code>.</p>
        </div>
    </div>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>8. Solicitações LGPD</h2>
        </div>
    </div>
    <ol>
        <li>Menu lateral → <strong>Master → Solicitações LGPD</strong>.</li>
        <li>Cada solicitação pode ser <strong>aprovada</strong> ou <strong>negada</strong> com motivo.</li>
        <li><strong>Aprovar exclusão</strong> anonimiza os dados do usuário (nome, email, telefone) mas mantém os logs de auditoria.</li>
        <li><strong>Aprovar exportação</strong> apenas marca como concluída (o arquivo já foi gerado e baixado pelo usuário).</li>
    </ol>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <h2>9. Painel Master</h2>
        </div>
    </div>
    <p>
        O painel em <strong>Master → Painel Master</strong> reúne:
        totais de turmas, alunos, projetos, alertas ativos, critérios arquivados e
        solicitações LGPD pendentes. É o ponto de partida para as tarefas administrativas.
    </p>
</article>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>