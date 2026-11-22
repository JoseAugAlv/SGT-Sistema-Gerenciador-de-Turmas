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
        <h1><i class="fas fa-user-graduate" style="color:var(--primary);"></i> Guia do Aluno</h1>
        <p>Tudo que você precisa para usar o sistema no dia a dia.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/tutorial">
            <i class="fas fa-arrow-left"></i> Todos os guias
        </a>
    </div>
</section>

<?php require __DIR__ . '/_nav.php'; ?>

<!-- ============ 1. ENTRAR EM TURMA ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">1</span>
        <div>
            <h2>Entrar em uma turma</h2>
            <p>Como se juntar a uma turma pelo código de acesso.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Peça o <strong>código de acesso</strong> para o professor ou representante.</li>
        <li>No menu lateral, clique em <strong>Turmas</strong>.</li>
        <li>Clique em <strong>Entrar com código</strong>.</li>
        <li>Digite o código e confirme.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/turmas/entrar</div>
        </div>
        <div class="demo-frame-label">
            <i class="fas fa-key"></i> Tela: entrar em turma
        </div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Entrar em uma Turma</h3>
                <p>Informe o código de acesso fornecido pelo professor.</p>
            </div>
            <div class="mini-panel">
                <div class="mini-panel-title"><i class="fas fa-key"></i> Código de acesso</div>
                <div style="display:flex; gap:6px;">
                    <div style="flex:1; padding:8px 10px; border:1px solid var(--border-strong); border-radius:6px; background:var(--surface-2); font-family:monospace; font-size:11px; font-weight:700; letter-spacing:2px; color:var(--text);">INFO2026</div>
                    <span class="mini-btn primary"><i class="fas fa-right-to-bracket"></i> Entrar</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ 2. VER PROJETOS ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">2</span>
        <div>
            <h2>Ver meus projetos</h2>
            <p>Onde acompanhar os projetos das suas turmas.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Menu lateral → <strong>Turmas</strong> → abra a turma desejada.</li>
        <li>Clique em <strong>Ver projetos</strong>.</li>
        <li>Escolha o projeto que quer explorar.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos</div>
        </div>
        <div class="demo-frame-label">
            <i class="fas fa-diagram-project"></i> Tela: lista de projetos
        </div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Meus Projetos</h3>
                <p>3 projetos das suas turmas.</p>
            </div>
            <div class="mini-row">
                <span class="mini-avatar blue">FC</span>
                <div class="mini-row-info">
                    <strong>Feira de Ciências 2026</strong>
                    <small>Turma INFO-3MOD-2026 · 4 grupos · 6 critérios</small>
                    <span class="mini-bar"><i style="width:65%"></i></span>
                </div>
                <span class="mini-badge green">Em andamento</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar purple">AD</span>
                <div class="mini-row-info">
                    <strong>App de Delivery</strong>
                    <small>Turma INFO-3MOD-2026 · 3 grupos · 5 critérios</small>
                    <span class="mini-bar"><i style="width:40%"></i></span>
                </div>
                <span class="mini-badge green">Em andamento</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar orange">RC</span>
                <div class="mini-row-info">
                    <strong>Recicla Já</strong>
                    <small>Turma INFO-2MOD-2026 · 5 grupos · 4 critérios</small>
                    <span class="mini-bar"><i style="width:100%"></i></span>
                </div>
                <span class="mini-badge gray">Encerrado</span>
            </div>
        </div>
    </div>
</section>

