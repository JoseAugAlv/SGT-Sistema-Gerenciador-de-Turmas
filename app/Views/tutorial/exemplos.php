<?php
$cssPagina = 'tutorial.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

/**
 * Renderiza um card de demo com:
 * - Título numerado antes do iframe
 * - Moldura de navegador com iframe
 * - Painel de explicação separado abaixo
 *
 * @param string $slug         Slug da demo (ex: 'dashboard-aluno')
 * @param string $url          URL fake exibida na barra do navegador
 * @param string $label        Etiqueta curta de papel (Aluno, Diretor, etc)
 * @param string $icon         Classe FontAwesome da etiqueta
 * @param string $tituloFrame  Título numerado antes do iframe
 * @param string $titulo       Título do painel de explicação
 * @param array  $topicos      Lista de bullets explicativos
 * @param string $dica         (opcional) Dica em destaque
 */
function demoFrame(
    string $slug,
    string $url,
    string $label,
    string $icon,
    string $tituloFrame,
    string $titulo,
    array  $topicos,
    string $dica = ''
): void {
    static $contador = 0;
    $contador++;

    $basePath = App::getBasePath();
    ?>
    <article class="demo-card">

        <!-- ---------- Título numerado ---------- -->
        <header class="demo-card-title">
            <span class="demo-card-num"><?= $contador ?></span>
            <h3><?= htmlspecialchars($tituloFrame) ?></h3>
            <span class="demo-card-label">
                <i class="<?= htmlspecialchars($icon) ?>"></i> <?= htmlspecialchars($label) ?>
            </span>
        </header>

        <!-- ---------- Moldura do navegador ---------- -->
        <div class="demo-frame">
            <div class="demo-frame-header">
                <div class="demo-frame-dots"><span></span><span></span><span></span></div>
                <div class="demo-frame-url">
                    <i class="fas fa-lock"></i> <?= htmlspecialchars($url) ?>
                </div>
            </div>

            <div class="demo-viewport">
                <iframe src="<?= $basePath ?>/tutorial/demo/<?= htmlspecialchars($slug) ?>"
                        loading="lazy"
                        sandbox="allow-same-origin allow-scripts"
                        title="<?= htmlspecialchars($tituloFrame) ?>"></iframe>
            </div>
        </div>

        <!-- ---------- Explicação (fora do frame) ---------- -->
        <div class="demo-explain">
            <header class="demo-explain-header">
                <span class="demo-explain-tag">
                    <i class="fas fa-circle-info"></i> Sobre esta tela
                </span>
                <h3><?= htmlspecialchars($titulo) ?></h3>
            </header>

            <ul class="demo-explain-list">
                <?php foreach ($topicos as $topico): ?>
                    <li>
                        <i class="fas fa-check"></i>
                        <span><?= htmlspecialchars($topico) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($dica !== ''): ?>
                <div class="demo-explain-dica">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <strong>Dica</strong>
                        <span><?= htmlspecialchars($dica) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </article>
    <?php
}
?>

<!-- ============================================================ -->
<!-- HERO                                                          -->
<!-- ============================================================ -->
<section class="tut-hero">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Exemplos
        </div>
        <h1>Galeria de telas do sistema</h1>
        <p>
            Conheça cada tela do sistema com <strong>dados fictícios</strong>, sem precisar
            criar conta nem entrar em uma turma. Role dentro de cada miniatura para ver tudo.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/tutorial">
            <i class="fas fa-arrow-left"></i> Voltar ao tutorial
        </a>
    </div>
</section>

<?php require __DIR__ . '/_nav.php'; ?>

