# Documento de Lógica e Processos — Sistema de Gestão de Turmas

> **Instruções para a IA implementadora:** este documento descreve **toda** a lógica de negócio definida na entrevista. Implemente seguindo MVC estrito, usando PDO com prepared statements, senhas com `password_hash` (bcrypt, cost 12), CSRF em todos os POST, validação server-side de todas as regras abaixo e auditoria em ações críticas.

---

## 1. PAPÉIS E CONTEXTO

### 1.1 Modelo de papéis

- **Conta base** (`usuarios.tipo`): sempre `aluno` ou `master`. **Não existem** tipos fixos "representante" ou "diretor" na tabela `usuarios`.
- **Representante**: papel dentro de uma **turma** (`turma_usuarios.papel = 'representante'`). Vale para **todos** os projetos/grupos daquela turma.
  - **Cada turma tem no máximo 2 representantes ativos simultaneamente.** Tentar nomear um 3º bloqueia com erro ("Turma já possui 2 representantes"). Uma turma, depois de ter ao menos um representante nomeado, **nunca deve ficar sem nenhum**: o master não pode remover o **último** representante restante sem nomear outro na mesma operação (ver 3.1 e 14.6).
  - `atas.representante_id` e demais campos que referenciam "o representante" funcionam como **referência simples, sem prioridade entre os dois** — qualquer um dos dois representantes ativos pode ocupar esse campo (ex.: quem criou a ata).
  - Para critérios `tipo_avaliacao = 'representante'`, quando há 2 representantes ativos, **ambos avaliam** e a nota final do critério é a **média entre os dois**. Para `tipo_avaliacao = 'misto'`: `nota = média(diretores) × 0.5 + média(representantes) × 0.5`.
- **Diretor**: papel dentro de um **grupo** (`grupo_diretores.ativo = 1`).
- Um mesmo aluno pode:
  - Ser representante na turma A e aluno comum na turma B;
  - Ser diretor em vários grupos simultaneamente;
  - Ser aluno comum e diretor ao mesmo tempo;
  - Ser diretor de um grupo **e**, ao mesmo tempo, membro comum de outro grupo do mesmo projeto;
  - Estar em várias turmas com papéis diferentes em cada uma.
- **Master**: único, global, criado via seed. Só ele nomeia/remove representantes.
- **Representante** nomeia/remove **diretores** dos grupos da sua turma.
- **Representantes e diretores também são alunos** — aparecem nas avaliações, estatísticas e listas.
- Toda promoção/rebaixamento gera registro em `auditoria_log`.

### 1.2 Hierarquia de permissões

```
master > representante (da turma) > diretor (do grupo) > aluno comum
```

Papel de representante **não** dá poder em outra turma. Papel de diretor **não** dá poder em outro grupo.

---

## 2. AUTENTICAÇÃO

### 2.1 Regras gerais

1. Login por **email + senha**. Senha com bcrypt (cost 12). Nunca MD5.
2. **Rate limit**: máx. 5 tentativas / 5 min por IP + email.
3. **CSRF token** obrigatório em todo POST.
4. Sessão regenerada no login (`session_regenerate_id(true)`).
5. Credenciais inválidas → mensagem genérica ("Email ou senha incorretos"). Nunca revelar se o email existe.

### 2.2 Bloqueios automáticos

- Se `email_confirmado = 0` → redirect para `/confirmar-email` (exceto rotas públicas).
- Se `primeiro_login = 1` → redirect para `/primeiro-acesso` (trocar senha obrigatoriamente).
- Se o usuário estiver tentando acessar rota de turma e `turmas.bloqueada = 1` e não for master → página de aviso "Turma bloqueada".

### 2.3 Fluxo 1 — Auto-cadastro do aluno

1. Aluno acessa `/cadastro`, preenche: nome, email, senha, telefone (opcional), data de nascimento (opcional), **aceita LGPD** (checkbox obrigatório — se desmarcar, não cria conta).
2. Validações:
   - Nome com pelo menos 3 caracteres.
   - Email válido (`filter_var`) e não cadastrado.
   - Senha forte (mín. 8 caracteres, 1 maiúscula, 1 minúscula, 1 número, 1 caractere especial).
   - Se data de nascimento informada: idade mínima 12 anos.
   - LGPD aceito.
3. Sistema cria `usuarios` com:
   - `tipo = 'aluno'`, `email_confirmado = 0`, `primeiro_login = 1`, `ativo = 1`;
   - `email_token` = `bin2hex(random_bytes(32))`;
   - `email_token_expira` = agora + 24h;
   - `lgpd_aceito = 1`, `lgpd_data = agora`.
4. Sistema cria `preferencias_usuario` com defaults ligados.
5. Sistema envia email com link `/confirmar-email?token=xxx`.
6. Aluno clica no link:
   - Token válido + não expirado → `email_confirmado = 1`, apaga `email_token` e `email_token_expira`.
   - Token expirado → erro, oferece reenvio.
   - Token inválido → erro genérico.
7. Aluno faz login → é redirecionado para `/primeiro-acesso` (trocar senha) e depois para `/turmas/entrar`.
8. Aluno digita código da turma:
   - Código válido → insere em `turma_usuarios` com `papel = 'aluno'`.
   - Código inválido → erro "Código de turma inválido".
   - Já está na turma → mensagem "Você já está nesta turma".
9. Pronto, aluno vê dashboard.

### 2.4 Fluxo 2 — Representante cria aluno

1. Representante acessa `/alunos/criar`, informa nome + email.
2. Sistema cria `usuarios` com:
   - `senha` = senha temporária gerada (`PasswordHelper::generateTemp()`, 10 chars);
   - `email_confirmado = 0`, `primeiro_login = 1`, `ativo = 1`;
   - `lgpd_aceito = 0` (será aceito no primeiro acesso);
   - `email_token` gerado (24h).
3. Sistema insere **automaticamente** o aluno em `turma_usuarios` com a turma do representante e `papel = 'aluno'`.
4. Sistema envia email com link `/primeiro-acesso?token=xxx`.
5. Aluno acessa:
   - Confirma email;
   - Troca senha (obrigatório);
   - Aceita LGPD (obrigatório);
   - Preenche telefone/data nascimento (opcional).
6. Só então libera o acesso ao sistema.

### 2.5 Fluxo 3 — Master cria aluno

Igual ao fluxo 2, mas o master pode escolher **uma ou mais turmas** para alocar automaticamente (multi-select). O restante é idêntico.

### 2.6 Fluxo 4 — Criação em massa (representante ou master)

1. Representante cola uma lista de nomes (um por linha) em `/alunos/massa`.
2. Para cada linha:
   - Valida nome (mín. 3 chars);
   - Gera email único (`nome@aluno.edu`, com sufixo numérico se já existir);
   - Gera senha temporária;
   - Cria usuário com `email_confirmado = 0`, `primeiro_login = 1`;
   - Insere em `turma_usuarios` na turma atual.
3. Ao final, mostra resumo: X criados, Y erros (com nomes).
4. Sistema dispara emails de primeiro acesso em lote.

### 2.7 Recuperação de senha

1. Aluno informa email em `/recuperar-senha`.
2. **Sempre** responde "Se o e-mail existir, você receberá as instruções" (mesma mensagem exista ou não — evita enumeração de contas).
3. Se existir: gera `reset_senhas.token` (32 bytes hex), `expira_em = agora + 2h`, envia email.
4. Aluno clica em `/redefinir-senha?token=xxx`:
   - Token válido + não usado + não expirado → mostra formulário de nova senha.
   - Senha passa por `SecurityHelper::validarForcaSenha()`.
   - Ao salvar: `usuarios.senha = hash`, `reset_senhas.usado = 1`.
5. Envia email de confirmação "Sua senha foi alterada".

### 2.8 Primeiro acesso

1. Se `primeiro_login = 1`, middleware redireciona para `/primeiro-acesso`.
2. Formulário bloqueante (não dá para pular) com 3 etapas:
   - Etapa 1: confirmar email (se ainda não confirmado);
   - Etapa 2: trocar senha (nova + confirmação);
   - Etapa 3: aceitar LGPD (checkbox + link para `/lgpd`).