<!-- ============ 3. AUTOAVALIAÇÃO ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">3</span>
        <div>
            <h2>Fazer autoavaliação</h2>
            <p>Quando o critério é do tipo "autoavaliação".</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto.</li>
        <li>Clique em <strong>Avaliações → Autoavaliação</strong>.</li>
        <li>Para cada critério, escolha um conceito (<strong>I</strong>, <strong>R</strong>, <strong>B</strong> ou <strong>MB</strong>).</li>
        <li>O valor numérico é preenchido automaticamente.</li>
        <li>Clique em <strong>Salvar</strong>.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/avaliacoes/auto</div>
        </div>
        <div class="demo-frame-label">
            <i class="fas fa-user-circle"></i> Tela: autoavaliação
        </div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Autoavaliação — Feira de Ciências</h3>
                <p>Escolha seu conceito em cada critério.</p>
            </div>
            <table class="mini-table">
                <thead>
                    <tr><th>Critério</th><th>Peso</th><th>Conceito</th></tr>
                </thead>
                <tbody>
                    <tr><td>Participação nas reuniões</td><td>3,0</td><td><span class="mini-conceito mb">MB</span></td></tr>
                    <tr><td>Qualidade da pesquisa</td><td>3,0</td><td><span class="mini-conceito b">B</span></td></tr>
                    <tr><td>Trabalho em equipe</td><td>2,0</td><td><span class="mini-conceito mb">MB</span></td></tr>
                    <tr><td>Apresentação final</td><td>2,0</td><td><span class="mini-conceito r">R</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ============ 4. AVALIAR PARES ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">4</span>
        <div>
            <h2>Avaliar colegas (pares)</h2>
            <p>Quando o critério é do tipo "pares".</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto → <strong>Avaliações → Pares</strong>.</li>
        <li>Você verá todos os outros alunos da turma.</li>
        <li>Para cada colega, escolha um conceito e escreva uma <strong>justificativa obrigatória</strong>.</li>
        <li>Clique em <strong>Salvar</strong>.</li>
    </ol>

    <div class="tut-callout tip">
        <i class="fas fa-shield-halved"></i>
        <div>
            <strong>Sua justificativa é privada</strong>
            Somente o representante e o diretor do grupo conseguem ver. O colega avaliado não vê.
        </div>
    </div>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/avaliacoes/pares</div>
        </div>
        <div class="demo-frame-label">
            <i class="fas fa-users"></i> Tela: avaliar pares
        </div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Avaliação por Pares — Feira de Ciências</h3>
                <p>Justificativa obrigatória em cada avaliação.</p>
            </div>
            <div class="mini-row">
                <span class="mini-avatar purple">MO</span>
                <div class="mini-row-info">
                    <strong>Maria Oliveira</strong>
                    <small>Grupo Alpha</small>
                </div>
                <span class="mini-conceito mb">MB</span>
                <span class="mini-badge blue"><i class="fas fa-pen"></i> Justificar</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar green">PS</span>
                <div class="mini-row-info">
                    <strong>Pedro Santos</strong>
                    <small>Grupo Alpha</small>
                </div>
                <span class="mini-conceito b">B</span>
                <span class="mini-badge green"><i class="fas fa-check"></i> OK</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar orange">AC</span>
                <div class="mini-row-info">
                    <strong>Ana Costa</strong>
                    <small>Grupo Beta</small>
                </div>
                <span class="mini-conceito r">R</span>
                <span class="mini-badge green"><i class="fas fa-check"></i> OK</span>
            </div>
        </div>
    </div>
</section>