<!-- ============================================================ -->
<!-- INTRODUÇÃO                                                    -->
<!-- ============================================================ -->
<section class="exemplos-intro">
    <div class="exemplos-intro-grid">
        <div class="exemplos-intro-card">
            <span class="exemplos-intro-icon purple"><i class="fas fa-images"></i></span>
            <h3>Como funciona esta galeria</h3>
            <p>
                Cada miniatura é uma <strong>captura viva</strong> da tela real. Os iframes
                usam as mesmas classes CSS do sistema — quando você alterna o tema (sol/lua),
                todas as miniaturas mudam junto.
            </p>
        </div>

        <div class="exemplos-intro-card">
            <span class="exemplos-intro-icon blue"><i class="fas fa-mobile-screen"></i></span>
            <h3>Responsivo por natureza</h3>
            <p>
                As miniaturas se adaptam ao seu dispositivo. No <strong>computador</strong>
                você vê a versão desktop com sidebar; no <strong>celular</strong> a mesma
                tela se reorganiza em layout mobile.
            </p>
        </div>

        <div class="exemplos-intro-card">
            <span class="exemplos-intro-icon green"><i class="fas fa-hand-pointer"></i></span>
            <h3>Interativo (mas sem risco)</h3>
            <p>
                Você pode <strong>rolar</strong> dentro de cada frame e até <strong>clicar</strong>
                nos botões — mas nada é executado de verdade. É uma demonstração visual,
                sem risco de alterar dados.
            </p>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- CATEGORIA 1 — DASHBOARDS                                      -->
<!-- ============================================================ -->
<section class="exemplos-secao">
    <div class="exemplos-secao-header">
        <i class="fas fa-house"></i>
        <h2>Dashboards por papel</h2>
        <small>3 telas</small>
    </div>

    <p class="exemplos-secao-desc">
        Quando um usuário faz login, o sistema identifica seu papel (aluno, diretor ou representante)
        e monta um <strong>painel personalizado</strong> com estatísticas, pendências e atalhos relevantes.
        As três miniaturas abaixo mostram como a interface se transforma conforme a responsabilidade
        do usuário.
    </p>

    <div class="exemplos-grid">
        <?php
        demoFrame(
            'dashboard-aluno',
            'sgt.local/ — como Aluno',
            'Aluno',
            'fas fa-user-graduate',
            'Dashboard do Aluno',
            'O que o aluno vê ao entrar:',
            [
                'Cards no topo com total de turmas, projetos abertos, critérios pendentes e alertas ativos.',
                'Lista de projetos em andamento com barra de progresso e dias restantes até o prazo.',
                'Feed de atividades recentes com as últimas notificações recebidas.',
                'Bloco "Próximos prazos" com destaque em vermelho quando falta menos de 3 dias.',
                'Atalhos rápidos no rodapé: entrar em turma, ver notificações, LGPD.',
            ],
            'O aluno vê apenas os próprios dados e avaliações — respeitando a configuração de visibilidade definida pelo representante.'
        );

        demoFrame(
            'dashboard-diretor',
            'sgt.local/ — como Diretor',
            'Diretor',
            'fas fa-crown',
            'Dashboard do Diretor',
            'O que o diretor vê:',
            [
                'Resumo dos grupos que lidera e total de membros em cada um.',
                'Atas pendentes em destaque (borda laranja) que ele precisa preencher antes do prazo.',
                'Critérios abertos onde ele precisa avaliar os membros do seu grupo.',
                'Lista de membros do grupo com badge de coroa na própria linha.',
                'Atalhos diretos para preencher ata ou ver o ranking do grupo.',
            ],
            'O diretor designado é o único que pode preencher a ata — o painel destaca isso para ele não perder os prazos.'
        );

        demoFrame(
            'dashboard-representante',
            'sgt.local/ — como Representante',
            'Representante',
            'fas fa-user-tie',
            'Dashboard do Representante',
            'O que o representante vê:',
            [
                'Total de alunos ativos na turma e quantas atas ele precisa validar.',
                'Bloco azul "Aguardando sua validação" com atas preenchidas pelos diretores.',
                'Lista de projetos da turma com barra de progresso geral.',
                'Atalhos para gerenciar alunos, entrar em turma e ver notificações.',
                'Acesso a todas as ações de gestão da turma (criar ata, critério, alerta).',
            ],
            'O representante tem o painel mais "gerencial" — vê tudo da turma e é quem valida as atas antes de virarem registro oficial.'
        );
        ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- CATEGORIA 2 — ESTRUTURA                                       -->
