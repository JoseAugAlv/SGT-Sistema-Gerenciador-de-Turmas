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
        <h1><i class="fas fa-crown" style="color:var(--blue);"></i> Guia do Diretor</h1>
        <p>Como liderar seu grupo, avaliar e registrar tudo.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/tutorial">
            <i class="fas fa-arrow-left"></i> Todos os guias
        </a>
    </div>
</section>

<?php require __DIR__ . '/_nav.php'; ?>

<!-- ============ 1. ENCONTRAR MEUS GRUPOS ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">1</span>
        <div>
            <h2>Encontrar meus grupos</h2>
            <p>Onde ver os grupos que você lidera.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Menu lateral → <strong>Turmas</strong> → abra a turma.</li>
        <li>Clique em <strong>Ver projetos</strong> → abra o projeto.</li>
        <li>Clique na aba <strong>Grupos</strong>.</li>
        <li>Os grupos em que você é diretor aparecem com uma <strong>coroa</strong>.</li>
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
                <p>4 grupos neste projeto.</p>
            </div>
            <div class="mini-row">
                <span class="mini-avatar purple">GA</span>
                <div class="mini-row-info">
                    <strong>Grupo Alpha <i class="fas fa-crown" style="color:var(--orange);"></i></strong>
                    <small>6 membros · 1 diretor (você)</small>
                </div>
                <span class="mini-badge purple">Diretor</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar blue">GB</span>
                <div class="mini-row-info">
                    <strong>Grupo Beta</strong>
                    <small>5 membros · 1 diretor</small>
                </div>
                <span class="mini-badge gray">Membro</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar green">GC</span>
                <div class="mini-row-info">
                    <strong>Grupo Gamma</strong>
                    <small>5 membros · 1 diretor</small>
                </div>
                <span class="mini-badge gray">Membro</span>
            </div>
        </div>
    </div>
</section>

<!-- ============ 2. AVALIAR MEMBROS ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">2</span>
        <div>
            <h2>Avaliar membros do grupo</h2>
            <p>Avaliação por diretor.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto → <strong>Avaliações → Diretor</strong>.</li>
        <li>Se dirige mais de um grupo, escolha qual avaliar.</li>
        <li>Cada linha é um membro, cada coluna é um critério.</li>
        <li>Use os botões de <strong>preenchimento rápido</strong> se quiser aplicar o mesmo conceito a todos.</li>
        <li>Clique em <strong>Salvar</strong>.</li>
    </ol>

    <div class="tut-callout warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Você não pode se autoavaliar</strong>
            Sua própria linha fica bloqueada. Se você for o único diretor do grupo, o representante da turma vai te avaliar automaticamente.
        </div>
    </div>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/avaliacoes/diretor</div>
        </div>
        <div class="demo-frame-label"><i class="fas fa-crown"></i> Tela: avaliar por diretor</div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Avaliação por Diretor — Grupo Alpha</h3>
                <p>Preencha o conceito de cada membro por critério.</p>
            </div>
            <table class="mini-table">
                <thead>
                    <tr><th>Membro</th><th>Particip.</th><th>Pesquisa</th><th>Equipe</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>João Silva</strong> <span class="mini-badge purple">você</span></td>
                        <td>—</td><td>—</td><td>—</td>
                    </tr>
                    <tr>
                        <td>Maria Oliveira</td>
                        <td><span class="mini-conceito mb">MB</span></td>
                        <td><span class="mini-conceito mb">MB</span></td>
                        <td><span class="mini-conceito b">B</span></td>
                    </tr>
                    <tr>
                        <td>Pedro Santos</td>
                        <td><span class="mini-conceito b">B</span></td>
                        <td><span class="mini-conceito b">B</span></td>
                        <td><span class="mini-conceito mb">MB</span></td>
                    </tr>
                    <tr>
                        <td>Ana Costa</td>
                        <td><span class="mini-conceito mb">MB</span></td>
                        <td><span class="mini-conceito r">R</span></td>
                        <td><span class="mini-conceito b">B</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ============ 3. PREENCHER ATAS ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">3</span>
        <div>
            <h2>Preencher atas</h2>
            <p>Registrar as reuniões do grupo.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>No painel inicial, se houver atas pendentes, elas aparecem em destaque.</li>
        <li>Ou acesse o projeto → aba <strong>Atas</strong> → clique na ata pendente.</li>
        <li><strong>Adicione atividades</strong>: nome, descrição e participantes.</li>
        <li><strong>Adicione relatórios</strong>: tipo, tema e conteúdo.</li>
        <li>Clique em <strong>Finalizar ata</strong> quando terminar.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/atas</div>
        </div>
        <div class="demo-frame-label"><i class="fas fa-book"></i> Tela: pendentes para mim</div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Atas — Diretor</h3>
                <p>2 atas pendentes para preencher.</p>
            </div>
            <div class="mini-row">
                <span class="mini-avatar orange"><i class="fas fa-hourglass-half"></i></span>
                <div class="mini-row-info">
                    <strong>Reunião de kickoff</strong>
                    <small>Grupo Alpha · prazo 20/03/2026</small>
                </div>
                <span class="mini-badge orange">Pendente</span>
                <span class="mini-btn primary">Preencher</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar orange"><i class="fas fa-hourglass-half"></i></span>
                <div class="mini-row-info">
                    <strong>Definição dos temas</strong>
                    <small>Grupo Alpha · prazo 22/03/2026</small>
                </div>
                <span class="mini-badge orange">Pendente</span>
                <span class="mini-btn primary">Preencher</span>
            </div>
        </div>
    </div>
