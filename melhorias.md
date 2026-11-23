# Melhorias do Prumo

Lista das melhorias aprovadas (todas, exceto a 14 — eleição de chapas do grêmio) e o que já foi feito.
Cada item feito tem **como testar** para você conferir. Legenda: ✅ feito e testado · 🔧 em andamento · ⬜ a fazer.

> Antes de testar, rode as migrações novas da pasta `database/` (as de **2026-11-23**) no banco, ou importe o `SGT.sql` atualizado
> (ele já vem com todas as tabelas e colunas novas).

| # | Melhoria | Situação |
|---|----------|----------|
| — | Landing page nova (3D, animações de rolagem, tema claro/escuro) | ✅ |
| 1 | Teste grátis de 14 dias do Dia a dia | ✅ |
| 18 | Aviso automático de renovação com cupom (e renovação de verdade) | ✅ |
| 6 | Enquetes e votações | ✅ |
| 10 | Caixinha anônima de dúvidas e sugestões | ✅ |
| 8 | Presença por QR Code nas reuniões | ✅ |
| 7 | Mural de fotos e conquistas | ✅ |
| 11 | Banco de materiais da turma por disciplina (ligado à agenda) | ✅ |
| 12 | Kanban de tarefas dentro do grupo | ✅ |
| 13 | Controle de caixa da turma/grêmio com comprovantes | ✅ |
| 15 | Projetos do grêmio com orçamento e prestação de contas | ✅ |
| 16 | Página pública do grêmio (sem login) | ✅ |
| 5 | Resumo semanal por email (segunda-feira) | ✅ |
| 4 | Relatório final do projeto em PDF (portfólio) | ✅ |
| 3 | Rateio da mensalidade entre os alunos | ✅ |
| 2 | Página "Para escolas" com simulador de pacote | ✅ |
| 17 | Painel de métricas do master | ✅ |
| 9 | App instalável (PWA) com notificação no celular | ✅ |
| 19 | esse pula, deixa pra depois Integração real com Mercado Pago | ⬜ | 
| — | Sistema de tutorial guiado (card na 1ª visita, destaque na tela, página /tutorial) | ✅ |

---

## Correções feitas no caminho

### ✅ Site lento ao clicar (login, criar, salvar)
**Causa:** com o email (SMTP) configurado, cada notificação mandava o email **durante** o clique — avisar a turma inteira esperava um email por
pessoa. E o resumo semanal automático (adicionado em 23/11) também enviava lotes no meio das requisições.
**Correção:** os emails das notificações agora vão para uma fila enviada **depois** que a página já respondeu (o clique volta na hora);
o disparo automático do resumo semanal foi removido (ele sai pelo script agendado ou pelo botão do master).
> Atenção: antes da correção, o resumo semanal automático chegou a enviar **30 emails reais** em 23/11 de manhã.

**Como testar:** crie um aviso ou uma enquete para a turma: a tela responde imediatamente.

### ✅ Contratar plano para uma turma que já existe
Ao escolher um plano (Preços → plano, ou "Criar minha turma"), quem é representante de uma turma **sem plano**, com plano vencido ou com
pedido aguardando pagamento vê no topo **"Contratar para uma turma que você já tem"**, com a lista das turmas. O clique leva à tela de plano
daquela turma com o plano escolhido já marcado — sem precisar criar turma nova. (O botão **Plano** no painel da turma continua lá.)

**Como testar:** com um representante de turma sem plano, abra **Preços** → escolha um plano → clique na turma que aparece no topo.

### ✅ Atualizações sem recarregar a página
Votar, marcar notificação como lida, responder a caixinha, publicar no mural, mexer nas tarefas, lançar no caixa e pagar a parte da
mensalidade agora acontecem **sem recarregar a página**: só o pedaço que mudou é trocado, com a mensagem de confirmação. E estas áreas
se **atualizam sozinhas** enquanto a página está aberta (pausam com a aba escondida ou enquanto você digita/arrasta ali):
resultado da enquete (10 s), lista de enquetes, notificações, caixinha, mural, quadro de tarefas e progresso da divisão da mensalidade.
A agenda/calendário e as curtidas já funcionavam assim; o sino de notificações atualiza a cada 30 s e logo depois de cada ação.
**Segurança/estabilidade:** o servidor não mudou; se o JavaScript falhar (rede, erro), o formulário é enviado do jeito normal.