<!-- ============================================================ -->
<section class="exemplos-secao">
    <div class="exemplos-secao-header">
        <i class="fas fa-diagram-project"></i>
        <h2>Estrutura do projeto</h2>
        <small>4 telas</small>
    </div>

    <p class="exemplos-secao-desc">
        A hierarquia do sistema é: <strong>Turma → Projeto → Grupo → Critérios</strong>.
        Cada nível tem sua própria tela de detalhe com ações específicas. As miniaturas abaixo
        mostram como navegar entre esses níveis e o que cada um contém.
    </p>

    <div class="exemplos-grid">
        <?php
        demoFrame(
            'turma-detalhe',
            'sgt.local/turmas/1',
            'Turma',
            'fas fa-users',
            'Detalhe da Turma',
            'O que aparece nesta tela:',
            [
                'Nome formatado automaticamente: {código-escola}-{ano-módulo}-{curso}-{período}.',
                'Código de acesso único que os alunos usam em "Entrar com código".',
                'Lista dos representantes ativos (máximo 2 por turma) com badge amarela de coroa.',
                'Lista completa de alunos ativos com avatar e data de entrada.',
                'Ações do master no topo: regenerar código, bloquear turma, editar e excluir.',
            ],
            'O código de acesso é o único caminho para um aluno entrar numa turma — se ele vazar, o representante pode regenerar.'
        );

        demoFrame(
            'projeto-detalhe',
            'sgt.local/projetos/1',
            'Projeto',
            'fas fa-diagram-project',
            'Detalhe do Projeto',
            'O que você encontra aqui:',
            [
                'Cabeçalho com nome do projeto, turma, modo de avaliação e prazo final.',
                'Abas horizontais de navegação: Etapas, Grupos, Atas, Critérios, Avaliações, Conceitos, Relatórios.',
                'Lista de etapas ordenadas com nome, descrição, datas de início/fim e critérios vinculados.',
                'Botões para adicionar, editar, mover (subir/descer) ou excluir cada etapa.',
                'Descrição completa do projeto no topo, quando preenchida.',
            ],
            'O modo "por etapa" permite vincular critérios específicos a cada fase do projeto — útil para avaliações progressivas.'
        );

        demoFrame(
            'grupo-detalhe',
            'sgt.local/grupos/1',
            'Grupo',
            'fas fa-users',
            'Detalhe do Grupo',
            'O que o grupo mostra:',
            [
                'Nome do grupo, modo de avaliação (individual/coletiva) e modo por (diretor, pares, etc).',
                'Lista de diretores ativos com data de nomeação e badge de coroa.',
                'Lista de membros ativos com avatar, nome e email.',
                'Ações do representante: editar, configurar avaliação, alocar em massa, gerenciar diretores, trocar diretor.',
                'Bloco de exclusão do grupo no rodapé (com confirmação antes de apagar).',
            ],
            'Trocar um diretor reatribui automaticamente as atas pendentes dele para o novo diretor — nada se perde.'
        );

        demoFrame(
            'criterios',
            'sgt.local/projetos/1/criterios',
            'Critérios',
            'fas fa-clipboard-check',
            'Critérios de Avaliação',
            'Como funciona a tela de critérios:',
            [
                'Resumo no topo com a soma dos pesos (ideal: 10,00) e status (Completo/Incompleto/Excedido).',
                'Grid de cards com nome do critério, peso e tipo de avaliação em tags coloridas.',
                'Cada card mostra prazo, total de avaliações registradas e ações (detalhes, editar, reabrir, excluir).',
                'Botão "Novo critério" no topo para adicionar mais itens.',
                'Modal de detalhes ao clicar em "Ver detalhes" — mostra tudo sem sair da página.',
            ],
            'Existem 6 tipos de avaliação (diretor, representante, pares, auto, coletiva, misto). Cada critério define seu próprio tipo.'
        );
        ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- CATEGORIA 3 — AVALIAÇÕES                                      -->