3. Ao concluir: `primeiro_login = 0`, `lgpd_aceito = 1`, `lgpd_data = agora`, `email_confirmado = 1`.
4. Redireciona para `/` (dashboard).

### 2.9 Logout

1. Limpa `$_SESSION`, apaga cookie de sessão, `session_destroy()`.
2. Redireciona para `/`.

---

## 3. TURMAS

### 3.1 Criação

- Apenas **master** cria turmas.
- Campos: código escola (numérico), ano/módulo, curso (sigla), período, código de acesso, cor primária, cor secundária.
- Nome é montado: `{codigo_escola}-{ano_modulo}-{curso}-{periodo}`.
- Código de acesso é único no banco.
- A turma nasce sem alunos (eles ingressam depois — ver 3.4), então não há representante no momento da criação. O master deve nomear ao menos 1 representante (até 2) assim que houver alunos elegíveis na turma; a partir daí vale a regra de nunca remover o último representante sem substituí-lo (ver 1.1).

### 3.2 Código de acesso

- Fixo por turma.
- **Master** ou **representante** pode regenerar (invalida o antigo automaticamente — o antigo deixa de funcionar para novos ingressos, mas quem já está na turma continua).

### 3.3 Bloqueio (SaaS)

- Apenas **master** bloqueia/desbloqueia.
- Ao bloquear: `bloqueada = 1`, `bloqueada_motivo`, `bloqueada_em`, `bloqueada_por`.
- Efeito: qualquer usuário não-master que tente acessar recursos da turma vê tela "Turma bloqueada. Contate o administrador."
- Sistema **não** cobre pagamentos nem verificação financeira. Master gerencia fora do sistema e clica em "bloquear/desbloquear" manualmente.

### 3.4 Entrada em turma

- **Aluno já logado** pode ingressar com código de acesso em `/turmas/entrar`.
- **Representante/master** pode adicionar aluno manualmente à turma.
- Um aluno pode estar em várias turmas com papéis diferentes.

---

## 4. PROJETOS E ETAPAS

### 4.1 Projetos

- Criados pelo **master** para uma turma.
- Campos: nome, descrição, prazo, `modo_avaliacao` (`etapa` ou `cronograma`).
- `modo_avaliacao = 'etapa'` → critérios podem ser vinculados a etapas.
- `modo_avaliacao = 'cronograma'` → etapas são apenas controle de progresso.

### 4.2 Etapas

- Criadas/editadas pelo **representante**.
- Campos: nome, descrição, data início, data fim, ordem (livre).
- **Podem ser editadas ou adicionadas a qualquer momento**, inclusive no meio do caminho.
- Reordenação via drag-n-drop.
- Remover etapa: se houver critério vinculado (`etapa_id`), o `etapa_id` do critério vira `NULL` (não apaga o critério).

### 4.3 Encerramento de projeto

- Pode ser feito pelo **representante da turma** ou pelo **master**.
- Ação **irreversível** (confirmação dupla).
- Ao encerrar:
  1. `projetos.encerrado = 1`, `encerrado_em = agora`, `encerrado_por = user_id`.
  2. Dispara `SnapshotService::congelarBoletim($projeto_id)`:
     - Para cada aluno com nota no projeto, grava em `boletins_snapshot` o JSON com todas as notas + média ponderada + conceito final.
  3. A partir daí, qualquer leitura usa o snapshot (não muda mais, mesmo se editar avaliação).
  4. Master pode forçar recálculo manual (opcional — não bloqueia edição mas o snapshot fica até ser regerado).

---

## 5. GRUPOS

### 5.1 Criação

- Criados pelo **representante** dentro de um projeto.
- Campos: nome, `modo_avaliacao_grupo` (individual/coletiva), `modo_avaliacao_por` (diretor/representante/pares/autoavaliacao/misto).

### 5.2 Membros

- Um aluno pode estar em **mais de um grupo do mesmo projeto**.
- Adição/remoção via `grupo_alunos`.
- Ao remover, registra `saiu_em` (mantém histórico).

### 5.3 Diretores

- Nomeados pelo **representante**.
- Guardados em `grupo_diretores` com histórico (`ativo = 0` quando removido).
- **Troca de diretor no meio do processo:**
  - Diretor antigo: `ativo = 0`, `removido_em = agora`, `removido_por = quem removeu`.
  - Diretor novo: `ativo = 1`, `nomeado_por = quem nomeou`.
  - **Novo diretor herda o poder de editar** as avaliações feitas pelo antigo naquele grupo.
  - Avaliações antigas **não são apagadas** — `avaliador_id` original é mantido.
  - **Novo diretor herda também as atas pendentes**: toda ata com `status = 'pendente'` daquele grupo tem `diretor_id` reatribuído automaticamente para o novo diretor (ver 8.1). Atas já `preenchida`/`revisada` mantêm o `diretor_id` original como registro histórico.
  - Auditoria registra a troca (e a reatribuição de atas).
- **Reverter diretor a aluno**: simplesmente desativa o vínculo **daquele grupo específico** em `grupo_diretores` (`ativo = 0`, `removido_em`, `removido_por`). Não existe bloqueio nem checagem de "está vinculado a outro grupo" — como diretor não é um estado do usuário (é apenas uma ou mais linhas ativas em `grupo_diretores`, uma por grupo), desativar a linha de um grupo não afeta as linhas de outros grupos onde a mesma pessoa ainda é diretora. Não há nada em `usuarios.tipo` para reverter (esse campo nunca reflete o papel de diretor — ver 1.1).

### 5.4 Modo de avaliação do grupo

- `individual`: cada aluno recebe nota separadamente (padrão).
- `coletiva`: o grupo recebe **uma nota única** (armazenada em `avaliacoes_coletivas`).

### 5.5 Quem aplica o critério

- `diretor`: os diretores ativos do grupo avaliam.
- `representante`: o representante da turma avalia.
- `pares`: alunos do grupo avaliam uns aos outros.
- `autoavaliacao`: o aluno se avalia.
- `misto`: combina diretor + representante (média 50/50).

> **Importante:** `modo_avaliacao_grupo` e `modo_avaliacao_por` (campos do **grupo**) servem **apenas como valor padrão pré-preenchido** no formulário de criação de um novo critério naquele grupo — são um atalho de UI, nada mais. Não há herança em tempo de execução: `criterios.tipo_avaliacao` é obrigatório (`NOT NULL`) e é **sempre** ele quem determina como aquele critério específico é avaliado, independentemente do que estiver configurado no grupo. Não há validação cruzada entre os dois — um grupo com `modo_avaliacao_grupo = 'individual'` pode perfeitamente ter um critério com `tipo_avaliacao = 'coletiva'`.

---

## 6. CRITÉRIOS

### 6.1 Campos

- `nome`, `descricao`, `peso` (decimal, ideal soma = 10 por projeto).
- `tipo_avaliacao`: **diretor / representante / pares / autoavaliacao / coletiva / misto**.
- `aplicavel_a`: **todos / apenas_diretores / apenas_representantes**.
- `prazo_avaliacao` (datetime).
- `etapa_id` (opcional, se projeto em modo etapa).

> **Mapeamento de nomenclatura (importante para a implementação):** `criterios.tipo_avaliacao` e `avaliacoes.tipo` usam valores diferentes para os mesmos conceitos, porque o segundo tem um `ENUM` mais curto. A conversão é fixa:
> | `criterios.tipo_avaliacao` | `avaliacoes.tipo` |
> |---|---|
> | `diretor` | `diretor` |
> | `representante` | `representante` |
> | `pares` | `par` |
> | `autoavaliacao` | `auto` |
> | `misto` | `misto` |
> `coletiva` não gera linhas em `avaliacoes` — usa a tabela `avaliacoes_coletivas` (sem coluna `tipo`).

### 6.2 Soma de pesos

- Sistema **não bloqueia** se ultrapassar 10, mas mostra aviso visual:
  - `incompleto` (falta completar);
  - `completo` (soma = 10);
  - `excedido` (soma > 10).

### 6.3 Prazo e bloqueio

