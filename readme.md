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