**Como testar:** abra uma enquete em duas abas (com contas diferentes), vote numa e veja o resultado mudar na outra em até 10 s, sem recarregar.

### ✅ Animação "Como funciona" mais fluida e precisa
Cada passo ganhou ~1 tela de rolagem, as camadas se cruzam suavemente (sem trocas bruscas) e há um **"ímã"**: ao parar de rolar,
a página desliza até o passo mais próximo, centralizado. O indicador "Passo X de 4" aparece também no computador.

### ✅ Créditos na landing
No fim da página inicial: **"Criado e pensado por Jose Augusto Alves"**, com botões para o portfólio (josealvesdev.com) e o GitHub.

### ✅ Convite: só entra na turma/grêmio depois de aceitar
Antes, o aluno convidado já aparecia na lista assim que o convite saía. Agora ninguém entra no envio:
quem **não tem conta** entra quando cria a conta pelo link do email (senha ou Google); quem **já tem conta**
recebe uma notificação (no app e por email) e entra ao tocar em **"Entrar na turma"** — abrir o link sozinho não aceita.
Vale igual para grêmio (os textos dizem "grêmio").

**Como testar:** convide um email; veja que ele aparece só em "Convites aguardando resposta" e não na lista da turma; aceite pelo link e veja-o entrar.

### ✅ "Baixar o app" no topo e card na 1ª entrada
Na página inicial, logo abaixo dos botões principais, há **"Baixar o app · Android, iPhone e computador"**.
Dentro do sistema, na **primeira vez que a pessoa entra** (em cada aparelho), aparece um card "Leve o Prumo no celular"
com **Baixar o app / Agora não**. Não aparece se o app já está instalado e espera o card do tutorial fechar.

**Como testar:** entre numa janela anônima; ~2 s depois do painel abrir, o card aparece. Recarregue: não aparece de novo.

### ✅ Conectar a conta ao Google pelo perfil
Quem criou a conta com email e senha pode ir em **Minha conta → Dados da conta → Conta Google → "Conectar com Google"**.
Depois disso, entra pelos dois jeitos. Se a conta Google já estiver ligada a outro usuário, o sistema avisa e não conecta.
Também dá para **Desconectar** (aí entra só com email e senha).

**Como testar:** entre com email e senha, abra Minha conta, clique em "Conectar com Google", escolha a conta; saia e entre com o Google.

### ✅ Pacote "pronto para usuários reais"
- **Código no GitHub:** tudo commitado e enviado (o `descricao.md` virou o seu `readme.md`, com as seções novas de planos).
- **Publicação pelo Git na Hostinger:** passo a passo em `DEPLOY.md` (inclui o que fica fora do Git: `.env`, `vendor/`, uploads).
- **Senha do master:** o `SGT_producao.sql` cria o master com `primeiro_login = 1` → o sistema obriga a trocar a senha no 1º acesso.
- **Manter conectado (30 dias):** caixa marcada por padrão no login; com o Google fica conectado sempre. O código do aparelho muda a cada uso;
  um cookie copiado derruba todos os acessos daquela conta; sair ou trocar a senha desconecta. Tabela nova `login_lembrar`
  (migração `database/2026-11-24_lembrar_login.sql`).