- Após `prazo_avaliacao`, o critério é considerado bloqueado. O bloqueio efetivo (o que a aplicação checa antes de aceitar uma avaliação) é: `bloqueado_efetivo = (prazo_avaliacao < agora) AND (bloqueado = 1)`.
- A coluna `criterios.bloqueado` **não é o interruptor manual do critério** — ela nasce em `0` e o sistema a define para `1` automaticamente assim que detecta, em qualquer leitura, que `prazo_avaliacao` já passou (job ou verificação on-read, tanto faz; o importante é que o valor persistido reflita o estado real). Isso existe só para permitir listar/filtrar critérios bloqueados sem recalcular datas toda hora (ex. no painel do master).
- **Reabertura**: somente **master** ou o **ocupante atual do cargo** (representante atual da turma ou diretor atual do grupo) pode reabrir. Reabrir seta `bloqueado = 0` **e** define um novo `prazo_avaliacao` (obrigatório informar a nova data/hora no ato da reabertura — não é permitido reabrir sem novo prazo, senão o critério voltaria a `bloqueado = 1` no próximo request).
- Reabertura grava `reaberto_por` e `reaberto_em`.

### 6.4 Exclusão com avaliações

- Ao clicar em excluir, se existirem avaliações:
  1. Serializa todas as avaliações do critério em JSON.
  2. Salva em `avaliacoes_arquivadas` com `expira_em = hoje + 30 dias`.
  3. Só então remove o critério (CASCADE remove as avaliações).
  4. Durante 30 dias, master pode restaurar (re-inserir a partir do JSON).
  5. Auditoria registra tudo.
- Se **não houver** avaliações, exclui direto (com confirmação).

### 6.5 Tipos de avaliação e regras

**`diretor`**:
- Os diretores **ativos** do grupo avaliam.
- Se o aluno está em **mais de um grupo do mesmo projeto**, o sistema coleta avaliações de **todos** os diretores ativos de **todos** os grupos do aluno naquele projeto e calcula a **média**.
- Se o aluno avaliado **é ele mesmo o único diretor** do grupo, a avaliação daquele critério **passa automaticamente para o representante** da turma.
- Se um critério `diretor` tem **2 diretores**, a nota final = **média** entre eles.

**`representante`**:
- Os representantes **ativos** da turma avaliam (podem ser 1 ou 2 — ver 1.1).
- Se houver 2 representantes ativos, ambos avaliam e a nota final do critério = **média** entre eles.
- `avaliacoes.avaliador_id` identifica qual dos dois representantes fez cada avaliação; ambos usam `tipo = 'representante'` (a `UNIQUE KEY (criterio_id, aluno_id, avaliador_id, tipo)` já permite as duas linhas coexistirem, pois `avaliador_id` é diferente).

**`pares`**:
- Cada aluno do grupo avalia **cada outro aluno** (menos a si mesmo).
- **Justificativa obrigatória**.
- Justificativa é **interna** — visível apenas para representante/diretor.
- Nota final do critério = **média** das notas dadas pelos pares.

**`autoavaliacao`**: o próprio aluno se avalia.

**`coletiva`**:
- Uma única nota por grupo.
- Armazenada em `avaliacoes_coletivas`.
- Todos os membros do grupo recebem essa mesma nota.

**`misto`**: `nota = média(avaliações dos diretores) × 0.5 + média(avaliações dos representantes) × 0.5`. Cada metade usa sua própria regra de agregação (diretor: média entre todos os diretores ativos aplicáveis; representante: média entre 1 ou 2 representantes ativos).

### 6.6 Visibilidade

- Aluno vê **suas** notas (conceito + valor). **Não vê** justificativas de pares nem quem avaliou.
- Representante/Diretor vê **tudo**, incluindo justificativas.
- Master vê tudo.

### 6.7 Edição de avaliação antiga (troca de cargo)

- Novo ocupante do cargo pode editar avaliações antigas.
- `editado_por` / `editado_em` gravados.
- Auditoria registrada.

---

## 7. AVALIAÇÕES — FLUXOS

### 7.1 Avaliação em massa (representante)

> Quando a turma tem 2 representantes ativos, cada um faz login e opera esse fluxo de forma independente — a tela mostra/edita apenas as avaliações cujo `avaliador_id` é o representante logado no momento (por isso o "Limpar e Salvar" do item 9 nunca apaga as avaliações do outro representante).

1. Representante seleciona um projeto.
2. Vê tabela: linhas = alunos da turma (incluindo rep e diretores); colunas = critérios.
3. Colunas separadas visualmente:
   - **Azul**: critérios aplicáveis a **todos**.
   - **Laranja**: critérios **apenas para diretores**.
4. Cada célula tem select I/R/B/MB + input numérico.
5. Ao mudar o conceito, o valor numérico é preenchido automaticamente (conforme `configuracoes_conceito` do projeto).
6. Ao mudar o valor, o conceito é recalculado.
7. Botões de ação rápida: preencher I/R/B/MB em massa, preencher valores (0, 25, 50, 60, 70, 80, 85, 90, 95, 100), limpar tudo.
8. **Salvar**: insere ou atualiza avaliações (unique `criterio_id, aluno_id, avaliador_id, tipo`).
9. **Limpar e Salvar**: remove **todas** as avaliações do representante para aquele projeto antes de re-salvar (ação com confirmação dupla).

### 7.2 Avaliação pelo diretor

1. Diretor seleciona um dos grupos que dirige.
2. Vê tabela: linhas = alunos do grupo; colunas = critérios com `tipo_avaliacao = 'diretor'`.
3. **Não pode se autoavaliar** (se for aluno do grupo). Célula dele fica bloqueada com aviso.
4. Se houver **outros diretores** no grupo, mostra as avaliações deles na mesma célula (transparência).
5. Salvar insere/atualiza avaliações.

### 7.3 Avaliação por pares (aluno avalia colega)

1. Aluno acessa `/avaliacoes/avaliar/{criterio_id}` — só aparece se:
   - O critério é `tipo_avaliacao = 'pares'`;
   - O prazo não passou;
   - O aluno ainda não avaliou todos os colegas.
2. Lista os colegas do grupo (menos ele mesmo).
3. Para cada colega: select I/R/B/MB + justificativa (obrigatória).
4. Envio em lote.
5. Justificativa fica visível apenas para rep/diretor.

### 7.4 Avaliação coletiva

1. Se o grupo tem `modo_avaliacao_grupo = 'coletiva'`, o representante (ou diretor, dependendo da config) dá **uma nota única** ao grupo.
2. Armazena em `avaliacoes_coletivas`.
3. Todos os membros recebem a mesma nota naquele critério.

### 7.5 Autoavaliação

1. Aluno acessa `/avaliacoes/avaliar` quando existe critério `autoavaliacao` aberto.
2. Seleciona I/R/B/MB para si mesmo.
3. Salva.

### 7.6 Cálculo de notas

**Nota do critério** (por aluno):
```
nota_criterio = média(avaliações do aluno para aquele critério)
```
- Se for `coletiva`: nota do grupo.
- Se for `pares`: média das avaliações dos pares.
- Se for `diretor` com múltiplos diretores: média entre eles.
- Se for `representante` com os 2 representantes ativos: média entre eles.
- Se for `misto`: `média(diretor) × 0.5 + média(representante) × 0.5`.

**Nota final do projeto** (por aluno):
```
média_ponderada = Σ(nota_critério × peso) / Σ(peso)
conceito_final = converter(média_ponderada, config_conceito.projeto)
```

**Enquanto o projeto está aberto:** cálculo em tempo real.

**Quando o projeto é encerrado:** usa snapshot (congelado).

### 7.7 Conversão conceito ↔ valor

Configurável por projeto em `configuracoes_conceito`:
- `I`: 0 até `limite_i_max`.
- `R`: `limite_r_min` até `limite_r_max`.
- `B`: `limite_b_min` até `limite_b_max`.
- `MB`: `limite_mb_min` até 100.

Ao selecionar um conceito, o valor numérico padrão:
- `I` → `limite_i_max`;
- `R` → média de `limite_r_min` e `limite_r_max`;
- `B` → média de `limite_b_min` e `limite_b_max`;
- `MB` → `limite_mb_min`.

Ao digitar um valor, o conceito é recalculado automaticamente pela faixa em que se encaixa.

---

## 8. ATAS

### 8.1 Criação (representante)

