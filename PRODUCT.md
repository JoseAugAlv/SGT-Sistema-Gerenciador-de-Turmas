# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Primários: representantes e alunos.** O sistema existe para que os próprios estudantes se organizem. O representante coordena a turma ou o grupo (critérios, avaliações em massa, atas, materiais, alertas); o aluno acompanha notas, faz autoavaliação e avaliação de pares, consulta atas e recebe avisos.
- **Diretor de grupo:** aluno com papel extra em um grupo; preenche atas e avalia os membros.
- **Master:** administrador global único (criado via seed); nomeia representantes, faz backup, vê auditoria.
- Representantes e diretores continuam sendo alunos: aparecem em listas, avaliações e estatísticas.

## Product Purpose

Facilitar a organização de turmas e grupos estudantis (turmas de curso, grêmio estudantil e afins) em um único lugar: quem faz o quê, projetos e etapas, avaliações com critérios e pesos, registro de reuniões, materiais e histórico. Sucesso é o representante conseguir coordenar a turma sem planilhas e grupos de mensagem paralelos, e o aluno saber a qualquer momento o que precisa fazer e como está sendo avaliado.

## Positioning

Papéis contextuais em vez de cargos fixos: a mesma pessoa pode ser representante numa turma, aluna comum em outra e diretora de vários grupos ao mesmo tempo, e cada permissão vale só no seu escopo (`master > representante da turma > diretor do grupo > aluno`). A avaliação entre os próprios estudantes (pares, coletiva, autoavaliação, mista diretores/representantes) é parte central do produto, não um extra.

## Operating Context

- Acesso **principalmente pelo celular**; telas internas devem funcionar bem em tela pequena antes de tudo.
- Entrada em turma por código de acesso; auto-cadastro do aluno ou criação pelo representante/master (individual ou em massa).
- Ciclo de uma turma: projetos → etapas → grupos → critérios → avaliações → atas → encerramento do projeto com boletim congelado (snapshot).
- Documentos gerados: boletim individual (PDF), relatório geral do projeto, exportação Excel.
- E-mails transacionais: confirmação, primeiro acesso, recuperação de senha, notificações.
- Regras de negócio completas em `descricao.md`.

## Capabilities and Constraints

- Stack existente: PHP puro em MVC (sem framework), PDO, XAMPP/Apache, PHPMailer, TCPDF. Sem build de front-end; CSS e JS em `public/`.
- Organização do código por setor (um controller, um model e uma pasta de views por área). Mudanças pequenas; sem grandes refatorações.
- Módulos: turmas e papéis, projetos e etapas, grupos e diretores, critérios (6 tipos de aplicação, pesos, prazo, bloqueio/reabertura, arquivamento), avaliações, conceitos (I/R/B/MB ↔ valor), atas, materiais (estoque, uso, compra), alertas e notificações com preferências, relatórios, LGPD, auditoria, área do master, tutoriais.
- Máximo de 2 representantes ativos por turma; uma turma com representante nunca fica sem nenhum.
- Segurança obrigatória: bcrypt, CSRF em todo POST, rate limit no login, mensagens que não revelam se um e-mail existe.
- **Em aberto:** a idade mínima de 12 anos no cadastro é só precaução e pode ser removida.

### Modelo de negócio: SaaS (planejado)

O produto será disponibilizado como **SaaS**. O sistema deve ficar preparado para:
- **Planos e preços** (assinatura). O "bloqueio de turma" já existente no código é o ponto de partida para restringir turmas sem plano ativo.
- **Login com Google** além de e-mail e senha.
- **Promoções e descontos:** lógica de controle (cupons, períodos promocionais, descontos por plano).

**Fonte da verdade de preços e regras comerciais: `precos.md`** (planos de turma e de grêmio, adicionais, parcelas, cupom, indicação, atraso, bloqueio, arrependimento).

**Decidido:**
- **Quem paga:** principalmente o **representante**, pela turma.
- **Cobrança por turma**, não por aluno. Mostrar o valor dividido pelos alunos (ex.: ÷ 40) é só um argumento de venda, para o preço parecer acessível quando a turma toda entra.
- **Dois produtos:** planos de **turma** (até 50 alunos; do Trimestral ao Vitalício, limitados por meses de acesso e número de projetos) e planos de **grêmio** (anual, por faixa de pessoas).
- **Pagamento somente por Pix**, via **Mercado Pago** numa etapa futura. Até lá, planos, descontos, cupons e códigos de resgate devem funcionar sem gateway.
- **Descontos editáveis na área do master:** percentuais de cupom, indicação e pacote de escola, pisos mínimos, validade e regras não ficam fixos no código.
- **Cupons:** um cupom criado dá desconto na compra (regras em `precos.md`). Descontos não acumulam: vale o maior.
- **Indicação (só turmas):** a turma indicada ganha desconto na 1ª compra; quem indicou ganha meses grátis.
- **Pacote de escola com códigos de resgate:** a escola escolhe **qualquer plano de turma** (ex.: todas as turmas no Trimestral) e compra um pacote para suas turmas. Ela recebe **um código de resgate por turma**; o representante de cada turma usa o código para ativar o plano, sem pagar.
  - O **desconto do pacote é definido pelo master, caso a caso**, para cada escola (negociação personalizada, não tabela fixa).
  - A **validade dos códigos de resgate** é definida pelo master numa área editável.

**Ainda não decidido** (não inventar): período de teste e a data limite do cupom (`[DATA LIMITE]` em `precos.md`).

## Brand Commitments

- Nome do produto: **Prumo** (sozinho, sem complemento). Tom sério e profissional, em português. "SGT — Sistema de Gestão de Turmas" é o nome antigo, ainda presente no código e nos textos; a troca para Prumo está pendente.
- Idioma: português do Brasil, em todo o sistema.

## Evidence on Hand

- Página pública `app/Views/home/landing.php` com painel de exemplo (dados ilustrativos, como "3º Informática A").
- Tutoriais com telas de exemplo em `app/Views/tutorial/`.
- Não há depoimentos, clientes, escolas parceiras nem números de uso reais; nenhum deve ser inventado.

## Product Principles

1. **Feito para estudantes se organizarem.** A linguagem e os fluxos servem quem não é especialista em gestão: claros, diretos, sem jargão administrativo.
2. **Celular primeiro.** Toda tarefa frequente de representante e aluno precisa ser confortável no telefone.
3. **Cada um vê o que é seu.** Permissão e informação sempre no escopo do papel naquela turma ou grupo.
4. **Avaliação transparente e justa.** Critérios, pesos, prazos e regras de cálculo visíveis para quem é avaliado, dentro das regras de visibilidade.
5. **Tudo registrado.** Ações críticas auditadas, boletim congelado no encerramento, nada some sem rastro.
6. **Pronto para ser vendido.** Novas funcionalidades devem considerar planos, limites e o bloqueio por assinatura desde o início.

## Accessibility & Inclusion

- Público inclui adolescentes (a partir de 12 anos); textos simples e objetivos.
- Conformidade com LGPD: consentimento obrigatório e direitos do titular (exportação e solicitação de exclusão).