- **Email:** porta 465 (SSL) funciona e há `MAIL_FROM` separado do login → dá para usar email da Hostinger, Brevo ou Resend (exemplos no `.env.example`).
- **Tarefas diárias:** `scripts/tarefas_diarias.php` (lembretes da agenda para todos, aviso de plano vencendo, limpeza). Cron em `DEPLOY.md`.
- **Backup:** `scripts/backup_banco.php` gera `backups/prumo_*.sql.gz` (guarda 8). Testado: restaurado com as 70 tabelas e as mesmas linhas.
- **Erros:** em produção os erros vão para `logs/php_errors.log` (antes nem eram gravados) e o usuário vê uma página amigável.
  Master → **Erros do sistema** mostra os erros agrupados. Ela já achou um erro real: o **PDF do boletim e o relatório geral em PDF**
  quebravam (`h()` indefinida) — corrigido e testado.
- **LGPD:** nova seção "Crianças e adolescentes" (art. 14, responsáveis, escolas, pedidos dos pais) e o cookie de "manter conectado" explicado.
- **Compartilhar o link:** imagem de prévia `public/img/share.png` (1200×630), endereços completos, `canonical`, dados estruturados.
- **Sem internet:** o app guarda as últimas 25 páginas abertas; sem conexão mostra a última versão com aviso no topo (e recarrega quando a rede volta).
  Login, pagamentos e convites nunca são guardados; as cópias somem ao sair da conta ou quando outra pessoa entra.
- **Animação "Como funciona":** tirado o "ímã" (puxava a página e travava a rolagem) e os filtros de desfoque (dobravam o custo de cada quadro).
  Os arquivos da landing agora levam a versão no endereço, para o navegador não usar a cópia antiga depois de atualizar.

**Como testar:** entre marcando "Manter conectado", feche o navegador e abra de novo (continua logado); como master, abra **Erros do sistema**;
baixe o boletim em PDF; no celular com o app, abra a agenda, ligue o modo avião e abra de novo.

---

## ✅ Landing page nova
**O que é:** página inicial para quem não está logado, com cena 3D "Como funciona" (as telas do Prumo se abrem em camadas ao rolar),
animações de rolagem em toda a página, efeitos de cursor e botão de tema claro/escuro.

**Como testar**
1. Saia da conta e abra `http://localhost/SGT/`.
2. Passe o mouse no topo (os pontos do fundo reagem; a janela do app gira) e role devagar até o fim.
3. Clique na lua/sol no menu para trocar o tema; entre no sistema e veja que o tema escolhido continua.
4. No celular: a cena "Como funciona" também roda, com a dica "Role para baixo para ver os 4 passos".

---

## ✅ 1. Teste grátis de 14 dias do Dia a dia
**O que é:** turmas com plano que não inclui o Dia a dia (Trimestral/Semestral) podem testar a agenda, horário, aviso do dia
e contagem regressiva por 14 dias, uma única vez. O master muda os dias em **Comercial** ("Teste grátis do Dia a dia").

**Como testar**
1. Com uma turma no plano Trimestral ou Semestral, entre como representante e abra **Turma → Plano** (`/turmas/{id}/plano`).
2. No bloco "Libere o Dia a dia", clique **Testar grátis por 14 dias** → vai para a agenda, já liberada.
3. Volte ao plano: aparece "Teste grátis ativo até dd/mm (faltam X dias)" e o botão de teste some (só uma vez por turma).
4. Para ver o aviso de fim: no banco, `UPDATE turmas SET teste_dia_ate = NOW() + INTERVAL 2 DAY WHERE id = ...;` e abra o painel da turma
   → aparece "Teste do Dia a dia acaba em 2 dias".

---

## ✅ 18. Aviso de renovação com cupom (e renovação de verdade)
**O que é:** antes não dava para renovar (o sistema dizia "a turma já tem um plano em andamento"). Agora:
- Quando faltam 7 dias (config "Avisar o representante antes do fim do plano"), a turma ganha um cupom exclusivo `RENOVA-XXXXXX`
  de 10% (config "Cupom automático de renovação" no Comercial), válido até o fim da tolerância, e os representantes recebem notificação/email.
- Na tela do plano aparece **Renovar o plano**, com o cupom já aplicado. Pago o Pix, o novo período começa **no fim do atual** (sem perder dias).
- O cupom só vale para a própria turma. A contagem de projetos do plano passa a ser do período atual.