<!-- ============ 5. MINHAS NOTAS ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">5</span>
        <div>
            <h2>Ver minhas notas</h2>
            <p>Seu boletim em tempo real.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto.</li>
        <li>Clique em <strong>Avaliações → Minhas Notas</strong>.</li>
        <li>Você verá todos os critérios com peso, nota e conceito.</li>
        <li>No rodapé, aparece a média ponderada e o conceito final.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/avaliacoes/minhas</div>
        </div>
        <div class="demo-frame-label">
            <i class="fas fa-graduation-cap"></i> Tela: minhas notas
        </div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Minhas Notas — Feira de Ciências</h3>
                <p>Notas em tempo real do seu desempenho.</p>
            </div>
            <div class="mini-stats">
                <div class="mini-stat">
                    <div class="label">Média</div>
                    <div class="valor purple">85,3</div>
                </div>
                <div class="mini-stat">
                    <div class="label">Conceito</div>
                    <div class="valor green">MB</div>
                </div>
            </div>
            <table class="mini-table">
                <thead>
                    <tr><th>Critério</th><th>Peso</th><th>Nota</th><th>Conc.</th></tr>
                </thead>
                <tbody>
                    <tr><td>Participação</td><td>3,0</td><td>90,0</td><td><span class="mini-conceito mb">MB</span></td></tr>
                    <tr><td>Pesquisa</td><td>3,0</td><td>85,0</td><td><span class="mini-conceito mb">MB</span></td></tr>
                    <tr><td>Trabalho em equipe</td><td>2,0</td><td>80,0</td><td><span class="mini-conceito b">B</span></td></tr>
                    <tr><td>Apresentação</td><td>2,0</td><td>82,5</td><td><span class="mini-conceito b">B</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ============ 6. ATAS ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">6</span>
        <div>
            <h2>Ver atas em que participei</h2>
            <p>Histórico das reuniões do seu grupo.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto → aba <strong>Atas</strong>.</li>
        <li>Você verá apenas as atas em que foi marcado como participante.</li>
        <li>Clique em <strong>Ver</strong> para consultar atividades e presença.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/atas</div>
        </div>
        <div class="demo-frame-label">
            <i class="fas fa-book"></i> Tela: atas que participei
        </div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Atas em que participei</h3>
                <p>4 reuniões registradas.</p>
            </div>
            <div class="mini-stats">
                <div class="mini-stat"><div class="label">Total</div><div class="valor">4</div></div>
                <div class="mini-stat"><div class="label">Presenças</div><div class="valor green">3</div></div>
                <div class="mini-stat"><div class="label">Justif.</div><div class="valor orange">1</div></div>
                <div class="mini-stat"><div class="label">Ausências</div><div class="valor red">0</div></div>
            </div>
            <div class="mini-row">
                <span class="mini-avatar purple"><i class="fas fa-book"></i></span>
                <div class="mini-row-info">
                    <strong>Reunião de kickoff</strong>
                    <small>Grupo Alpha · 12/03/2026</small>
                </div>
                <span class="mini-badge green"><i class="fas fa-check"></i> Presente</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar purple"><i class="fas fa-book"></i></span>
                <div class="mini-row-info">
                    <strong>Definição dos temas</strong>
                    <small>Grupo Alpha · 19/03/2026</small>
                </div>
                <span class="mini-badge green"><i class="fas fa-check"></i> Presente</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar purple"><i class="fas fa-book"></i></span>
                <div class="mini-row-info">
                    <strong>Revisão da pesquisa</strong>
                    <small>Grupo Alpha · 26/03/2026</small>
                </div>
                <span class="mini-badge orange"><i class="fas fa-info-circle"></i> Justificado</span>
            </div>
        </div>
    </div>
</section>

<!-- ============ 7. NOTIFICAÇÕES ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">7</span>
        <div>
            <h2>Notificações e preferências</h2>
            <p>Fique por dentro sem ser incomodado.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Clique no <strong>sino</strong> (canto superior direito) para ver as notificações.</li>
        <li>Em <strong>Notificações → Preferências</strong>, escolha quais emails deseja receber.</li>
        <li><strong>Alertas urgentes</strong> ignoram suas preferências e sempre chegam.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/notificacoes</div>
        </div>
        <div class="demo-frame-label">
            <i class="fas fa-bell"></i> Tela: notificações
        </div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Notificações</h3>
                <p>2 não lidas.</p>
            </div>
            <div class="mini-row">
                <span class="mini-avatar orange"><i class="fas fa-bullhorn"></i></span>
                <div class="mini-row-info">
                    <strong>Alerta: apresentação adiada</strong>
                    <small>Há 2 horas · turma INFO-3MOD</small>
                </div>
                <span class="mini-badge red">URGENTE</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar purple"><i class="fas fa-star"></i></span>
                <div class="mini-row-info">
                    <strong>Nova avaliação recebida</strong>
                    <small>Ontem · critério "Trabalho em equipe"</small>
                </div>
                <span class="mini-badge blue">Nova</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar green"><i class="fas fa-book"></i></span>
                <div class="mini-row-info">
                    <strong>Ata validada pelo representante</strong>
                    <small>Há 3 dias · Grupo Alpha</small>
                </div>
                <span class="mini-badge gray">Lida</span>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>