1. Representante acessa `/atas/criar`.
2. Formulário: título, grupo, data da ata, prazo de preenchimento, descrição.
3. Sistema vincula automaticamente:
   - `diretor_id` = **qualquer** diretor ativo do grupo (obrigatório existir ao menos um; não há critério de prioridade entre diretores — o campo é só uma referência de FK, não define hierarquia nem "responsabilidade principal");
   - `representante_id` = o representante logado que está criando a ata (se a turma tiver 2 representantes ativos, é sempre quem executou a ação; também sem prioridade entre os dois).
4. Status inicial: `pendente`.
5. Sistema cria notificação para o diretor.
6. **Reatribuição automática:** se o diretor referenciado em `diretor_id` for trocado (ver 5.3) enquanto a ata ainda está `pendente`, o sistema atualiza `diretor_id` para o novo diretor ativo do grupo e registra a mudança em auditoria. Atas já `preenchida` ou `revisada` **não** são reatribuídas — mantêm o `diretor_id` original como registro histórico de quem de fato preencheu.

### 8.2 Preenchimento (diretor)

1. Diretor acessa `/atas/detalhe/{id}` de uma ata `pendente`.
2. Pode:
   - Adicionar **atividades** (nome, descrição, participantes, materiais usados).
   - Adicionar **relatórios** (título, tema, descrição, participantes mencionados).
   - Temas de relatório: falta de compromisso, não ajudou, colaborou sendo de outro grupo, liderança, proatividade, dificuldade técnica, conflito, entrega fora do prazo, qualidade do trabalho, outro.
3. Ao finalizar, clica em "Finalizar Ata" → status vira `preenchida`.
4. Depois disso, **não pode mais editar** (a menos que o representante reabra).

### 8.3 Validação (representante)

1. Representante vê atas `preenchida`.
2. Revisa atividades e relatórios.
3. Pode:
   - **Validar** → status vira `revisada`.
   - **Editar** (só se `pendente`) → volta o status se necessário.
   - **Excluir**.

### 8.4 Visualização (aluno)

1. Aluno vê apenas atas em que participou (`ata_participantes`).
2. Vê suas presenças (presente/justificado/ausente + justificativa).
3. Vê atividades em que foi marcado.

---

## 9. MATERIAIS

### 9.1 Cadastro

- Representante ou diretor cadastra material.
- Campos: nome, quantidade inicial, unidade, preço unitário, projeto.
- Ao cadastrar, gera movimentação `tipo_movimentacao = 'cadastro'`.

### 9.2 Uso (retirada)

- Usuário seleciona material, quantidade, observação.
- Validações:
  - Material existe;
  - Quantidade > 0;
  - Quantidade ≤ estoque disponível.
- Efeito:
  - `materiais.quantidade -= qtd_usada`;
  - Gera movimentação `tipo_movimentacao = 'uso'`.

### 9.3 Compra

- Usuário seleciona material, quantidade comprada, preço unitário, observação.
- Efeito:
  - Calcula **preço médio ponderado**:
    ```
    valor_atual = quantidade_atual × preco_atual
    valor_compra = qtd_comprada × preco_compra
    nova_qtd = quantidade_atual + qtd_comprada
    novo_preco = (valor_atual + valor_compra) / nova_qtd
    ```
  - Atualiza `quantidade` e `preco`;
  - Gera movimentação `tipo_movimentacao = 'compra'`.

### 9.4 Histórico

- Lista movimentações com filtros (projeto, material, tipo, período).
- Mostra: data, material, tipo, quantidade, preço unitário, usuário, cargo, observação.

---

## 10. ALERTAS E NOTIFICAÇÕES

### 10.1 Alertas (representante)

- Representante cria alerta com:
  - Título, mensagem;
  - Escopo: turma inteira / projeto específico / grupo específico;
  - `urgente` (checkbox) → envia email **mesmo para quem desativou notificações**;
  - `expira_em` (opcional).
- Sistema cria **uma notificação para cada usuário do escopo**.
- Alerta fica visível na tela de alertas de todos os usuários do escopo.

### 10.2 Notificações

- Tipos: `alerta`, `prazo`, `avaliacao`, `papel`, `ata`, `material`, `lgpd`.
- Cada notificação tem `lida` (0/1) e `lida_em`.
- Usuário pode marcar uma ou todas como lidas.
- Contador de não lidas via AJAX (polling a cada 30s).
- Dropdown no topo mostra as últimas 10.

### 10.3 Preferências

- Usuário controla em `/user/preferencias`:
  - Receber email geral (on/off);
  - Receber email de prazos (on/off);
  - Receber email de alertas (on/off);
  - Receber email de avaliações (on/off).
- **Alertas urgentes ignoram essas preferências** (sempre enviam email).

### 10.4 Notificações automáticas

- Prazo de critério se aproximando (3 dias antes).
- Novo alerta.
- Novo papel atribuído.
- Nova avaliação recebida.
- Ata pendente de preenchimento (para diretor).
- Ata preenchida aguardando validação (para representante).

---

## 11. LGPD

### 11.1 Consentimento

- Checkbox **obrigatório** no cadastro. Se não aceitar → não cria conta.
- Armazena `lgpd_aceito = 1` + `lgpd_data = agora`.

### 11.2 Páginas públicas

- `/sobre` — o que é o sistema, funcionalidades, tecnologias.
- `/termos` — termos de uso em seções numeradas.
- `/lgpd` — política de privacidade, direitos do titular, contato do encarregado.

### 11.3 Direitos do titular

- **Exportar dados**: gera JSON com todos os dados do usuário (perfil, avaliações, atas, notificações). Registra pedido em `lgpd_solicitacoes` com `tipo = 'exportacao'`, gera arquivo, envia por email.
- **Solicitar exclusão**: cria ticket. Master aprova ou nega. Se aprovar, exclusão real é feita mantendo logs anonimizados. Registra em `lgpd_solicitacoes` com `tipo = 'exclusao'`.

---

## 12. RELATÓRIOS

### 12.1 Boletim individual

- **Aluno**: vê **sempre e somente o próprio** boletim, em qualquer configuração.
- **Representante e master**: veem o boletim de **qualquer** aluno da turma, sempre — não são afetados pela configuração de visibilidade abaixo.
- **Diretor** e **aluno-agindo-como-aluno-comum** (quando não está atuando como diretor) têm sua visibilidade de boletins **alheios** controlada por uma configuração que **o representante define por projeto**, com 3 níveis:
  - `turma`: vê o boletim de qualquer aluno da turma;
  - `grupo`: vê o boletim apenas de alunos que estão em algum grupo do projeto em que ele também está (como diretor ou como membro);
  - `proprio`: vê apenas o próprio boletim.
  - A regra é aplicada **por papel/contexto**: um aluno que é diretor de um grupo e membro comum de outro usa a config de "diretor" quando está olhando a partir do contexto de grupo que dirige, e a de "aluno" quando está olhando como membro comum — ambas configuráveis independentemente.
  - Persistência: duas colunas em `configuracoes_conceito` — `visibilidade_diretor ENUM('turma','grupo','proprio') DEFAULT 'grupo'` e `visibilidade_aluno ENUM('turma','grupo','proprio') DEFAULT 'proprio'` (ver schema atualizado).
- Mostra por projeto:
  - Critérios com peso, conceito, valor;
  - Média ponderada;
  - Conceito final.
- Exportação PDF via `PdfHelper`.
- Se projeto encerrado, usa snapshot.

### 12.2 Relatório geral do projeto

- Representante gera.
- Tabela: alunos × critérios com médias.
- Ranking (opcional).
- Exportação PDF/Excel.

### 12.3 Exportação Excel

- Listagens de alunos, avaliações, movimentações de materiais.
- Geração via `ExcelHelper` (HTML table → .xls).

---

## 13. AUDITORIA

### 13.1 O que registrar

- Login/logout.
- Criação/edição/exclusão de usuário.
- Mudança de papel (representante, diretor).
- Troca de diretor (quem saiu, quem entrou).
- Reabertura de critério.
- Exclusão de critério (com avaliações).
- Restauração de critério arquivado.
- Bloqueio/desbloqueio de turma.
- Encerramento de projeto (com snapshot).
- Edição de avaliação antiga por novo ocupante do cargo.
- Exportação de dados LGPD.
- Exclusão de dados LGPD.