**Como testar**
1. No banco, deixe o plano de uma turma perto do fim: `UPDATE assinaturas SET fim = NOW() + INTERVAL 5 DAY WHERE turma_id = ... AND status = 'ativa';`
2. Entre como representante e abra o plano: aparece o cupom `RENOVA-…` e o bloco "Renovar o plano"; chega uma notificação.
3. Escolha o plano e gere o Pix; em "Simular pagamento" confirme → o plano antigo vira "renovada" e o novo começa na data do fim do antigo.
4. Tente usar o mesmo cupom em outra turma → "exclusivo da renovação de outra turma".

---

## ✅ 6. Enquetes e votações
**O que é:** o representante (ou vice/presidente) cria enquetes; todos da turma votam. Opções: várias respostas, votos anônimos ou com nome,
resultado sempre / depois de votar / só ao encerrar, data de encerramento. Dá para mudar o voto enquanto está aberta.

**Como testar**
1. Painel da turma → atalho **Enquetes** → "Nova enquete": pergunta + opções (botão "Mais uma opção").
2. Os alunos recebem notificação; como aluno, abra a enquete, vote e mude o voto.
3. Com "Depois de votar", o resultado só aparece depois do voto. Como representante, clique **Encerrar votação**: a mais votada ganha o troféu.

---

## ✅ 10. Caixinha anônima
**O que é:** qualquer membro manda dúvida, sugestão, reclamação ou elogio; o representante lê **sem saber quem enviou**, responde e pode
publicar a pergunta com a resposta para a turma. Quem enviou recebe aviso da resposta. Limite: 5 mensagens por dia por pessoa.

**Como testar**
1. Como aluno: painel da turma → **Caixinha** → escolha o tipo, escreva e envie.
2. Como representante: abra a Caixinha (aparece "1 nova"), responda marcando "Publicar para a turma".
3. Como o aluno: ele recebe a notificação e vê a resposta em "Suas mensagens"; outro aluno vê em "Perguntas respondidas para a turma".
4. Confira que em nenhum lugar aparece o nome de quem enviou.

---

## ✅ 8. Presença por QR Code nas reuniões
**O que é:** na reunião, o organizador abre um QR de presença (10 min a 1h30) para projetar. Cada convocado escaneia e a presença é
marcada na hora. O QR **muda a cada 30 segundos** (foto mandada no grupo não funciona). A tela mostra quantos marcaram e os nomes, ao vivo.
Junto veio uma melhoria geral: **depois do login, o sistema volta para a página que você tentou abrir**.

**Como testar**
1. Turma → **Reuniões** → abra uma reunião → em Presença, escolha o tempo e clique **Abrir QR** → **Mostrar QR** / **Tela cheia**.
2. Pelo celular (na mesma rede, abrindo o sistema pelo IP do computador), escaneie o QR → "Presença confirmada!".
   Sem estar logado, ele pede login e volta direto para a confirmação.
3. Na tela do QR, o número sobe sozinho. Feche o QR: na lista da ata, quem veio pelo QR aparece marcado com o selo "QR".

---

## ✅ 7. Mural de fotos e conquistas
**O que é:** fotos e conquistas da turma (conquista pode ser sem foto, com ícone de troféu/medalha/estrela…). Representante publica direto;
o que os alunos enviam espera aprovação. Curtidas, fixar no topo, foto ampliada ao clicar.

**Como testar**
1. Como aluno: painel da turma → **Mural** → "Publicar no mural" → Foto (JPG/PNG/WEBP até 5 MB) → "Enviar para aprovação".
2. Como representante: no mural aparece "Esperando sua aprovação" → **Aprovar** (o aluno recebe aviso). Publicações do representante entram direto.
3. Publique uma **Conquista** sem foto e escolha o ícone (troféu, medalha…): vira um cartão dourado.
4. Clique no coração para curtir/descurtir (sem recarregar), clique na foto para ampliar, e no "…" para fixar no topo ou excluir.

