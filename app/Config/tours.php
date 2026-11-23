<?php
// app/Config/tours.php
// Tours guiados das telas (fonte única: o motor public/js/tour.js recebe isto em JSON, e a página /tutorial
// lista os mesmos tours com o botão "Rodar tutorial").
//   rota   — expressão da página (caminho depois do /SGT), onde o tour roda e o card da 1ª visita aparece
//   abrir  — página para o botão "Rodar tutorial": {turma}, {projeto}, {grupo}, {reuniao} são trocados por
//            ids da própria pessoa (TutorialController); sem eles, o botão pede para entrar numa turma antes
//   passos — alvo (seletor CSS; null = card no centro), titulo, texto. Passo sem alvo visível é pulado
//            (assim o mesmo tour serve para aluno, representante e master).
return [
    'inicio' => [
        'titulo' => 'Página inicial', 'icone' => 'fa-house', 'rota' => '^/$', 'abrir' => '/',
        'descricao' => 'O que aparece no início, o menu, a busca rápida, as notificações e a agenda.',
        'passos' => [
            ['alvo' => null, 'titulo' => 'Bem-vindo ao Prumo!', 'texto' => 'Em 1 minuto você conhece o essencial desta tela. Use as setas do teclado ou os botões abaixo.'],
            ['alvo' => '#sidebar', 'titulo' => 'Menu', 'texto' => 'Turmas, agenda, projetos, planos e a sua conta ficam aqui. No celular, o menu fica no botão ☰ e na barra de baixo.'],
            ['alvo' => '.busca-botao', 'titulo' => 'Busca rápida', 'texto' => 'Procure qualquer função pelo nome. Atalho: Ctrl + K.'],
            ['alvo' => '.notification-button', 'titulo' => 'Notificações', 'texto' => 'Avisos, prazos, enquetes e respostas chegam aqui. O ponto vermelho indica novidades.'],
            ['alvo' => '.theme-picker', 'titulo' => 'Tema', 'texto' => 'Claro, escuro ou misto: escolha o que fica melhor para os seus olhos.'],
            ['alvo' => '.home-agenda', 'titulo' => 'Sua agenda', 'texto' => 'Provas, entregas e reuniões das suas turmas, com cores por prioridade. Clique num dia para ver os detalhes.'],
            ['alvo' => '#user-menu', 'titulo' => 'Sua conta', 'texto' => 'Seus dados, preferências de email e de notificações no celular, e sair.'],
        ],
    ],
    'turma' => [
        'titulo' => 'Painel da turma', 'icone' => 'fa-users', 'rota' => '^/turmas/\d+$', 'abrir' => '/turmas/{turma}',
        'descricao' => 'Código da turma, atalhos do dia a dia (avisos, agenda, enquetes, caixinha, estudos, mural, caixa) e projetos.',
        'passos' => [
            ['alvo' => '.turma-faixa', 'titulo' => 'Sua turma', 'texto' => 'Nome, identidade e o código de acesso: passe o código para os colegas entrarem.'],
            ['alvo' => '.dia-atalhos', 'titulo' => 'Dia a dia', 'texto' => 'Os atalhos do que a turma usa toda semana.'],
            ['alvo' => '.dia-atalhos a[href$="/alertas"]', 'titulo' => 'Avisos', 'texto' => 'Recados para a turma, com contagem regressiva para provas e eventos.'],
            ['alvo' => '.dia-atalhos a[href$="/enquetes"]', 'titulo' => 'Enquetes', 'texto' => 'A turma decide junto: o representante cria, todos votam.'],
            ['alvo' => '.dia-atalhos a[href$="/caixinha"]', 'titulo' => 'Caixinha anônima', 'texto' => 'Dúvidas e sugestões para o representante, sem aparecer o nome de quem mandou.'],
            ['alvo' => '.dia-atalhos a[href$="/estudos"]', 'titulo' => 'Estudos', 'texto' => 'Materiais por disciplina, ligados às provas da agenda.'],
            ['alvo' => '.dia-atalhos a[href$="/caixa"]', 'titulo' => 'Caixa', 'texto' => 'Entradas, saídas e comprovantes do dinheiro da turma, à vista de todos.'],
            ['alvo' => '.turma-acoes a[href$="/plano"]', 'titulo' => 'Plano', 'texto' => 'Para o representante: plano, parcelas, divisão da mensalidade e renovação.'],
            ['alvo' => '.turma-projetos', 'titulo' => 'Projetos', 'texto' => 'Os projetos da turma. Abra um para ver etapas, grupos, critérios e avaliações.'],
        ],
    ],
    'plano' => [
        'titulo' => 'Plano da turma', 'icone' => 'fa-receipt', 'rota' => '^/turmas/\d+/plano$', 'abrir' => '/turmas/{turma}/plano',
        'descricao' => 'Parcelas e Pix, dividir a mensalidade entre os alunos, Dia a dia, renovação e troca de plano.',
        'passos' => [
            ['alvo' => '.lista-parcelas', 'titulo' => 'Parcelas', 'texto' => 'Cada parcela do plano, com vencimento e situação. A próxima tem o botão de pagar por Pix.'],
            ['alvo' => '.lista-parcelas a[href$="/rateio"]', 'titulo' => 'Dividir com a turma', 'texto' => 'Divida a parcela entre os alunos: cada um paga a sua parte pelo Pix.'],
            ['alvo' => '#dia-a-dia', 'titulo' => 'Dia a dia', 'texto' => 'Agenda, horário, aviso do dia e contagem. Vem no Anual; nos outros planos dá para testar grátis ou liberar.'],
            ['alvo' => '#renovar', 'titulo' => 'Renovação', 'texto' => 'Perto do fim, renove aqui: o novo período começa quando o atual acaba, com cupom de desconto.'],
            ['alvo' => '#trocar', 'titulo' => 'Trocar de plano', 'texto' => 'Suba para um plano maior pagando só a diferença.'],
        ],
    ],
    'projeto' => [
        'titulo' => 'Projeto', 'icone' => 'fa-diagram-project', 'rota' => '^/projetos/\d+$', 'abrir' => '/projetos/{projeto}',
        'descricao' => 'As abas do projeto: etapas, grupos, atas, critérios, avaliações, orçamento e o portfólio em PDF.',
        'passos' => [
            ['alvo' => '.proj-nav', 'titulo' => 'Abas do projeto', 'texto' => 'Tudo do projeto fica nestas abas. Você vê só as que fazem sentido para o seu papel.'],
            ['alvo' => '.proj-nav a[href$="/grupos"]', 'titulo' => 'Grupos', 'texto' => 'Os grupos, seus integrantes, diretores e o quadro de tarefas de cada um.'],
            ['alvo' => '.proj-nav a[href$="/avaliacoes"]', 'titulo' => 'Avaliações', 'texto' => 'Autoavaliação, avaliação dos colegas, do diretor e do representante.'],
            ['alvo' => '.proj-nav a[href$="/orcamento"]', 'titulo' => 'Orçamento', 'texto' => 'Quanto o projeto vai gastar, o que já foi gasto e os comprovantes.'],
            ['alvo' => 'a[href$="/portfolio.pdf"]', 'titulo' => 'Portfólio', 'texto' => 'Um PDF bonito do projeto inteiro, para guardar ou mostrar.'],
        ],
    ],
    'grupo' => [
        'titulo' => 'Grupo', 'icone' => 'fa-people-group', 'rota' => '^/grupos/\d+$', 'abrir' => '/grupos/{grupo}',
        'descricao' => 'Integrantes, diretores e o quadro de tarefas do grupo.',
        'passos' => [
            ['alvo' => '.hero-section', 'titulo' => 'Seu grupo', 'texto' => 'Integrantes e diretores. O diretor organiza o grupo e preenche as atas.'],
            ['alvo' => 'a[href$="/tarefas"]', 'titulo' => 'Tarefas', 'texto' => 'O quadro do grupo: o que fazer, quem faz e o prazo de cada tarefa.'],
        ],
    ],
    'kanban' => [
        'titulo' => 'Quadro de tarefas', 'icone' => 'fa-table-columns', 'rota' => '^/grupos/\d+/tarefas$', 'abrir' => '/grupos/{grupo}/tarefas',
        'descricao' => 'Criar tarefas, arrastar entre colunas e ver a participação de cada um.',
        'passos' => [
            ['alvo' => 'details.panel > summary', 'titulo' => 'Nova tarefa', 'texto' => 'Título, responsável (que recebe aviso), prazo e a ata em que foi combinada.'],
            ['alvo' => '.kb-quadro', 'titulo' => 'Colunas', 'texto' => 'Arraste os cartões de "A fazer" até "Feito". No celular, use as setas ‹ › do cartão.'],
            ['alvo' => '.kb-part', 'titulo' => 'Participação', 'texto' => 'Quantas tarefas cada um concluiu: ajuda o diretor na hora de avaliar.'],
        ],
    ],
    'agenda' => [
        'titulo' => 'Agenda', 'icone' => 'fa-calendar-days', 'rota' => '^/agenda$', 'abrir' => '/agenda',
        'descricao' => 'Ver por mês, semana ou lista, criar eventos da turma ou pessoais e as cores de prioridade.',
        'passos' => [
            ['alvo' => '.hero-section', 'titulo' => 'Sua agenda', 'texto' => 'Eventos de todas as suas turmas e os seus pessoais, num lugar só.'],
            ['alvo' => '[data-view]', 'titulo' => 'Jeitos de ver', 'texto' => 'Mês, semana ou lista. Atalhos: M, S e L.'],
            ['alvo' => '.ag-chips', 'titulo' => 'Prioridades', 'texto' => 'Verde baixa, azul média, laranja alta e vermelho urgente.'],
        ],
    ],
    'enquetes' => [
        'titulo' => 'Enquetes', 'icone' => 'fa-square-poll-vertical', 'rota' => '^/turmas/\d+/enquetes$', 'abrir' => '/turmas/{turma}/enquetes',
        'descricao' => 'Criar uma enquete, votar e ver o resultado.',
        'passos' => [
            ['alvo' => '.enq-nova', 'titulo' => 'Nova enquete', 'texto' => 'Pergunta, opções, se é anônima e quando aparece o resultado. A turma recebe aviso.'],
            ['alvo' => '.enq-lista', 'titulo' => 'Enquetes da turma', 'texto' => 'Abertas primeiro. Clique para votar; dá para mudar o voto enquanto estiver aberta.'],
        ],
    ],
    'caixa' => [
        'titulo' => 'Caixa da turma', 'icone' => 'fa-sack-dollar', 'rota' => '^/turmas/\d+/caixa$', 'abrir' => '/turmas/{turma}/caixa',
        'descricao' => 'Saldo, gráfico do mês, lançar com comprovante e contribuições.',
        'passos' => [
            ['alvo' => '.cx2-saldo', 'titulo' => 'Saldo', 'texto' => 'Quanto a turma tem agora, somando entradas e saídas.'],
            ['alvo' => '.cx2-grafico', 'titulo' => 'Últimos meses', 'texto' => 'Entradas e saídas por mês. Clique num mês para filtrar.'],
            ['alvo' => 'details.panel > summary', 'titulo' => 'Lançar', 'texto' => 'O representante lança entradas e saídas com o comprovante (foto ou PDF).'],
            ['alvo' => '.cx2-grade', 'titulo' => 'Lançamentos e contribuições', 'texto' => 'Todos veem os lançamentos e os comprovantes. Na direita, quem já contribuiu.'],
        ],
    ],
    'reuniao' => [
        'titulo' => 'Reunião e presença por QR', 'icone' => 'fa-qrcode', 'rota' => '^/reunioes/\d+$', 'abrir' => '/reunioes/{reuniao}',
        'descricao' => 'Pauta, ata e a presença marcada pelo QR Code que muda a cada 30 segundos.',
        'passos' => [
            ['alvo' => '.reu-qr', 'titulo' => 'Presença por QR', 'texto' => 'Abra o QR e projete: cada um escaneia e a presença é marcada na hora.'],
            ['alvo' => '#presenca', 'titulo' => 'Lista e ata', 'texto' => 'Confira a lista (quem veio pelo QR tem o selo) e escreva o que foi decidido.'],
        ],
    ],
    'preferencias' => [
        'titulo' => 'Notificações e emails', 'icone' => 'fa-bell', 'rota' => '^/notificacoes/preferencias$', 'abrir' => '/notificacoes/preferencias',
        'descricao' => 'Quais emails receber, o resumo da semana e as notificações no celular.',
        'passos' => [
            ['alvo' => '.pref-resumo', 'titulo' => 'Resumo da semana', 'texto' => 'Toda segunda, um email com o que vem pela frente nas suas turmas.'],
            ['alvo' => '#aparelho', 'titulo' => 'No seu aparelho', 'texto' => 'Ative para receber os avisos no celular ou no computador, mesmo com o site fechado.'],
        ],
    ],
];