</section>

<!-- ============ 4. MATERIAIS ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">4</span>
        <div>
            <h2>Cadastrar e usar materiais</h2>
            <p>Controle de estoque do projeto.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto → aba <strong>Materiais</strong>.</li>
        <li>Clique em <strong>Novo material</strong> e preencha nome, unidade, quantidade e preço.</li>
        <li>Para retirar, clique em <strong>Usar</strong> e informe a quantidade.</li>
        <li>Para registrar compra, clique em <strong>Comprar</strong> — o preço médio é recalculado.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/materiais</div>
        </div>
        <div class="demo-frame-label"><i class="fas fa-boxes"></i> Tela: materiais</div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Materiais — Feira de Ciências</h3>
                <p>4 materiais em estoque.</p>
            </div>
            <div class="mini-row">
                <span class="mini-avatar green"><i class="fas fa-box"></i></span>
                <div class="mini-row-info">
                    <strong>Papel sulfite A4</strong>
                    <small>R$ 25,90 · 12 un</small>
                </div>
                <span class="mini-badge green">Alto</span>
                <span class="mini-btn"><i class="fas fa-minus"></i></span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar orange"><i class="fas fa-box"></i></span>
                <div class="mini-row-info">
                    <strong>Cola branca</strong>
                    <small>R$ 8,50 · 3 un</small>
                </div>
                <span class="mini-badge orange">Baixo</span>
                <span class="mini-btn"><i class="fas fa-plus"></i></span>
            </div>
        </div>
    </div>
</section>

<!-- ============ 5. NOTAS DO GRUPO ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">5</span>
        <div>
            <h2>Ver as notas do meu grupo</h2>
            <p>Como está o desempenho da equipe.</p>
        </div>
    </div>

    <ol class="tut-steps">
        <li>Abra o projeto → <strong>Relatórios → Relatório Geral</strong>.</li>
        <li>Você verá todos os alunos, critérios e médias.</li>
        <li>O ranking no final mostra as maiores médias.</li>
    </ol>

    <div class="demo-frame">
        <div class="demo-frame-header">
            <div class="demo-frame-dots"><span></span><span></span><span></span></div>
            <div class="demo-frame-url"><i class="fas fa-lock"></i> sgt.local/projetos/1/relatorios/geral</div>
        </div>
        <div class="demo-frame-label"><i class="fas fa-trophy"></i> Tela: ranking do grupo</div>
        <div class="demo-frame-body">
            <div class="mini-hero">
                <h3>Ranking por média</h3>
                <p>Grupo Alpha</p>
            </div>
            <div class="mini-row">
                <span class="mini-avatar orange">1º</span>
                <div class="mini-row-info">
                    <strong>Maria Oliveira</strong>
                    <small>Média 92,3</small>
                    <span class="mini-bar"><i style="width:92%"></i></span>
                </div>
                <span class="mini-badge green">MB</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar blue">2º</span>
                <div class="mini-row-info">
                    <strong>Pedro Santos</strong>
                    <small>Média 85,0</small>
                    <span class="mini-bar"><i style="width:85%"></i></span>
                </div>
                <span class="mini-badge green">MB</span>
            </div>
            <div class="mini-row">
                <span class="mini-avatar green">3º</span>
                <div class="mini-row-info">
                    <strong>Ana Costa</strong>
                    <small>Média 78,5</small>
                    <span class="mini-bar"><i style="width:78%"></i></span>
                </div>
                <span class="mini-badge blue">B</span>
            </div>
        </div>
    </div>
</section>

<!-- ============ 6. NOTIFICAÇÕES ============ -->
<section class="tut-block">
    <div class="tut-block-header">
        <span class="tut-block-num">6</span>
        <div>
            <h2>Notificações importantes</h2>
        </div>
    </div>

    <ul class="tut-steps">
        <li>Nova ata para preencher</li>
        <li>Atas reatribuídas quando você assume um grupo</li>
        <li>Novos alertas da turma</li>
        <li>Prazos próximos de critérios</li>
    </ul>

    <div class="tut-callout info">
        <i class="fas fa-circle-info"></i>
        <div>
            <strong>Ao virar diretor de um grupo, você herda as atas pendentes</strong>
            O sistema reatribui automaticamente as atas que estavam com o diretor anterior.
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>