---

## ✅ 11. Banco de materiais de estudo por disciplina
**O que é:** página **Estudos** da turma com arquivos (PDF, imagem, ZIP até 15 MB), links e resumos escritos, organizados por disciplina
(as do horário de aulas aparecem sozinhas). Cada material pode ajudar numa prova/entrega da agenda: a turma é avisada, e o evento na agenda
ganha o botão **Materiais de estudo (n)**. Busca por título/conteúdo. Quem compartilhou ou o representante remove.
(O antigo módulo "Materiais" continua sendo o controle de compras dos projetos.)

**Como testar**
1. Na agenda da turma, crie uma prova. Depois: painel da turma → **Estudos** → "Compartilhar material" → tipo, disciplina, título,
   e em "Ajuda em qual prova" escolha a prova → os colegas recebem notificação.
2. Clique na disciplina na lateral para filtrar; clique na prova em "Próximas provas e entregas" para ver só os materiais dela.
3. Abra a prova na **Agenda**: aparece o botão "Materiais de estudo (1)".
4. Teste os três tipos (arquivo abre/baixa com o nome original; link abre em nova aba; resumo abre em "Ler resumo").

---

## ✅ 12. Kanban de tarefas do grupo
**O que é:** cada grupo tem um quadro **A fazer → Fazendo → Revisão → Feito**. Tarefa com responsável (avisado por notificação), prazo
(fica vermelha quando vence) e a ata em que foi combinada. Arraste os cartões no computador; no celular use as setas ‹ ›.
Embaixo, **Participação no quadro** mostra quantas tarefas cada integrante concluiu (ajuda o diretor na avaliação).
Acesso: integrantes e diretores do grupo, representante/vice e master.

**Como testar**
1. Abra um grupo (Projetos → projeto → grupo) → botão **Tarefas**.
2. "Nova tarefa": título, responsável, prazo e (se houver) a ata → o responsável recebe notificação.
3. Arraste o cartão até "Feito" (ou use a seta ›) e recarregue: ele continua lá, riscado, e entra na participação.
4. Entre como um aluno que não é do grupo: o quadro é bloqueado.

---

## ✅ 13. Controle de caixa da turma/grêmio
**O que é:** página **Caixa** com saldo, entradas e saídas do mês, gráfico dos últimos 6 meses (clique num mês para filtrar),
lançamentos com categoria, data, **comprovante** (foto ou PDF) e, nas entradas, **quem pagou** — com a lista de contribuições e
(para o representante) quem ainda não contribuiu. Todos da turma veem tudo (transparência); só representante/vice/master lançam e removem.
No grêmio, o lançamento pode ser de uma diretoria. Botão **Exportar** gera CSV que abre no Excel.

**Como testar**
1. Como representante: painel da turma → **Caixa** → "Novo lançamento" → Entrada de `1.234,56`, quem pagou, anexe uma foto.
2. Lance uma Saída (ex.: `12,50`, Material): o saldo e o gráfico mudam.
3. Clique no clipe para abrir o comprovante; clique em **Exportar** para baixar o CSV.
4. Como aluno: vê saldo, lançamentos e comprovantes, mas não vê o formulário nem o botão de remover.

---

## ✅ 15. Orçamento dos projetos e prestação de contas
**O que é:** cada projeto ganhou a aba **Orçamento**: itens previstos, arrecadado, gasto, saldo e barra de uso (fica vermelha se passar).
Gastos e arrecadações do projeto entram no **Caixa** com comprovante (avisa quando falta comprovante). No grêmio, o presidente escolhe a
**diretoria responsável** pelo projeto, e o diretor/coordenadores dela também podem lançar. A página **Prestação de contas**
(botão no Caixa e no Orçamento) mostra tudo por diretoria e por projeto, mais o caixa geral, pronta para **Imprimir / PDF**.