<!-- ============================================================ -->
<section class="exemplos-secao">
    <div class="exemplos-secao-header">
        <i class="fas fa-star"></i>
        <h2>Avaliações</h2>
        <small>5 telas</small>
    </div>

    <p class="exemplos-secao-desc">
        O sistema suporta <strong>6 tipos de avaliação</strong>, cada um com sua própria tela
        e lógica de cálculo. O representante pode avaliar a turma em massa; o diretor avalia
        apenas o seu grupo; os alunos avaliam colegas e a si mesmos. Todas as notas entram
        na média ponderada do boletim.
    </p>

    <div class="exemplos-grid">
        <?php
        demoFrame(
            'avaliacoes-rep',
            'sgt.local/projetos/1/avaliacoes/representante',
            'Representante',
            'fas fa-user-tie',
            'Avaliação em Massa (Representante)',
            'Como funciona a avaliação em massa:',
            [
                'Tabela grande com todos os alunos da turma nas linhas e critérios nas colunas.',
                'Cabeçalhos coloridos por aplicabilidade: azul (todos), laranja (só diretores), verde (só representantes).',
                'Barra de preenchimento rápido aplica um conceito ou valor a todos os alunos de uma vez.',
                'Cada célula tem um select de conceito (I/R/B/MB) e um campo de valor numérico.',
                'Marque "Limpar e Salvar" para apagar todas as suas avaliações antes de recomeçar.',
            ],
            'Este é o único painel onde o representante avalia todos os alunos de uma só vez, sem abrir aluno por aluno.'
        );

        demoFrame(
            'avaliacoes-diretor',
            'sgt.local/projetos/1/avaliacoes/diretor',
            'Diretor',
            'fas fa-crown',
            'Avaliação por Diretor',
            'Como o diretor avalia:',
            [
                'Tabela com membros do seu grupo (linhas) e critérios tipo "diretor" ou "misto" (colunas).',
                'A própria linha aparece bloqueada com aviso "Não pode se autoavaliar".',
                'Se houver mais de um diretor, você vê as avaliações dos outros como referência.',
                'Mesma barra de preenchimento rápido do painel do representante.',
                'Botão "Salvar" no rodapé envia todas as avaliações de uma vez.',
            ],
            'Se você for o único diretor do grupo, o representante da turma avalia você automaticamente nos critérios tipo diretor.'
        );

        demoFrame(
            'avaliacoes-pares',
            'sgt.local/projetos/1/avaliacoes/pares',
            'Pares',
            'fas fa-users',
            'Avaliação por Pares',
            'Como funciona a avaliação por pares:',
            [
                'Lista de colegas da turma (menos você), com o grupo de cada um identificado.',
                'Para cada colega: escolha o conceito e escreva uma justificativa obrigatória.',
                'O valor numérico é preenchido automaticamente conforme o conceito escolhido.',
                'A nota final do critério é a média das notas dadas por todos os pares.',
                'Se um colega não for avaliado por ninguém, o critério fica sem nota.',
            ],
            'Sua justificativa é interna — só o representante e o diretor do grupo veem. O colega avaliado não acessa o texto.'
        );

        demoFrame(
            'avaliacoes-auto',
            'sgt.local/projetos/1/avaliacoes/auto',
            'Autoavaliação',
            'fas fa-user-circle',
            'Autoavaliação',
            'Como é a autoavaliação:',
            [
                'Tabela simples com cada critério em uma linha.',
                'Select de conceito (I/R/B/MB) e campo de valor — o valor é preenchido automaticamente.',
                'Você avalia apenas a si mesmo, sem ver as notas dos outros.',
                'A nota entra diretamente na média ponderada do seu boletim.',
            ],
            'Use com honestidade — o representante e o diretor veem a mesma nota que você atribuir a si mesmo.'
        );

        demoFrame(
            'minhas-notas',
            'sgt.local/projetos/1/avaliacoes/minhas',
            'Minhas Notas',
            'fas fa-graduation-cap',
            'Minhas Notas (Boletim Individual)',
            'O que aparece no boletim individual:',
            [
                'Tabela com todos os critérios do projeto — nome, tipo, peso, sua nota e conceito.',
                'Conceitos em cores: MB (verde), B (azul), R (laranja), I (vermelho).',
                'Rodapé com a média ponderada destacada em roxo e o conceito final.',
                'Banner vermelho de "Boletim congelado" quando o projeto foi encerrado.',
                'Botão "Exportar PDF" no topo para gerar documento oficial.',
            ],
            'Depois que o projeto é encerrado, o boletim é congelado — edições nas avaliações não alteram mais os valores.'
        );
        ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- CATEGORIA 4 — ATAS                                            -->
