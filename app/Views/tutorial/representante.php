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
        <h1><i class="fas fa-user-tie" style="color:var(--green);"></i> Guia do Representante</h1>
        <p>Como coordenar sua turma e gerenciar projetos.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/tutorial">
            <i class="fas fa-arrow-left"></i> Todos os guias
        </a>
    </div>
</section>

<?php require __DIR__ . '/_nav.php'; ?>

<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">1</span>
        <div>
            <h2>Como você vira representante</h2>
        </div>
    </div>
    <p>
        O <strong>master</strong> nomeia até 2 representantes por turma.
        A turma nunca fica sem representante: o último só sai se outro for nomeado antes.
    </p>
</section>

<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">2</span>
        <div>
            <h2>Gerenciar grupos e diretores</h2>
            <p>Criação e organização dos grupos da turma.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto → aba <strong>Grupos</strong>.</li>
        <li>Clique em <strong>Novo grupo</strong> e escolha o modo (individual ou coletiva).</li>
        <li>Abra o grupo → <strong>Adicionar membro</strong> para incluir alunos.</li>
        <li>Clique em <strong>Gerenciar diretores</strong> → escolha um membro.</li>
        <li>Para trocar um diretor, use <strong>Trocar diretor</strong>: o novo herda as atas pendentes.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/grupos</div>
        </div>
        <div class="demo-frame-label"><i class="fas fa-users"></i> Tela: grupos do projeto</div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Grupos — Feira de Ciências</h3>
                <p>4 grupos · 22 alunos alocados</p>
            </div>
            <div class="mini-row">
                <span class="mini-avatar purple">GA</span>
                <div class="mini-row-info">
                    <strong>Grupo Alpha</strong>
                    <small>6 membros · 1 diretor</small>
                </div>
                <span class="mini-btn primary">Gerenciar</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar blue">GB</span>
                <div class="mini-row-info">
                    <strong>Grupo Beta</strong>
                    <small>5 membros · 1 diretor</small>
                </div>
                <span class="mini-btn primary">Gerenciar</span>
            </div>
            <div class="mini-row" style="border-top:1px dashed var(--border-strong); padding-top:8px;">
                <span class="mini-avatar green"><i class="fas fa-plus"></i></span>
                <div class="mini-row-info"><strong>Novo grupo</strong></div>
            </div>
        </div>
    </div>
</section>

<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">3</span>
        <div>
            <h2>Criar etapas e critérios</h2>
        </div>
    </div>

    <ol class="tut-steps">
        <li><strong>Etapas</strong>: abra o projeto → <strong>Adicionar etapa</strong>. Use as setas para reordenar.</li>
        <li><strong>Critérios</strong>: aba <strong>Critérios → Novo critério</strong>. Defina nome, peso, tipo e prazo.</li>
        <li>Tipos disponíveis: <em>diretor, representante, pares, autoavaliação, coletiva, misto</em>.</li>
        <li>O sistema avisa quando a soma dos pesos passa ou não chega a 10.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/criterios</div>
        </div>
        <div class="demo-frame-label"><i class="fas fa-clipboard-check"></i> Tela: critérios</div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Critérios — Feira de Ciências</h3>
                <p>4 critérios · soma dos pesos: 10,00</p>
            </div>
            <div class="mini-stats">
                <div class="mini-stat">
                    <div class="label">Soma dos pesos</div>
                    <div class="valor green">10,00</div>
                </div>
                <div class="mini-stat">
                    <div class="label">Status</div>
                    <div class="valor green">Completo</div>
                </div>
            </div>
            <table class="mini-table">
                <thead><tr><th>Critério</th><th>Tipo</th><th>Peso</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td>Participação nas reuniões</td><td>representante</td><td>3,0</td><td><span class="mini-badge green">Aberto</span></td></tr>
                    <tr><td>Pesquisa e desenvolvimento</td><td>diretor</td><td>3,0</td><td><span class="mini-badge green">Aberto</span></td></tr>
                    <tr><td>Colaboração com o grupo</td><td>pares</td><td>2,0</td><td><span class="mini-badge green">Aberto</span></td></tr>
                    <tr><td>Autoavaliação</td><td>autoavaliação</td><td>2,0</td><td><span class="mini-badge green">Aberto</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">4</span>
        <div>
            <h2>Avaliar a turma em massa</h2>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto → <strong>Avaliações → Representante</strong>.</li>
        <li>Cada linha é um aluno, cada coluna é um critério.</li>
        <li>Use os botões de preenchimento rápido para agilizar.</li>
        <li>Marque <strong>Limpar e Salvar</strong> se quiser apagar tudo antes.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/avaliacoes/representante</div>
        </div>
        <div class="demo-frame-label"><i class="fas fa-user-tie"></i> Tela: avaliação em massa</div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Avaliação em Massa</h3>
                <p>22 alunos × 4 critérios</p>
            </div>
            <table class="mini-table">
                <thead>
                    <tr><th>Aluno</th><th>Particip.</th><th>Pesquisa</th><th>Colab.</th><th>Auto</th></tr>
                </thead>
                <tbody>
                    <tr><td>Ana Costa</td><td><span class="mini-conceito mb">MB</span></td><td><span class="mini-conceito b">B</span></td><td><span class="mini-conceito mb">MB</span></td><td><span class="mini-conceito b">B</span></td></tr>
                    <tr><td>João Silva</td><td><span class="mini-conceito b">B</span></td><td><span class="mini-conceito b">B</span></td><td><span class="mini-conceito mb">MB</span></td><td><span class="mini-conceito mb">MB</span></td></tr>
                    <tr><td>Maria Oliveira</td><td><span class="mini-conceito mb">MB</span></td><td><span class="mini-conceito mb">MB</span></td><td><span class="mini-conceito mb">MB</span></td><td><span class="mini-conceito b">B</span></td></tr>
                    <tr><td>Pedro Santos</td><td><span class="mini-conceito b">B</span></td><td><span class="mini-conceito r">R</span></td><td><span class="mini-conceito b">B</span></td><td><span class="mini-conceito mb">MB</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">5</span>
        <div>
            <h2>Criar e validar atas</h2>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto → <strong>Atas → Nova ata</strong>.</li>
        <li>Marque os grupos que devem receber (pode selecionar vários).</li>
        <li>Quando um diretor preencher e finalizar, a ata aparece como <strong>aguardando validação</strong>.</li>
        <li>Abra → revise → clique em <strong>Validar</strong>.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/atas</div>
        </div>
        <div class="demo-frame-label"><i class="fas fa-book"></i> Tela: atas (representante)</div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Atas — Representante</h3>
                <p>6 atas · 2 aguardando validação</p>
            </div>
            <div class="mini-row">
                <span class="mini-avatar blue"><i class="fas fa-pen"></i></span>
                <div class="mini-row-info">
                    <strong>Reunião de kickoff</strong>
                    <small>Grupo Alpha · 12/03/2026</small>
                </div>
                <span class="mini-badge blue">Preenchida</span>
                <span class="mini-btn primary">Validar</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar blue"><i class="fas fa-pen"></i></span>
                <div class="mini-row-info">
                    <strong>Definição dos temas</strong>
                    <small>Grupo Beta · 18/03/2026</small>
                </div>
                <span class="mini-badge blue">Preenchida</span>
                <span class="mini-btn primary">Validar</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar green"><i class="fas fa-check"></i></span>
                <div class="mini-row-info">
                    <strong>Revisão de pesquisa</strong>
                    <small>Grupo Gamma · 20/03/2026</small>
                </div>
                <span class="mini-badge green">Revisada</span>
            </div>
        </div>
    </div>