### 13.2 Estrutura

- `auditoria_log`: `usuario_id`, `acao`, `tabela_afetada`, `registro_id`, `dados_anteriores` (JSON), `dados_novos` (JSON), `ip`, `user_agent`, `created_at`.

### 13.3 Retenção

- Mínimo 12 meses. Não deletar.

---

## 14. MASTER — ÁREA EXCLUSIVA

### 14.1 Painel

- Estatísticas globais: total de turmas, usuários, projetos.
- Atalhos para: backup, auditoria, critérios arquivados, configurações.

### 14.2 Backup

- Exportar banco completo (SQL).
- Download e exclusão de backups antigos.
- Módulo ativável/desativável via `config/modules.php`.

### 14.3 Auditoria

- Lista com filtros: usuário, ação, tabela, período.
- Detalhe mostra JSON antes/depois, IP, user-agent.

### 14.4 Critérios arquivados

- Lista critérios excluídos com avaliações.
- Mostra `expira_em` (janela de 30 dias).
- Botão "Restaurar" re-insere critério e avaliações.
- Após restaurar, `restaurado = 1`.

### 14.5 Configurações

- Informações do sistema (versão PHP, MySQL, path).
- Configurações gerais (nome da escola, cor padrão).

### 14.6 Nomear representantes

- Único que pode nomear/remover representantes.
- Ao nomear: insere/atualiza em `turma_usuarios` com `papel = 'representante'`. Máximo de **2 representantes ativos por turma** — a 3ª tentativa é bloqueada com erro ("Turma já possui 2 representantes").
- Ao remover: volta para `papel = 'aluno'`. Se a turma já tiver representante(s) nomeado(s) alguma vez, **não é permitido remover o último representante ativo restante** sem nomear outro na mesma operação (a turma não pode ficar sem nenhum representante).
- Auditoria registrada.

---

## 15. SEGURANÇA — CHECKLIST DE IMPLEMENTAÇÃO

Para **toda** página nova:

1. Rota tem os `roles` corretos?
2. Formulário POST tem `ViewHelper::csrfField()`?
3. Controller valida CSRF com `CsrfMiddleware::validate()`?
4. Todo dado do `$_POST`/`$_GET` é validado antes de usar?
5. Toda saída nas Views passa por `htmlspecialchars()`?
6. Toda query usa **prepared statements** (nunca concatenação direta)?
7. Se envolve edição/exclusão de um registro específico, checa se o usuário tem permissão sobre **aquele** registro (não só o role)?
8. Ações sensíveis geram log de auditoria?
9. Upload de arquivo valida MIME real (`finfo`), extensão e tamanho?
10. Diretório de uploads tem `.htaccess` bloqueando execução de PHP?

### 15.1 Proteções já implementadas no projeto