**Como testar**
1. Abra um projeto → aba **Orçamento** → adicione itens previstos (ex.: Tinta R$ 80, Isopor R$ 40).
2. "Lançar no caixa do projeto": um gasto de R$ 150 sem comprovante → aparece "Passou do orçamento" e o alerta de comprovante.
3. No grêmio: escolha a diretoria responsável; entre como diretor dela e lance um gasto (um membro comum não consegue).
4. Clique em **Prestação de contas** → tabela por diretoria/projeto → **Imprimir / PDF**.

---

## ✅ 16. Página pública do grêmio
**O que é:** uma página aberta (sem login), nas cores e com o logo do grêmio, em `http://…/SGT/g/endereco-do-gremio`. O presidente
(ou vice/master) liga/desliga e escolhe o que aparece: apresentação, Instagram, **diretorias** (nomes abreviados, ex.: "Davi S."),
**próximos eventos e reuniões gerais**, **conquistas** do mural (sem fotos de pessoas) e **transparência** do caixa (saldo, arrecadado, gasto).
Desligada ou inexistente, a página responde "não encontrada". Pronta para compartilhar (título, descrição e imagem nas redes).

**Como testar**
1. Num grêmio, como presidente: painel → atalho **Página pública** → marque "Página no ar", escolha o endereço e o que mostrar → Salvar.
2. Clique em **Copiar link** e abra numa janela anônima (sem login): a página aparece.
3. Desmarque "Página no ar" e recarregue a janela anônima: "Página não encontrada".
4. Confira que não aparece email nem nome completo de ninguém.

---

## ✅ 5. Resumo semanal por email
**O que é:** toda segunda, cada pessoa recebe um email com a **agenda dos próximos 7 dias** (provas, entregas, eventos, reuniões),
**suas tarefas em aberto** do Kanban, **enquetes que ainda não votou**, **avisos da semana** e quantas notificações não leu.
Quem não tem nada para resumir não recebe; cada um recebe no máximo uma vez por semana; dá para desligar em **Notificações → Preferências**.

**Como sai**
- **Recomendado:** agende no Agendador de Tarefas do Windows, toda segunda às 7h:
  `C:\xampp\php\php.exe C:\xampp\htdocs\SGT\scripts\resumo_semanal.php`
- **Automático:** com o email (SMTP) configurado no `.env`, de segunda 7h até quarta cada acesso ao sistema envia um lote pequeno.
- **Na hora:** o master tem o botão **Enviar resumo agora** em Notificações → Preferências.

**Como testar**
1. Em **Notificações → Preferências**, clique em **Ver como fica o meu** (abre o email da semana).
2. Desmarque "Receber o resumo da semana", salve, e veja que você deixa de receber.
3. Sem SMTP configurado, os emails ficam registrados em `logs/mail.log` (nada é enviado de verdade).

---

## ✅ 4. Portfólio do projeto em PDF
**O que é:** um PDF de várias páginas, nas cores da turma: **capa** (nome, turma, período e números: alunos, grupos, atas, tarefas feitas,
critérios) e "Sobre o projeto"; **linha do tempo** das etapas e **critérios** com pesos; **uma página por grupo** (integrantes, direção,
tarefas concluídas no Kanban e lista das atas); **desempenho da turma** (distribuição dos conceitos e média geral, **sem notas individuais**)
e **orçamento**. Qualquer membro da turma pode baixar.

**Como testar**
1. Abra um projeto → botão **Portfólio (PDF)** (no topo), ou aba **Relatórios** → "Portfólio do projeto".
2. O PDF abre no navegador; use o botão de download do próprio leitor para salvar.
3. Confira que não aparece a nota de nenhum aluno, só a distribuição.

---