<!-- ============================================================ -->
<section class="exemplos-secao">
    <div class="exemplos-secao-header">
        <i class="fas fa-book"></i>
        <h2>Atas de reunião</h2>
        <small>2 telas</small>
    </div>

    <p class="exemplos-secao-desc">
        As atas têm um <strong>fluxo claro em 3 etapas</strong>: o representante cria,
        o diretor preenche e o representante valida. Cada ata registra atividades,
        participantes, presenças e relatórios internos. A visão muda conforme o papel
        de quem está acessando.
    </p>

    <div class="exemplos-grid">
        <?php
        demoFrame(
            'atas-rep',
            'sgt.local/projetos/1/atas — representante',
            'Representante',
            'fas fa-user-tie',
            'Atas — Visão do Representante',
            'Como o representante gerencia as atas:',
            [
                'Bloco azul no topo destacando atas preenchidas que aguardam validação.',
                'Grid de cards, cada um mostrando título, grupo, data, diretor e status colorido.',
                'Botões contextuais: "Validar" (só para preenchidas), "Editar" (menos revisadas), "Abrir".',
                'Status possíveis: Pendente (laranja), Preenchida (azul), Revisada (verde).',
                'Botão "Nova ata" no topo para criar e distribuir para vários grupos ao mesmo tempo.',
            ],
            'O representante pode replicar uma ata para vários grupos — cada grupo recebe uma cópia própria, com seu diretor designado.'
        );

        demoFrame(
            'atas-diretor',
            'sgt.local/projetos/1/atas — diretor',
            'Diretor',
            'fas fa-crown',
            'Atas — Visão do Diretor',
            'Como o diretor vê suas atas:',
            [
                'Bloco laranja no topo com as atas que ele precisa preencher, ordenadas por prazo.',
                'Cada card mostra título, grupo, data, prazo e botão "Preencher agora".',
                'Depois de finalizada, a ata sai da lista de pendentes e vai para validação.',
                'Aviso explicando o que é uma ata e como preenchê-la corretamente.',
                'As atas revisadas pelo representante somem da lista e ficam só no histórico.',
            ],
            'Ao preencher uma ata, você adiciona atividades, relatórios internos e ajusta a presença de cada membro do grupo.'
        );
        ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- CATEGORIA 5 — MATERIAIS                                       -->
<!-- ============================================================ -->
<section class="exemplos-secao">
    <div class="exemplos-secao-header">
        <i class="fas fa-boxes"></i>
        <h2>Materiais e solicitações</h2>
        <small>1 tela completa</small>
    </div>

    <p class="exemplos-secao-desc">
        Qualquer aluno pode <strong>solicitar material</strong>. Todos da turma veem a solicitação.
        O representante ou diretor <strong>aprova</strong>, <strong>recusa</strong> ou
        <strong>confirma a compra</strong>. Ao confirmar, o estoque é atualizado automaticamente
        com o cálculo do preço médio ponderado.
    </p>

    <div class="exemplos-grid">
        <?php
        demoFrame(
            'materiais',
            'sgt.local/projetos/1/materiais',
            'Materiais',
            'fas fa-boxes',
            'Materiais e Solicitações',
            'O que você encontra nesta tela:',
            [
                'Bloco laranja no topo com solicitações pendentes e aprovadas aguardando compra.',
                'Grid de materiais com badge colorida por nível: Alto (verde), Médio (azul), Baixo (laranja), Zerado (vermelho).',
                'Cada card mostra quantidade atual, unidade, preço médio e ações (usar, comprar, excluir).',
                'Botão "Solicitar material" no topo — qualquer aluno pode pedir o que precisa.',
                'Botão "Movimentações" para ver o histórico completo de entradas e saídas.',
            ],
            'Quando uma compra é confirmada, o preço médio é recalculado: (estoque × preço atual + comprado × preço compra) ÷ (estoque + comprado).'
        );
        ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- CATEGORIA 6 — RELATÓRIOS                                      -->