| Proteção | Onde |
|---|---|
| Token CSRF | `CsrfMiddleware` |
| Comparação com `hash_equals()` | CSRF, reset de senha |
| `password_hash()` / `password_verify()` | Todo login/senha |
| Prepared statements (PDO) | Todos os Models |
| `htmlspecialchars()` | Todas as Views |
| Validação MIME real | `UploadHelper` / `SecurityHelper` |
| `.htaccess` em uploads | `public/uploads/.htaccess` |
| Rate limiting no login | `RateLimiter` |
| Resposta idêntica em "esqueci senha" | `AuthController@enviarToken` |
| Logs de auditoria | `log_sistema` / `auditoria_log` |
| Whitelist de colunas em ORDER BY | Listagens com ordenação |
| Ownership check | Editar/excluir registros próprios |
| `display_errors` off em prod | `public/index.php` |
| Headers de segurança | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` |

---

## 16. FLUXOS CRÍTICOS — RESUMO

### 16.1 Fluxo de avaliação por diretor (com múltiplos grupos)

1. Aluno X está nos grupos G1 e G2 do projeto P.
2. G1 tem diretores D1 e D2. G2 tem diretor D3.
3. Critério C é `tipo_avaliacao = 'diretor'`.
4. Sistema coleta:
   - D1 avalia X;
   - D2 avalia X;
   - D3 avalia X.
5. Nota final do critério C para X = **média(D1, D2, D3)**.

### 16.2 Fluxo de autoavaliação do diretor

1. Aluno X é diretor único do grupo G.
2. Critério C é `tipo_avaliacao = 'diretor'` e aplicável a "todos".
3. X não pode se autoavaliar.
4. Sistema **automaticamente** delega para o representante da turma avaliar X nesse critério.
5. Avaliação salva com `avaliador_id = representante_id`, `tipo = 'representante'`.

### 16.3 Fluxo de troca de diretor

1. Diretor D1 (nomeado em 01/03) sai em 15/04.
2. Representante nomeia D2 em 16/04.
3. `grupo_diretores`: D1 vira `ativo = 0`, D2 vira `ativo = 1`.
4. D2 pode:
   - Editar avaliações que D1 fez entre 01/03 e 15/04.
   - Fazer novas avaliações.
5. `editado_por` registra quem editou.
6. Auditoria registra troca.

### 16.4 Fluxo de exclusão de critério com avaliações

1. Representante clica em "Excluir" no critério C.
2. Sistema verifica se há avaliações em C.
3. Se sim:
   - Serializa em JSON;
   - Salva em `avaliacoes_arquivadas` com `expira_em = hoje + 30 dias`;
   - Mostra aviso "Critério será arquivado por 30 dias. Após isso, será removido definitivamente".
   - Ao confirmar, remove C.
4. Durante 30 dias, master pode restaurar.
5. Após 30 dias, pode ser limpo por job.

### 16.5 Fluxo de encerramento de projeto

1. Representante clica em "Encerrar Projeto".
2. Confirmação dupla: "Tem certeza? Isso congela o boletim de todos".
3. Para cada aluno com nota:
   - Calcula média ponderada;
   - Calcula conceito final;
   - Salva em `boletins_snapshot`.
4. `projetos.encerrado = 1`.
5. A partir daí, leituras usam snapshot.
6. Edições de avaliação **não** alteram o snapshot (a menos que master force recálculo).

### 16.6 Fluxo de bloqueio de turma (SaaS)

1. Master acessa turma.
2. Clica em "Bloquear" com motivo.
3. `turmas.bloqueada = 1`, `bloqueada_motivo`, `bloqueada_em`, `bloqueada_por`.
4. Qualquer requisição não-master a recursos da turma:
   - Middleware `TurmaBloqueadaMiddleware` intercepta;
   - Retorna página "Turma bloqueada. Contate o administrador."
5. Master desbloqueia quando quiser.

---

## 17. ORDEM DE IMPLEMENTAÇÃO SUGERIDA

1. **Core** (Router, Controller, Model, Database, View, Session, Config).
2. **Auth** (cadastro, login, confirmação, reset, primeiro acesso).
3. **Turmas** + `turma_usuarios` + papéis.
4. **Projetos** + Etapas.
5. **Grupos** + `grupo_alunos` + `grupo_diretores`.
6. **Critérios** + `configuracoes_conceito`.
7. **Avaliações** (todos os tipos) + `AvaliacaoService` + `NotaService`.
8. **Snapshot** + Relatórios PDF.
9. **Atas** + Materiais.
10. **Alertas** + Notificações + `EmailService`.
11. **Páginas públicas** (sobre/termos/LGPD) + exportação de dados.
12. **Auditoria** + painel master.

---

**Fim do documento.** Este material é auto-suficiente para uma IA implementar o sistema em MVC seguindo o padrão do ProjetoBase, sem herdar nenhuma lógica legada.


## Estrutura:
ª   -
ª   .env
ª   .env.example
ª   .gitignore
ª   .htaccess
ª   codigo_completo.txt
ª   composer.json
ª   composer.lock
ª   descricao.md
ª   ProjetoBase_bd.sql
ª   
+---app
ª   +---Config
ª   ª       config.php
ª   ª       database.php
ª   ª       menu.php
ª   ª       modules.php
ª   ª       SessionConfig.php
ª   ª       
ª   +---Controllers
ª   ª       AlertaController.php
ª   ª       AlunoController.php
ª   ª       AtaController.php
ª   ª       AuthController.php
ª   ª       AvaliacaoController.php
ª   ª       ConceitoController.php
ª   ª       CriterioController.php
ª   ª       DashboardController.php
ª   ª       DiretorController.php
ª   ª       EtapaController.php
ª   ª       ExemploController.php
ª   ª       GrupoController.php
ª   ª       HomeController.php
ª   ª       LgpdController.php
ª   ª       LogController.php
ª   ª       MasterController.php
ª   ª       MaterialController.php
ª   ª       NotificacaoController.php
ª   ª       OperadorController.php
ª   ª       ProjetoController.php
ª   ª       RelatorioController.php
ª   ª       RelatoriosController.php
ª   ª       SobreController.php
ª   ª       TermosController.php
ª   ª       TurmaController.php
ª   ª       TutorialController.php
ª   ª       UserController.php
ª   ª       UsuarioController.php
ª   ª       
ª   +---Core
ª   ª       App.php
ª   ª       Auth.php
ª   ª       BaseController.php
ª   ª       CrudController.php
ª   ª       DynamicCrudHelper.php
ª   ª       Mail.php
ª   ª       Model.php
ª   ª       Perfil.php
ª   ª       Router.php
ª   ª       
ª   +---Helpers
ª   ª       ExcelHelper.php
ª   ª       menuHelper.php
ª   ª       NavHelper.php
ª   ª       PaginationHelper.php
ª   ª       PasswordHelper.php
ª   ª       pdfHelper.php
ª   ª       RateLimiter.php
ª   ª       SecurityHelper.php
ª   ª       UploadHelper.php
ª   ª       ViewHelper.php
ª   ª       
ª   +---Middleware
ª   ª       AuthMiddleware.php
ª   ª       CsrfMiddleware.php
ª   ª       
ª   +---Models
ª   ª       Alerta.php
ª   ª       Ata.php
ª   ª       AtividadeAta.php
ª   ª       Auditoria.php
ª   ª       Avaliacao.php
ª   ª       AvaliacaoArquivada.php
ª   ª       AvaliacaoColetiva.php
ª   ª       BoletimSnapshot.php
ª   ª       ConfiguracaoConceito.php
ª   ª       Criterio.php
ª   ª       Etapa.php
ª   ª       Exemplo.php
ª   ª       Grupo.php
ª   ª       GrupoAluno.php
ª   ª       GrupoDiretor.php
ª   ª       Home.php
ª   ª       LogSistema.php
ª   ª       Material.php
ª   ª       MovimentacaoMaterial.php
ª   ª       Notificacao.php
ª   ª       Projeto.php
ª   ª       RelatorioAta.php
ª   ª       Turma.php
ª   ª       TurmaUsuario.php
ª   ª       Usuario.php
ª   ª       
ª   +---Views
ª       +---alertas
ª       ª       criar.php
ª       ª       dropdown.php
ª       ª       index.php
ª       ª       
ª       +---alunos
ª       ª       criar.php
ª       ª       editar.php
ª       ª       grupos.php
ª       ª       index.php
ª       ª       massa.php
ª       ª       
ª       +---atas
ª       ª       criar.php
ª       ª       detalhe.php
ª       ª       editar.php
ª       ª       index_aluno.php
ª       ª       index_diretor.php
ª       ª       index_rep.php
ª       ª       preencher.php
ª       ª       validar.php
ª       ª       
ª       +---auth
ª       ª       criar.php
ª       ª       esqueci_senha.php
ª       ª       index.php
ª       ª       primeiro_acesso.php
ª       ª       redefinir_senha.php
ª       ª       verificar.php
ª       ª       
ª       +---avaliacoes
ª       ª       avaliar_pares.php
ª       ª       coletiva.php
ª       ª       index_aluno.php
ª       ª       index_diretor.php
ª       ª       index_rep.php
ª       ª       parcial.php
ª       ª       pares.php
ª       ª       
ª       +---conceitos
ª       ª       index.php
ª       ª       preview.php
ª       ª       
ª       +---criterios
ª       ª       criar.php
ª       ª       editar.php
ª       ª       index.php
ª       ª       reabrir.php
ª       ª       
ª       +---diretores
ª       ª       alocar.php
ª       ª       historico.php
ª       ª       index.php
ª       ª       promover.php
ª       ª       trocar.php
ª       ª       
ª       +---erros
ª       ª       403.php
ª       ª       404.php
ª       ª       500.php
ª       ª       
ª       +---etapas
ª       ª       editar.php
ª       ª       index.php
ª       ª       
ª       +---grupos
ª       ª       config_avaliacao.php
ª       ª       criar.php
ª       ª       detalhe.php
ª       ª       editar.php
ª       ª       index.php
ª       ª       
ª       +---home
ª       ª       dashboard.php
ª       ª       dashboard_aluno.php
ª       ª       dashboard_diretor.php
ª       ª       dashboard_master.php
ª       ª       dashboard_representante.php
ª       ª       index.php
ª       ª       
ª       +---layouts
ª       ª       flashes.php
ª       ª       footer.php
ª       ª       header.php
ª       ª       nav.php
ª       ª       sidebar.php
ª       ª       
ª       +---lgpd
ª       ª       index.php
ª       ª       
ª       +---logs
ª       ª       detalhe.php
ª       ª       index.php
ª       ª       
ª       +---master
ª       ª       auditoria.php
ª       ª       backup.php
ª       ª       configuracoes.php
ª       ª       criterios_arquivados.php
ª       ª       index.php
ª       ª       restaurar_criterio.php
ª       ª       usuarios.php
ª       ª       
ª       +---materiais
ª       ª       cadastrar.php
ª       ª       comprar.php
ª       ª       index.php
ª       ª       movimentacoes.php
ª       ª       usar.php
ª       ª       
ª       +---notificacoes
ª       ª       dropdown.php
ª       ª       index.php
ª       ª       
ª       +---operador
ª       ª       atividades.php
ª       ª       
ª       +---projetos
ª       ª       criar.php
ª       ª       detalhe.php
ª       ª       editar.php
ª       ª       encerrar.php
ª       ª       index.php
ª       ª       
ª       +---relatorios
ª       ª       boletim_aluno.php
ª       ª       boletim_aluno_pdf.php
ª       ª       exportar_excel.php
ª       ª       geral_projeto.php
ª       ª       geral_projeto_pdf.php
ª       ª       index.php
ª       ª       usuarios.php
ª       ª       
ª       +---sobre
ª       ª       index.php
ª       ª       
ª       +---termos
ª       ª       termos.php
ª       ª       
ª       +---turmas
ª       ª       bloquear.php
ª       ª       criar.php
ª       ª       detalhe.php
ª       ª       editar.php
ª       ª       entrar.php
ª       ª       index.php
ª       ª       
ª       +---tutorial
ª       ª       index.php
ª       ª       
ª       +---user
ª       ª       editar.php
ª       ª       index.php
ª       ª       preferencias.php
ª       ª       senha.php
ª       ª       
ª       +---usuarios
ª               criar.php
ª               editar.php
ª               index.php
ª               
+---backups
ª       .htaccess
ª       
+---database
+---logs
ª       auditoria.log
ª       
+---public
ª   ª   .htaccess
ª   ª   index.php
ª   ª   
ª   +---css
ª   ª       auth.css
ª   ª       crud.css
ª   ª       footer.css
ª   ª       home.css
ª   ª       login.css
ª   ª       logs.css
ª   ª       master.css
ª   ª       nav.css
ª   ª       notificacoes.css
ª   ª       sobre.css
ª   ª       style.css
ª   ª       termos.css
ª   ª       tutorial.css
ª   ª       user.css
ª   ª       usuarios.css
ª   ª       
ª   +---js
ª   ª       main.js
ª   ª       nav.js
ª   ª       validar-senha.js
ª   ª       
ª   +---uploads
ª       ª   .htaccess
ª       ª   
ª       +---image
ª               logo.ico
ª               
+---routes
ª       web.php
ª       
+---vendor

## BANCO SQL
-- =====================================================================
-- SISTEMA DE GESTÃO DE TURMAS — SCHEMA COMPLETO
-- Charset: utf8mb4 / Engine: InnoDB
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1. USUÁRIOS E AUTENTICAÇÃO
-- =====================================================================

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  tipo ENUM('aluno','master') NOT NULL DEFAULT 'aluno',
  telefone VARCHAR(20) DEFAULT NULL,
  data_nascimento DATE DEFAULT NULL,
  email_confirmado TINYINT(1) NOT NULL DEFAULT 0,
  email_token VARCHAR(255) DEFAULT NULL,
  email_token_expira DATETIME DEFAULT NULL,
  primeiro_login TINYINT(1) NOT NULL DEFAULT 1,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  lgpd_aceito TINYINT(1) NOT NULL DEFAULT 0,
  lgpd_data DATETIME DEFAULT NULL,
  ultimo_acesso DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_usuarios_email (email),
  INDEX idx_usuarios_tipo (tipo),
  INDEX idx_usuarios_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reset_senhas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  expira_em DATETIME NOT NULL,
  usado TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_reset_token (token),
  INDEX idx_reset_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE preferencias_usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL UNIQUE,
  receber_email TINYINT(1) DEFAULT 1,
  receber_email_prazos TINYINT(1) DEFAULT 1,
  receber_email_alertas TINYINT(1) DEFAULT 1,
  receber_email_avaliacoes TINYINT(1) DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 2. TURMAS E PAPÉIS (N:N)
-- =====================================================================

CREATE TABLE turmas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  codigo_acesso VARCHAR(30) NOT NULL UNIQUE,
  codigo_interno VARCHAR(100) DEFAULT NULL,
  cor_primaria VARCHAR(20) DEFAULT '#3498db',
  cor_secundaria VARCHAR(20) DEFAULT '#2ecc71',
  bloqueada TINYINT(1) NOT NULL DEFAULT 0,
  bloqueada_motivo VARCHAR(255) DEFAULT NULL,
  bloqueada_em DATETIME DEFAULT NULL,
  bloqueada_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (bloqueada_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_turmas_codigo (codigo_acesso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Limite de no máximo 2 linhas com papel='representante' e ativo=1 por turma_id é
-- validado na aplicação (não há constraint de banco para isso) — ver 1.1 e 14.6.
CREATE TABLE turma_usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  usuario_id INT NOT NULL,
  papel ENUM('aluno','representante') NOT NULL DEFAULT 'aluno',
  ativo TINYINT(1) DEFAULT 1,
  entrou_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  saiu_em DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unico_turma_usuario (turma_id, usuario_id),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_tu_papel (papel),
  INDEX idx_tu_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 3. PROJETOS E ETAPAS
-- =====================================================================

CREATE TABLE projetos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  nome VARCHAR(150) NOT NULL,
  descricao TEXT DEFAULT NULL,
  prazo DATE DEFAULT NULL,
  modo_avaliacao ENUM('etapa','cronograma') DEFAULT 'cronograma',
  encerrado TINYINT(1) DEFAULT 0,
  encerrado_em DATETIME DEFAULT NULL,
  encerrado_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (encerrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_projetos_turma (turma_id),
  INDEX idx_projetos_encerrado (encerrado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE projeto_etapas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL,
  nome VARCHAR(150) NOT NULL,
  descricao TEXT DEFAULT NULL,
  data_inicio DATE DEFAULT NULL,
  data_fim DATE DEFAULT NULL,
  ordem INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
  INDEX idx_etapas_projeto (projeto_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 4. GRUPOS (com histórico de diretores)
-- =====================================================================

CREATE TABLE grupos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL,
  nome VARCHAR(150) NOT NULL,
  modo_avaliacao_grupo ENUM('individual','coletiva') DEFAULT 'individual',
  modo_avaliacao_por ENUM('diretor','representante','pares','autoavaliacao','misto') DEFAULT 'diretor',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
  INDEX idx_grupos_projeto (projeto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE grupo_alunos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  usuario_id INT NOT NULL,
  entrou_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  saiu_em DATETIME DEFAULT NULL,
  UNIQUE KEY unico_grupo_aluno (grupo_id, usuario_id),
  FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_ga_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- usuario_id usa ON DELETE SET NULL (em vez de CASCADE) para preservar o histórico de quem
-- foi diretor de cada grupo (exigido em 5.3) mesmo que o usuário seja removido via LGPD.
CREATE TABLE grupo_diretores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  usuario_id INT DEFAULT NULL,
  ativo TINYINT(1) DEFAULT 1,
  nomeado_por INT DEFAULT NULL,
  nomeado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  removido_por INT DEFAULT NULL,
  removido_em DATETIME DEFAULT NULL,
  FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (nomeado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (removido_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_gd_grupo_ativo (grupo_id, ativo),
  INDEX idx_gd_usuario (usuario_id),
  INDEX idx_gd_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 5. CRITÉRIOS (tipo por critério + etapa opcional)
-- =====================================================================

CREATE TABLE criterios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL,
  etapa_id INT DEFAULT NULL,
  nome VARCHAR(200) NOT NULL,
  descricao TEXT DEFAULT NULL,
  peso DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  tipo_avaliacao ENUM(
    'diretor',
    'representante',
    'pares',
    'autoavaliacao',
    'coletiva',
    'misto'
  ) NOT NULL DEFAULT 'diretor',
  aplicavel_a ENUM('todos','apenas_diretores','apenas_representantes') DEFAULT 'todos',
  prazo_avaliacao DATETIME DEFAULT NULL,
  bloqueado TINYINT(1) DEFAULT 0,
  reaberto_por INT DEFAULT NULL,
  reaberto_em DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
  FOREIGN KEY (etapa_id) REFERENCES projeto_etapas(id) ON DELETE SET NULL,
  FOREIGN KEY (reaberto_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_crit_projeto (projeto_id),
  INDEX idx_crit_etapa (etapa_id),
  INDEX idx_crit_tipo (tipo_avaliacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 6. CONFIGURAÇÃO DE CONCEITOS (por projeto)
-- =====================================================================

CREATE TABLE configuracoes_conceito (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL UNIQUE,
  limite_i_max INT NOT NULL DEFAULT 49,
  limite_r_min INT NOT NULL DEFAULT 50,
  limite_r_max INT NOT NULL DEFAULT 69,
  limite_b_min INT NOT NULL DEFAULT 70,
  limite_b_max INT NOT NULL DEFAULT 84,
  limite_mb_min INT NOT NULL DEFAULT 85,
  visibilidade_diretor ENUM('turma','grupo','proprio') NOT NULL DEFAULT 'grupo',
  visibilidade_aluno ENUM('turma','grupo','proprio') NOT NULL DEFAULT 'proprio',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 7. AVALIAÇÕES
-- =====================================================================

CREATE TABLE avaliacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  criterio_id INT NOT NULL,
  aluno_id INT NOT NULL,
  avaliador_id INT DEFAULT NULL,
  tipo ENUM('diretor','representante','par','auto','misto') NOT NULL,
  conceito ENUM('I','R','B','MB') NOT NULL,
  valor_numerico DECIMAL(6,2) NOT NULL,
  justificativa TEXT DEFAULT NULL,
  editado_por INT DEFAULT NULL,
  editado_em DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unico_avaliacao (criterio_id, aluno_id, avaliador_id, tipo),
  FOREIGN KEY (criterio_id) REFERENCES criterios(id) ON DELETE CASCADE,
  FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (avaliador_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (editado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_aval_criterio (criterio_id),
  INDEX idx_aval_aluno (aluno_id),
  INDEX idx_aval_avaliador (avaliador_id),
  INDEX idx_aval_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE avaliacoes_coletivas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  criterio_id INT NOT NULL,
  grupo_id INT NOT NULL,
  avaliador_id INT NOT NULL,
  conceito ENUM('I','R','B','MB') NOT NULL,
  valor_numerico DECIMAL(6,2) NOT NULL,
  justificativa TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unico_aval_coletiva (criterio_id, grupo_id, avaliador_id),
  FOREIGN KEY (criterio_id) REFERENCES criterios(id) ON DELETE CASCADE,
  FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
  FOREIGN KEY (avaliador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_avc_criterio (criterio_id),
  INDEX idx_avc_grupo (grupo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE avaliacoes_arquivadas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  criterio_original_id INT NOT NULL,
  projeto_id INT NOT NULL,
  dados_json JSON NOT NULL,
  arquivado_por INT DEFAULT NULL,
  arquivado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expira_em DATETIME DEFAULT NULL,
  restaurado TINYINT(1) DEFAULT 0,
  restaurado_em DATETIME DEFAULT NULL,
  restaurado_por INT DEFAULT NULL,
  FOREIGN KEY (arquivado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (restaurado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_arq_projeto (projeto_id),
  INDEX idx_arq_expira (expira_em),
  INDEX idx_arq_restaurado (restaurado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE boletins_snapshot (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL,
  aluno_id INT NOT NULL,
  dados_json JSON NOT NULL,
  media_geral DECIMAL(6,2) DEFAULT NULL,
  conceito_geral ENUM('I','R','B','MB') DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unico_snapshot (projeto_id, aluno_id),
  FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
  FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_snap_projeto (projeto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 8. ATAS E REGISTROS
-- =====================================================================

-- diretor_id e representante_id são NULLable e usam ON DELETE SET NULL (em vez de CASCADE)
-- para que a exclusão LGPD de um usuário não apague a ata inteira (e com ela, os registros
-- de outros alunos que participaram). A obrigatoriedade de preencher os dois no momento da
-- criação (8.1) é validada na aplicação, não no banco.
CREATE TABLE atas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(200) NOT NULL,
  descricao TEXT DEFAULT NULL,
  grupo_id INT NOT NULL,
  diretor_id INT DEFAULT NULL,
  representante_id INT DEFAULT NULL,
  data_ata DATE NOT NULL,
  prazo_preenchimento DATE DEFAULT NULL,
  horario_inicio TIME DEFAULT NULL,
  horario_fim TIME DEFAULT NULL,
  status ENUM('pendente','preenchida','revisada') DEFAULT 'pendente',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
  FOREIGN KEY (diretor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (representante_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_atas_grupo (grupo_id),
  INDEX idx_atas_status (status),
  INDEX idx_atas_data (data_ata)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ata_participantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ata_id INT NOT NULL,
  aluno_id INT NOT NULL,
  presente ENUM('sim','nao','justificado') DEFAULT 'sim',
  justificativa TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unico_ata_aluno (ata_id, aluno_id),
  FOREIGN KEY (ata_id) REFERENCES atas(id) ON DELETE CASCADE,
  FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE atividades_ata (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ata_id INT NOT NULL,
  nome VARCHAR(200) NOT NULL,
  descricao TEXT DEFAULT NULL,
  autor_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ata_id) REFERENCES atas(id) ON DELETE CASCADE,
  FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_ativ_ata (ata_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE participantes_atividade (
  id INT AUTO_INCREMENT PRIMARY KEY,
  atividade_id INT NOT NULL,
  usuario_id INT NOT NULL,
  tipo_participacao ENUM('responsavel','colaborador','observador') DEFAULT 'colaborador',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unico_part_atv (atividade_id, usuario_id),
  FOREIGN KEY (atividade_id) REFERENCES atividades_ata(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE materiais_atividade (
  id INT AUTO_INCREMENT PRIMARY KEY,
  atividade_id INT NOT NULL,
  material_nome VARCHAR(200) NOT NULL,
  quantidade DECIMAL(10,2) DEFAULT NULL,
  unidade VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (atividade_id) REFERENCES atividades_ata(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE relatorios_ata (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ata_id INT NOT NULL,
  usuario_id INT NOT NULL,
  tipo_usuario ENUM('representante','diretor') NOT NULL,
  titulo VARCHAR(200) DEFAULT NULL,
  conteudo TEXT NOT NULL,
  tipo_relatorio ENUM('ocorrencia','decisao','encaminhamento','observacao') DEFAULT 'ocorrencia',
  tema VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ata_id) REFERENCES atas(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_rel_ata (ata_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE participantes_relatorio (
  id INT AUTO_INCREMENT PRIMARY KEY,
  relatorio_id INT NOT NULL,
  usuario_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unico_part_rel (relatorio_id, usuario_id),
  FOREIGN KEY (relatorio_id) REFERENCES relatorios_ata(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 9. MATERIAIS
-- =====================================================================

CREATE TABLE materiais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  preco DECIMAL(10,2) NOT NULL DEFAULT 0,
  quantidade DECIMAL(10,2) NOT NULL DEFAULT 0,
  unidade VARCHAR(50) DEFAULT NULL,
  projeto_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
  INDEX idx_mat_projeto (projeto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE movimentacoes_materiais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  material_id INT NOT NULL,
  usuario_id INT NOT NULL,
  tipo_movimentacao ENUM('cadastro','compra','uso','ajuste') NOT NULL,
  quantidade DECIMAL(10,2) NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
  observacao TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (material_id) REFERENCES materiais(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_mov_material (material_id),
  INDEX idx_mov_tipo (tipo_movimentacao),
  INDEX idx_mov_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 10. ALERTAS E NOTIFICAÇÕES
-- =====================================================================

CREATE TABLE alertas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  autor_id INT NOT NULL,
  turma_id INT NOT NULL,
  projeto_id INT DEFAULT NULL,
  grupo_id INT DEFAULT NULL,
  titulo VARCHAR(200) NOT NULL,
  mensagem TEXT NOT NULL,
  urgente TINYINT(1) DEFAULT 0,
  expira_em DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
  FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
  INDEX idx_alertas_turma (turma_id),
  INDEX idx_alertas_urgente (urgente),
  INDEX idx_alertas_expira (expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notificacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  referencia_id INT DEFAULT NULL,
  titulo VARCHAR(200) NOT NULL,
  mensagem TEXT NOT NULL,
  link VARCHAR(255) DEFAULT NULL,
  lida TINYINT(1) DEFAULT 0,
  enviada_email TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  lida_em DATETIME DEFAULT NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_notif_usuario (usuario_id),
  INDEX idx_notif_lida (lida),
  INDEX idx_notif_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 11. LOGS E AUDITORIA
-- =====================================================================

CREATE TABLE log_sistema (
  id INT AUTO_INCREMENT PRIMARY KEY,
  acao VARCHAR(100) NOT NULL,
  tabela_afetada VARCHAR(100) DEFAULT NULL,
  registro_id INT DEFAULT NULL,
  detalhes TEXT DEFAULT NULL,
  usuario_id INT DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_log_usuario (usuario_id),
  INDEX idx_log_acao (acao),
  INDEX idx_log_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE auditoria_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT DEFAULT NULL,
  acao VARCHAR(100) NOT NULL,
  tabela_afetada VARCHAR(100) DEFAULT NULL,
  registro_id INT DEFAULT NULL,
  dados_anteriores JSON DEFAULT NULL,
  dados_novos JSON DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_audit_usuario (usuario_id),
  INDEX idx_audit_acao (acao),
  INDEX idx_audit_tabela (tabela_afetada),
  INDEX idx_audit_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 12. LGPD — SOLICITAÇÕES
-- =====================================================================

CREATE TABLE lgpd_solicitacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  tipo ENUM('exportacao','exclusao') NOT NULL,
  status ENUM('pendente','processando','concluida','negada') DEFAULT 'pendente',
  motivo_negacao VARCHAR(255) DEFAULT NULL,
  arquivo_gerado VARCHAR(255) DEFAULT NULL,
  processado_por INT DEFAULT NULL,
  processado_em DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (processado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_lgpd_usuario (usuario_id),
  INDEX idx_lgpd_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED — MASTER INICIAL
-- =====================================================================
-- Senha padrão: admin123
-- (hash bcrypt abaixo — troque no primeiro login)

INSERT INTO usuarios (nome, email, senha, tipo, email_confirmado, primeiro_login, ativo, lgpd_aceito, lgpd_data)
VALUES (
  'Master',
  'master@adm.com',
  '$2y$12$abcdefghijklmnopqrstuvABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
  'master',
  1,
  1,
  1,
  1,
  NOW()
);

INSERT INTO preferencias_usuario (usuario_id, receber_email, receber_email_prazos, receber_email_alertas, receber_email_avaliacoes)
VALUES (1, 1, 1, 1, 1);