## ✅ 3. Rateio da mensalidade entre os alunos
**O que é:** o representante principal divide a **próxima parcela** do plano entre quem ele escolher (o valor por pessoa aparece na hora;
centavos que não dividem exato vão em algumas partes). Cada aluno recebe notificação, vê um aviso no painel da turma e paga **a sua parte**
pelo Pix. O representante acompanha quem já pagou, manda lembrete, pode **pagar o restante** ou cancelar (se ninguém pagou ainda).
Quando todas as partes estão pagas, a parcela é quitada sozinha (e o plano é ativado, se era a 1ª). Enquanto a parcela está dividida,
ela não pode ser paga de uma vez (evita pagar em dobro). Os alunos não veem quem já pagou, só o representante.

> No modo de teste o Pix de cada parte é simulado. Com a integração real do Mercado Pago (item 19), cada parte vira um Pix de verdade.

**Como testar**
1. Como representante, abra **Turma → Plano**: na próxima parcela, clique **Dividir** → escolha as pessoas → "Dividir e avisar a turma".
2. Entre como um aluno: aparece no painel da turma "Sua parte da mensalidade" → **Pagar minha parte** → simular.
3. Como representante, na divisão: veja quem pagou, use **Lembrar quem falta** ou **Pagar o restante**.
4. Quando a última parte é paga, a parcela aparece como paga na tela do plano.

---

## ✅ 2. Página "Para escolas" com simulador
**O que é:** página pública em `http://…/SGT/escolas` (link "Para escolas" no rodapé da página inicial): benefícios do pacote,
**simulador** (arraste o número de turmas e escolha a duração → preço por turma, total do pacote e economia, com a faixa de desconto
acendendo) e formulário **Pedir proposta** (protegido contra robôs e excesso de envios). Os pedidos chegam como notificação para o master
e aparecem em **Comercial → Escolas interessadas**, onde dá para marcar como Em contato / Fechado / Descartado.
Os descontos por volume (5+, 10+ e 20+ turmas) são editáveis em **Comercial → Configurações**.

**Como testar**
1. Sem login, abra `/escolas`, mexa no simulador e clique em "Pedir proposta com estes números" (o formulário já vem preenchido).
2. Envie o pedido → "Pedido recebido!".
3. Como master: abra **Comercial** → "Escolas interessadas" → mude a situação do pedido.

---

## ✅ 17. Painel de métricas do master
**O que é:** item **Métricas** no menu do master (`/master/metricas`):
- **Receita do mês** (com variação sobre o mês anterior), **a receber nos próximos 30 dias** e o que está em atraso;
- gráfico de **receita dos últimos 12 meses** e total desde o início (cortesias não contam);
- **turmas por situação do plano** (em dia, vencendo, pendência, sem plano, isentas), taxa de **conversão** (turmas novas que pagaram),
  taxa de **renovação** e testes do Dia a dia em andamento;
- **planos vendidos** e **cupons mais usados**;
- **uso dos recursos** nos últimos 30 dias (projetos, atas, avisos, agenda, reuniões, enquetes, tarefas, mural, estudos, caixa, caixinha);
- **usuários ativos** (hoje, 7 e 30 dias) e o funil das **escolas interessadas**.

**Como testar:** entre como master → menu **Métricas**.

---

## ✅ 9. App instalável (PWA) com notificações no aparelho
**O que é:** o Prumo pode ser **instalado** como app (ícone na tela inicial, abre em tela cheia, atalhos para Agenda e Notificações) e
mostra uma **página "sem internet"** quando a conexão cai. As **notificações do sistema também chegam no celular/computador** (Web Push),
mesmo com o site fechado — para quem ativar em **Notificações → Preferências → Notificações neste aparelho**. Clicar na notificação abre
a página certa. Feito sem biblioteca nova (criptografia RFC 8291 e chaves VAPID com o OpenSSL do PHP; chaves geradas no 1º uso).

> **Importante:** os navegadores só permitem instalar e receber push em **https** ou em **localhost**. Pelo IP do computador na rede
> (`http://192.168…`) o celular não deixa ativar. Para usar no celular de verdade, publique o site com https.