<!-- ============================================================ -->
<section class="exemplos-secao">
    <div class="exemplos-secao-header">
        <i class="fas fa-table"></i>
        <h2>Relatórios e boletins</h2>
        <small>1 tela + exportações</small>
    </div>

    <p class="exemplos-secao-desc">
        O relatório geral mostra a <strong>matriz completa</strong> de alunos × critérios com
        médias ponderadas e ranking. Ideal para o representante ter uma visão geral, para o
        diretor acompanhar o grupo, e para o master auditar o projeto inteiro.
    </p>

    <div class="exemplos-grid">
        <?php
        demoFrame(
            'relatorio-geral',
            'sgt.local/projetos/1/relatorios/geral',
            'Relatórios',
            'fas fa-table',
            'Relatório Geral do Projeto',
            'Estrutura do relatório:',
            [
                'Toolbar no topo com botões para exportar em PDF ou Excel.',
                'Legenda de conceitos (MB / B / R / I) com cores para referência rápida.',
                'Matriz de notas: cada linha é um aluno, cada coluna é um critério com peso em baixo.',
                'Coluna "Média" destacada em roxo com a média ponderada de cada aluno.',
                'Coluna "Conceito" com badge colorida (MB/B/R/I) por aluno.',
                'Ranking no final da página, ordenado da maior para a menor média.',
            ],
            'Use os botões de exportar PDF/Excel para gerar documentos oficiais com o mesmo layout do sistema.'
        );
        ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- CATEGORIA 7 — COMUNICAÇÃO E CONFIG                            -->
<!-- ============================================================ -->
<section class="exemplos-secao">
    <div class="exemplos-secao-header">
        <i class="fas fa-bell"></i>
        <h2>Comunicação e configurações</h2>
        <small>2 telas</small>
    </div>

    <p class="exemplos-secao-desc">
        Notificações mantêm todos informados sobre <strong>prazos, avaliações, atas e alertas</strong>.
        A configuração de conceito define como as notas numéricas são convertidas em conceitos
        e quem pode ver o boletim de quem.
    </p>

    <div class="exemplos-grid">
        <?php
        demoFrame(
            'notificacoes',
            'sgt.local/notificacoes',
            'Notificações',
            'fas fa-bell',
            'Central de Notificações',
            'O que aparece nas notificações:',
            [
                'Toolbar com filtros (Todas / Não lidas) e contador de não lidas.',
                'Lista de notificações com ícone colorido por tipo: alerta, prazo, avaliação, ata, papel, material.',
                'Notificações não lidas têm barra lateral roxa e gradiente sutil de fundo.',
                'Cada item tem botões "Abrir" (leva ao contexto) e "Marcar como lida".',
                'Botão "Marcar todas como lidas" para limpar a lista de uma vez.',
            ],
            'Alertas urgentes ignoram as preferências de email do usuário e chegam mesmo que ele tenha desativado as notificações.'
        );

        demoFrame(
            'conceitos',
            'sgt.local/projetos/1/conceitos',
            'Conceitos',
            'fas fa-sliders',
            'Configuração de Conceito',
            'Como configurar os conceitos:',
            [
                'Barra visual no topo mostrando como 0–100 é dividido entre I, R, B e MB.',
                'Cards coloridos por faixa (vermelho, laranja, azul, verde) com limites editáveis.',
                'Configuração de visibilidade de boletins: turma / apenas o grupo / apenas o próprio.',
                'Duas seções separadas: "Como diretor" e "Como aluno" — cada uma com regras próprias.',
                'Botão "Pré-visualizar" abre um simulador com slider para testar as faixas.',
            ],
            'O representante pode ajustar as faixas por projeto — um projeto pode ter regras diferentes de outro.'
        );
        ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- RODAPÉ DE DICAS                                               -->
<!-- ============================================================ -->
<div class="tut-callout tip" style="margin-top:12px;">
    <i class="fas fa-lightbulb"></i>
    <div>
        <strong>Pronto para experimentar de verdade?</strong>
        <p class="mb-0">
            Crie uma conta gratuita e entre em uma turma de demonstração com o código
            <code style="padding:2px 6px; background:var(--surface-2); border-radius:4px; font-family:monospace;">DEMO2026</code>
            — todos os dados são fictícios e você pode testar tudo sem medo.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>