</section>

<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">6</span>
        <div>
            <h2>Enviar alertas</h2>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra a turma → <strong>Alertas → Novo alerta</strong>.</li>
        <li>Escolha o <strong>escopo</strong>: turma, projeto ou grupo.</li>
        <li>Marque <strong>Urgente</strong> se quiser que chegue mesmo para quem desativou emails.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/turmas/1/alertas</div>
        </div>
        <div class="demo-frame-label"><i class="fas fa-bullhorn"></i> Tela: alertas da turma</div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Alertas da Turma</h3>
                <p>3 alertas ativos</p>
            </div>
            <div class="mini-row">
                <span class="mini-avatar red"><i class="fas fa-bullhorn"></i></span>
                <div class="mini-row-info">
                    <strong>Apresentação adiada para 27/03</strong>
                    <small>Há 2 horas · urgente</small>
                </div>
                <span class="mini-badge red">URGENTE</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar purple"><i class="fas fa-bullhorn"></i></span>
                <div class="mini-row-info">
                    <strong>Lembrete: entrega da pesquisa</strong>
                    <small>Há 1 dia</small>
                </div>
                <span class="mini-badge purple">Alerta</span>
            </div>
        </div>
    </div>
</section>

<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">7</span>
        <div>
            <h2>Relatórios e boletins</h2>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto → aba <strong>Relatórios</strong>.</li>
        <li>Escolha <strong>Relatório Geral</strong> para ver todos os alunos × critérios com ranking.</li>
        <li>Exporte em PDF ou Excel.</li>
        <li>Para um aluno específico, use <strong>Boletim individual</strong>.</li>
    </ol>
</section>

<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">8</span>
        <div>
            <h2>Configuração de conceito</h2>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto → aba <strong>Conceitos</strong>.</li>
        <li>Ajuste as faixas: <strong>I</strong>, <strong>R</strong>, <strong>B</strong>, <strong>MB</strong>.</li>
        <li>Defina a <strong>visibilidade de boletins</strong> (turma / grupo / próprio).</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/conceitos</div>
        </div>
        <div class="demo-frame-label"><i class="fas fa-sliders"></i> Tela: configuração de conceito</div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Configuração de Conceito</h3>
                <p>Distribuição das faixas de nota.</p>
            </div>
            <div style="display:flex; height:34px; border-radius:6px; overflow:hidden; border:1px solid var(--border); margin:8px 0;">
                <div style="width:50%; background:linear-gradient(135deg,#dc3b4d,#c62828); color:#fff; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:750;">I</div>
                <div style="width:20%; background:linear-gradient(135deg,#e08600,#c26e00); color:#fff; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:750;">R</div>
                <div style="width:15%; background:linear-gradient(135deg,#2563eb,#1e4fc4); color:#fff; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:750;">B</div>
                <div style="width:15%; background:linear-gradient(135deg,#16a34a,#0f8a3d); color:#fff; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:750;">MB</div>
            </div>
            <div class="mini-stats">
                <div class="mini-stat"><div class="label">I até</div><div class="valor red">49</div></div>
                <div class="mini-stat"><div class="label">R</div><div class="valor orange">50-69</div></div>
                <div class="mini-stat"><div class="label">B</div><div class="valor blue">70-84</div></div>
                <div class="mini-stat"><div class="label">MB</div><div class="valor green">85+</div></div>
            </div>
        </div>
    </div>
</section>

<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">9</span>
        <div>
            <h2>Materiais e solicitações</h2>
        </div>
    </div>
    <p>
        Você pode cadastrar, usar e comprar materiais. Também aprova solicitações
        feitas pelos alunos e confirma compras, o que atualiza o estoque automaticamente.
    </p>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>