**Como testar (no computador, em http://localhost/SGT)**
1. Entre, vá em **Notificações → Preferências** → "Notificações neste aparelho" → **Ativar** → permita no navegador → chega um teste.
2. Crie um aviso/enquete para a turma com outra conta: a notificação aparece no canto da tela, mesmo com a aba fechada.
3. No Chrome/Edge, use o ícone de instalar na barra de endereço (ou o botão **Instalar o app** nas preferências).
4. Com o app aberto, desligue a internet e navegue: aparece "Você está sem internet".

---

## ✅ 19. Integração real com o Mercado Pago (Pix)
**O que é:** com a cobrança real ligada, cada parcela (e o adicional do Dia a dia) gera um **Pix de verdade** no Mercado Pago: QR Code e
copia e cola reais, válidos por 24 h. A confirmação chega sozinha pelo **webhook** do Mercado Pago (assinatura conferida; o sistema ainda
consulta a API e confere o valor antes de dar baixa) e, enquanto a tela do Pix está aberta, por uma **consulta automática a cada 5 s**
(funciona até em localhost). Confirmado, acontece o mesmo do pagamento simulado: ativa o plano, usa o cupom, credita a indicação e avisa.
O master liga/desliga em **Comercial** ("Ligar cobrança real (Mercado Pago)").
> O rateio da mensalidade (item 3) continua só no modo de teste: com a cobrança real, cada parte precisaria do próprio Pix (próximo passo).

**Para ligar (produção)**
1. No Mercado Pago: **Suas integrações → Credenciais** → copie o *Access Token* → no `.env`: `MP_ACCESS_TOKEN=APP_USR-...`
2. **Webhooks**: URL `https://SEU-DOMINIO/SGT/webhooks/mercadopago`, evento *Pagamentos* → copie a assinatura secreta → `MP_WEBHOOK_SECRET=...`
3. Garanta que o `APP_URL` do `.env` seja o endereço https do site.
4. Em **Comercial**, clique **Ligar cobrança real**.

**Como testar (com credenciais de TESTE do Mercado Pago)**
1. Ponha o Access Token de teste no `.env` e ligue a cobrança real em Comercial.
2. Escolha um plano → a tela do Pix mostra o QR real e "Aguardando o pagamento…".
3. Pague com um usuário de teste do Mercado Pago → em segundos a tela avisa "Pagamento recebido!" e o plano fica ativo.

---

## ✅ Sistema de tutorial guiado
**O que é:**
- **1ª visita a cada tela** (início, painel da turma, plano, projeto, grupo, quadro de tarefas, agenda, enquetes, caixa, reunião,
  preferências): aparece um **card grande no meio da tela** sugerindo o tour, com "Fazer o tour", "Agora não" e "Não sugerir tutoriais".
  A escolha fica salva na conta (vale em qualquer aparelho).
- **Tour:** a tela escurece, um **holofote** destaca a parte explicada e um **card ao lado** mostra o passo (no celular, o card fica
  embaixo). Voltar / Próximo / Concluir, setas do teclado e Esc. Passos de coisas que a pessoa não vê (por papel) são pulados.
- **Página Tutorial:** nova seção **"Tours guiados nas telas"** com um cartão por tour (desenho da tela, o que ele mostra e os passos)
  e o botão **Rodar tutorial**, que abre a tela certa (com as suas turmas/projetos) e começa na hora. Botão para **voltar a sugerir** os tours.
  Os guias em texto por papel e os exemplos de telas que já existiam continuam lá.
- Os tours ficam num arquivo só (`app/Config/tours.php`): para criar um novo, basta escrever a rota e os passos.

**Como testar**
1. Entre com uma conta e abra o painel de uma turma: aparece o card "Primeira vez aqui" → **Fazer o tour** e avance pelos passos.
2. Recarregue: o card não aparece de novo. Abra outra tela (ex.: Agenda): o card daquela tela aparece.
3. Vá em **Tutorial** (menu) → "Tours guiados nas telas" → **Rodar tutorial** em qualquer um.
4. Em Tutorial, clique **Voltar a sugerir os tours** para os cards voltarem.

