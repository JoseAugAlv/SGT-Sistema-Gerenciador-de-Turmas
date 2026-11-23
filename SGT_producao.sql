-- =====================================================================
-- Prumo — banco ZERADO para o servidor (gerado a partir do SGT.sql)
-- Todas as tabelas, vazias, + o essencial: usuário master, cursos, períodos, planos, configurações.
-- No phpMyAdmin: selecione o banco da hospedagem à esquerda > Importar > este arquivo.
-- Não cria nem apaga o banco (a hospedagem não deixa); apague as tabelas antigas antes.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET SQL_SAFE_UPDATES = 0;
SET FOREIGN_KEY_CHECKS = 0;


-- =====================================================================
-- 1. USUÁRIOS E AUTENTICAÇÃO
-- =====================================================================
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  google_id VARCHAR(64) DEFAULT NULL,           -- login com Google (identificador da conta)
  tipo ENUM('aluno','master') NOT NULL DEFAULT 'aluno',
  telefone VARCHAR(20) DEFAULT NULL,
  data_nascimento DATE DEFAULT NULL,
  email_confirmado TINYINT(1) NOT NULL DEFAULT 0,
  email_token VARCHAR(255) DEFAULT NULL,
  email_token_expira DATETIME DEFAULT NULL,
  primeiro_login TINYINT(1) NOT NULL DEFAULT 1,
  cadastro_pendente TINYINT(1) NOT NULL DEFAULT 0, -- entrou pelo Google/convite e falta nome e telefone
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  lgpd_aceito TINYINT(1) NOT NULL DEFAULT 0,
  lgpd_data DATETIME DEFAULT NULL,
  ultimo_acesso DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_google_id (google_id),
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
  nivel_email ENUM('todas','importantes','mencoes','nenhuma') NOT NULL DEFAULT 'importantes',
  resumo_semanal TINYINT(1) NOT NULL DEFAULT 1,  -- recebe o resumo da semana por email (segunda-feira)
  receber_email TINYINT(1) DEFAULT 1,
  receber_email_prazos TINYINT(1) DEFAULT 1,
  receber_email_alertas TINYINT(1) DEFAULT 1,
  receber_email_avaliacoes TINYINT(1) DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 2. TURMAS E PAPÉIS
-- =====================================================================
CREATE TABLE turmas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  tipo ENUM('turma','gremio') NOT NULL DEFAULT 'turma',  -- grêmio: presidente = representante principal
  codigo_acesso VARCHAR(30) NOT NULL UNIQUE,
  codigo_interno VARCHAR(100) DEFAULT NULL,
  cor_primaria VARCHAR(20) DEFAULT '#3498db',
  cor_secundaria VARCHAR(20) DEFAULT '#2ecc71',
  cor_personalizada TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = faixa da turma usa cor_primaria em vez da cor do tema
  logo VARCHAR(255) DEFAULT NULL,                  -- caminho em public/ (uploads/turmas/...)
  bloqueada TINYINT(1) NOT NULL DEFAULT 0,
  bloqueada_motivo VARCHAR(255) DEFAULT NULL,
  bloqueada_em DATETIME DEFAULT NULL,
  bloqueada_por INT DEFAULT NULL,
  isenta_plano TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = não precisa de plano (turmas antigas, cortesia)
  teste_dia_ate DATETIME NULL,                 -- teste grátis do Dia a dia vale até (NULL = nunca testou)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (bloqueada_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_turmas_codigo (codigo_acesso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE turma_usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  usuario_id INT NOT NULL,
  papel ENUM('aluno','representante') NOT NULL DEFAULT 'aluno',
  vice TINYINT(1) NOT NULL DEFAULT 0,            -- 1 = vice (avalia e organiza, sem acesso a plano/pagamento)
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

-- Convites por email: o aluno já entra na turma e completa o cadastro pelo link
CREATE TABLE convites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  usuario_id INT NOT NULL,
  email VARCHAR(150) NOT NULL,
  token VARCHAR(64) NOT NULL,
  status ENUM('pendente','aceito','cancelado') NOT NULL DEFAULT 'pendente',
  convidado_por INT DEFAULT NULL,
  expira_em DATETIME NOT NULL,
  aceito_em DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_convite_token (token),
  KEY idx_convite_turma (turma_id, status),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (convidado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 3. CURSOS E PERÍODOS
-- =====================================================================
CREATE TABLE cursos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  sigla VARCHAR(20) NOT NULL UNIQUE,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE periodos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL,
  sigla VARCHAR(20) NOT NULL UNIQUE,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 4. PROJETOS E ETAPAS
-- =====================================================================
CREATE TABLE projetos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  diretoria_id INT NULL,                     -- grêmio: diretoria responsável pelo projeto (orçamento e prestação de contas)
  nome VARCHAR(150) NOT NULL,
  descricao TEXT DEFAULT NULL,
  prazo DATE DEFAULT NULL,
  modo_avaliacao ENUM('etapa','cronograma') DEFAULT 'cronograma',
  alunos_pedem_grupo TINYINT(1) NOT NULL DEFAULT 0,      -- 1 = alunos pedem para entrar no grupo
  alunos_pedem_diretor TINYINT(1) NOT NULL DEFAULT 0,    -- 1 = membros pedem para ser diretor
  diretores_por_grupo INT NOT NULL DEFAULT 1,
  avaliacao_reps ENUM('compartilhada','media') NOT NULL DEFAULT 'compartilhada', -- representante e vice: mesma nota ou média
  encerrado TINYINT(1) DEFAULT 0,
  encerrado_em DATETIME DEFAULT NULL,
  encerrado_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (encerrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_projetos_turma (turma_id),
  INDEX idx_projetos_encerrado (encerrado),
  CONSTRAINT fk_projeto_diretoria FOREIGN KEY (diretoria_id) REFERENCES gremio_diretorias(id) ON DELETE SET NULL
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
-- 5. GRUPOS
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

-- Pedidos dos alunos: entrar num grupo ou ser diretor (quando o projeto permite)
CREATE TABLE solicitacoes_grupo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  usuario_id INT NOT NULL,
  tipo ENUM('entrar','diretor') NOT NULL,
  status ENUM('pendente','aprovada','recusada','cancelada') NOT NULL DEFAULT 'pendente',
  respondida_por INT DEFAULT NULL,
  respondida_em DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_solic_grupo (grupo_id, status),
  KEY idx_solic_usuario (usuario_id, status),
  FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (respondida_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 6. CRITÉRIOS
-- =====================================================================
CREATE TABLE criterios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL,
  etapa_id INT DEFAULT NULL,
  nome VARCHAR(200) NOT NULL,
  descricao TEXT DEFAULT NULL,
  peso DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  tipo_avaliacao ENUM('diretor','representante','pares','autoavaliacao','coletiva','misto') NOT NULL DEFAULT 'diretor',
  aplicavel_a ENUM('todos','apenas_diretores','apenas_representantes') DEFAULT 'todos',
  prazo_avaliacao DATETIME DEFAULT NULL,
  obrigatorio TINYINT(1) NOT NULL DEFAULT 0,  -- aluno precisa preencher antes do prazo (só pares/autoavaliação)
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

-- Desconto na nota final do aluno que não preencheu um critério obrigatório.
-- Pontos na escala 0–100, subtraídos da média ponderada do projeto.
CREATE TABLE descontos_nota (
  id INT AUTO_INCREMENT PRIMARY KEY,
  criterio_id INT NOT NULL,
  aluno_id INT NOT NULL,
  pontos DECIMAL(5,2) NOT NULL,
  motivo VARCHAR(255) NOT NULL,
  aplicado_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_desconto (criterio_id, aluno_id),
  FOREIGN KEY (criterio_id) REFERENCES criterios(id) ON DELETE CASCADE,
  FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (aplicado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_desc_aluno (aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 7. CONFIGURAÇÃO DE CONCEITO
-- =====================================================================
CREATE TABLE configuracoes_conceito (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL UNIQUE,
  esquema_nota ENUM('mencao','0a10','0a100') NOT NULL DEFAULT 'mencao',
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
-- 8. AVALIAÇÕES
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
-- 9. ATAS E REGISTROS
-- =====================================================================
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
  preenchimento ENUM('diretor','grupo') NOT NULL DEFAULT 'diretor',  -- quem preenche: só o diretor ou todo o grupo
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
  tipo_usuario ENUM('representante','diretor','aluno') NOT NULL,
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
-- 10. MATERIAIS
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
-- 11. ALERTAS E NOTIFICAÇÕES
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
  contagem_ate DATETIME DEFAULT NULL,     -- contagem regressiva no painel
  do_dia DATE DEFAULT NULL,               -- aviso do dia (some à meia-noite)
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
-- DIA A DIA E GRÊMIO: calendário, horário das aulas, diretorias e reuniões
-- =====================================================================
-- Agenda: eventos da turma (criados pelo representante/vice, todos veem) e pessoais (só quem criou vê)
CREATE TABLE eventos_calendario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NULL,                     -- pessoal pode não ter turma
  visibilidade ENUM('turma','pessoal') NOT NULL DEFAULT 'turma',
  tipo ENUM('entrega','prova','tarefa','estudo','evento','reuniao','feriado','outro') NOT NULL DEFAULT 'evento',
  prioridade ENUM('baixa','media','alta','urgente') NOT NULL DEFAULT 'media',
  titulo VARCHAR(150) NOT NULL,
  descricao TEXT NULL,
  data DATE NOT NULL,
  hora_inicio TIME NULL,
  hora_fim TIME NULL,
  local VARCHAR(120) NULL,
  lembrete TINYINT(1) NOT NULL DEFAULT 1, -- notificação na véspera
  criado_por INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_evento_turma_data (turma_id, data),
  KEY idx_evento_criador (criado_por, data),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Quem já concluiu cada evento (cada pessoa marca o seu)
CREATE TABLE evento_conclusoes (
  evento_id INT NOT NULL,
  usuario_id INT NOT NULL,
  concluido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (evento_id, usuario_id),
  FOREIGN KEY (evento_id) REFERENCES eventos_calendario(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE horarios_aula (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  dia_semana TINYINT NOT NULL,           -- 1 = segunda ... 7 = domingo
  inicio TIME NOT NULL,
  fim TIME NOT NULL,
  disciplina VARCHAR(100) NOT NULL,
  professor VARCHAR(100) NULL,
  sala VARCHAR(50) NULL,
  KEY idx_horario_turma (turma_id, dia_semana, inicio),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Diretorias / coordenações do grêmio (criadas pelo presidente)
CREATE TABLE gremio_diretorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  nome VARCHAR(100) NOT NULL,
  descricao VARCHAR(500) NULL,
  criado_por INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE gremio_diretoria_membros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  diretoria_id INT NOT NULL,
  usuario_id INT NOT NULL,
  cargo ENUM('diretor','coordenador','membro') NOT NULL DEFAULT 'membro',
  UNIQUE KEY uk_diretoria_membro (diretoria_id, usuario_id),
  FOREIGN KEY (diretoria_id) REFERENCES gremio_diretorias(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reuniões da turma ou do grêmio (geral ou de uma diretoria): pauta, ata e presença
CREATE TABLE reunioes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  diretoria_id INT NULL,
  titulo VARCHAR(150) NOT NULL,
  data DATETIME NOT NULL,
  local VARCHAR(120) NULL,
  pauta TEXT NULL,
  ata TEXT NULL,
  status ENUM('agendada','realizada','cancelada') NOT NULL DEFAULT 'agendada',
  qr_token VARCHAR(40) NULL,                 -- QR de presença aberto (muda a cada 30 s, assinado com APP_KEY)
  qr_ate DATETIME NULL,                      -- até quando o QR aceita presenças
  criado_por INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_reuniao_turma (turma_id, data),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (diretoria_id) REFERENCES gremio_diretorias(id) ON DELETE SET NULL,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reuniao_presencas (
  reuniao_id INT NOT NULL,
  usuario_id INT NOT NULL,
  presente TINYINT(1) NOT NULL DEFAULT 1,
  via ENUM('manual','qr') NOT NULL DEFAULT 'manual',  -- como a presença foi marcada
  marcado_em DATETIME NULL,
  PRIMARY KEY (reuniao_id, usuario_id),
  FOREIGN KEY (reuniao_id) REFERENCES reunioes(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- 12. LOGS E AUDITORIA
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
-- 13. LGPD
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

-- =====================================================================
-- 14. SOLICITAÇÕES DE MATERIAIS
-- =====================================================================
CREATE TABLE solicitacoes_materiais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL,
  turma_id INT NOT NULL,
  solicitante_id INT NOT NULL,
  nome_material VARCHAR(150) NOT NULL,
  quantidade DECIMAL(10,2) NOT NULL,
  unidade VARCHAR(50) DEFAULT NULL,
  justificativa TEXT DEFAULT NULL,
  preco_estimado DECIMAL(10,2) DEFAULT NULL,
  status ENUM('pendente','aprovada','comprada','recusada','cancelada') DEFAULT 'pendente',
  material_id INT DEFAULT NULL,
  aprovado_por INT DEFAULT NULL,
  aprovado_em DATETIME DEFAULT NULL,
  comprado_por INT DEFAULT NULL,
  comprado_em DATETIME DEFAULT NULL,
  motivo_recusa TEXT DEFAULT NULL,
  recusado_por INT DEFAULT NULL,
  recusado_em DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (solicitante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES materiais(id) ON DELETE SET NULL,
  FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (comprado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (recusado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_sm_projeto (projeto_id),
  INDEX idx_sm_status (status),
  INDEX idx_sm_solicitante (solicitante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- PLANOS E PAGAMENTOS
-- =====================================================================
-- Planos, cupons, assinaturas e pagamentos (modo de teste, sem gateway).
-- Preços e regras: precos.md.

CREATE TABLE planos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  tipo ENUM('turma','gremio') NOT NULL,
  nome VARCHAR(60) NOT NULL,
  meses INT DEFAULT NULL,                 -- NULL = sem vencimento (Vitalício)
  projetos INT NOT NULL,
  pessoas_max INT NOT NULL DEFAULT 50,
  preco DECIMAL(10,2) NOT NULL,           -- à vista, preço de lista
  piso DECIMAL(10,2) NOT NULL,            -- nenhum desconto deixa o à vista abaixo disto
  max_parcelas INT NOT NULL DEFAULT 1,
  tabela_parcelas CHAR(1) DEFAULT NULL,   -- 'A' (acréscimo desde 2x) ou 'B' (6x sem juros); NULL = só à vista
  inclui_dia_a_dia TINYINT(1) NOT NULL DEFAULT 1, -- agenda, horário, aviso do dia e contagem (do Anual para cima)
  preco_dia_a_dia DECIMAL(10,2) DEFAULT NULL,     -- adicional para os planos que não incluem
  ordem INT NOT NULL DEFAULT 0,
  ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE config_comercial (
  chave VARCHAR(60) PRIMARY KEY,
  valor VARCHAR(255) DEFAULT NULL,
  descricao VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pacotes para escolas: um cupom gratuito (uso único) por turma
CREATE TABLE pacotes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  plano_id INT NOT NULL,
  quantidade INT NOT NULL,
  valido_ate DATE DEFAULT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_por INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (plano_id) REFERENCES planos(id),
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  origem ENUM('manual','pacote','indicacao','renovacao') NOT NULL DEFAULT 'manual',
  pacote_id INT NULL,                     -- cupom gerado por um pacote de escola
  turma_dona_id INT NULL,                 -- código de indicação desta turma
  tipo_plano ENUM('turma','gremio','todos') NOT NULL DEFAULT 'todos',
  percentual DECIMAL(5,2) DEFAULT NULL,   -- NULL = usa o percentual padrão da configuração
  valido_ate DATE DEFAULT NULL,
  usos_max INT DEFAULT NULL,
  usos INT NOT NULL DEFAULT 0,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_cupom_pacote FOREIGN KEY (pacote_id) REFERENCES pacotes(id) ON DELETE CASCADE,
  CONSTRAINT fk_cupom_turma_dona FOREIGN KEY (turma_dona_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Desconto do cupom em cada plano (% ou R$). Sem linhas aqui, vale a regra geral (tipo de plano + percentual)
CREATE TABLE cupom_planos (
  cupom_id INT NOT NULL,
  plano_id INT NOT NULL,
  tipo_desconto ENUM('pct','valor') NOT NULL DEFAULT 'pct',
  valor DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (cupom_id, plano_id),
  FOREIGN KEY (cupom_id) REFERENCES cupons(id) ON DELETE CASCADE,
  FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE assinaturas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  plano_id INT NOT NULL,
  status ENUM('aguardando','ativa','cancelada','trocada','renovada') NOT NULL DEFAULT 'aguardando',
  inicio DATETIME DEFAULT NULL,           -- definido quando a 1ª parcela é paga
  fim DATETIME DEFAULT NULL,              -- NULL com status ativa = sem vencimento
  projetos_limite INT NOT NULL,
  valor_lista DECIMAL(10,2) NOT NULL,
  desconto_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  valor_total DECIMAL(10,2) NOT NULL,
  valor_base DECIMAL(10,2) DEFAULT NULL,  -- valor à vista pago pelo plano (base do crédito na troca)
  parcelas INT NOT NULL DEFAULT 1,
  dia_a_dia TINYINT(1) NOT NULL DEFAULT 0, -- comprou o adicional Dia a dia (planos que não incluem)
  cupom_id INT DEFAULT NULL,
  troca_de INT DEFAULT NULL,             -- troca de plano: assinatura anterior (vira 'trocada' ao pagar a 1ª parcela)
  renovacao_de INT NULL,                 -- renovação: assinatura anterior (vira 'renovada'; o novo período começa no fim dela)
  cupom_renovacao_id INT NULL,           -- cupom automático de renovação gerado perto do fim deste plano
  comprado_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (plano_id) REFERENCES planos(id),
  FOREIGN KEY (cupom_id) REFERENCES cupons(id) ON DELETE SET NULL,
  FOREIGN KEY (comprado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (troca_de) REFERENCES assinaturas(id) ON DELETE SET NULL,
  INDEX idx_ass_turma (turma_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dados de cobrança de quem compra planos (CPF e endereço). Um registro por usuário, reaproveitado nas próximas compras.
CREATE TABLE dados_cobranca (
  usuario_id INT PRIMARY KEY,
  nome_completo VARCHAR(120) NOT NULL,
  cpf CHAR(11) NOT NULL,
  cep CHAR(8) NOT NULL,
  logradouro VARCHAR(150) NOT NULL,
  numero VARCHAR(20) NOT NULL,
  complemento VARCHAR(80) DEFAULT NULL,
  bairro VARCHAR(80) NOT NULL,
  cidade VARCHAR(80) NOT NULL,
  uf CHAR(2) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pagamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assinatura_id INT NOT NULL,
  numero INT NOT NULL,                    -- nº da parcela (1..parcelas)
  tipo ENUM('parcela','adicional') NOT NULL DEFAULT 'parcela', -- adicional = compra do Dia a dia depois
  valor DECIMAL(10,2) NOT NULL,
  vencimento DATE NOT NULL,
  status ENUM('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
  metodo VARCHAR(20) NOT NULL DEFAULT 'pix_teste',
  txid VARCHAR(40) DEFAULT NULL,
  mp_id VARCHAR(40) NULL,                   -- Mercado Pago: id do pagamento (modo 'mercadopago')
  pix_copia TEXT NULL,                      -- Pix copia e cola real
  pix_qr MEDIUMTEXT NULL,                   -- QR do Pix (imagem PNG em base64)
  pix_expira DATETIME NULL,                 -- validade do Pix gerado
  pago_em DATETIME DEFAULT NULL,
  pago_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_parcela (assinatura_id, numero),
  FOREIGN KEY (assinatura_id) REFERENCES assinaturas(id) ON DELETE CASCADE,
  FOREIGN KEY (pago_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crédito de quem indicou: % de desconto nas próximas parcelas ou na próxima compra
CREATE TABLE creditos_indicacao (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  origem_turma_id INT DEFAULT NULL,
  origem_assinatura_id INT DEFAULT NULL,
  pct DECIMAL(5,2) NOT NULL,
  status ENUM('disponivel','usado') NOT NULL DEFAULT 'disponivel',
  usado_assinatura_id INT DEFAULT NULL,
  usado_em DATETIME DEFAULT NULL,
  descricao VARCHAR(200) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_credito_turma (turma_id, status),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (origem_turma_id) REFERENCES turmas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enquetes e votações da turma/grêmio
CREATE TABLE enquetes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  criado_por INT NOT NULL,
  pergunta VARCHAR(200) NOT NULL,
  descricao TEXT NULL,
  multipla TINYINT(1) NOT NULL DEFAULT 0,         -- 1 = pode marcar mais de uma opção
  anonima TINYINT(1) NOT NULL DEFAULT 1,          -- 1 = ninguém vê quem votou em quê
  resultado ENUM('sempre','apos_votar','apos_encerrar') NOT NULL DEFAULT 'apos_votar',
  encerra_em DATETIME NULL,                       -- NULL = fica aberta até encerrar à mão
  encerrada_em DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_enquete_turma (turma_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE enquete_opcoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  enquete_id INT NOT NULL,
  texto VARCHAR(150) NOT NULL,
  ordem INT NOT NULL DEFAULT 0,
  FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE enquete_votos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  enquete_id INT NOT NULL,
  opcao_id INT NOT NULL,
  usuario_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_voto (enquete_id, opcao_id, usuario_id),
  FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE,
  FOREIGN KEY (opcao_id) REFERENCES enquete_opcoes(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Caixinha anônima de dúvidas e sugestões
CREATE TABLE caixinha_mensagens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  autor_id INT NULL,
  tipo ENUM('duvida','sugestao','reclamacao','elogio') NOT NULL DEFAULT 'duvida',
  texto TEXT NOT NULL,
  status ENUM('nova','lida','respondida','arquivada') NOT NULL DEFAULT 'nova',
  resposta TEXT NULL,
  respondida_por INT NULL,
  respondida_em DATETIME NULL,
  publica TINYINT(1) NOT NULL DEFAULT 0,          -- 1 = pergunta e resposta aparecem para a turma toda
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (respondida_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_caixinha_turma (turma_id, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mural de fotos e conquistas
CREATE TABLE mural_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  autor_id INT NULL,
  tipo ENUM('foto','conquista') NOT NULL DEFAULT 'foto',
  icone ENUM('trofeu','medalha','estrela','foguete','coracao') NULL,   -- conquista sem foto
  titulo VARCHAR(120) NOT NULL,
  texto VARCHAR(600) NULL,
  arquivo VARCHAR(255) NULL,                     -- caminho em public/ (uploads/mural/...)
  aprovado TINYINT(1) NOT NULL DEFAULT 0,
  fixado TINYINT(1) NOT NULL DEFAULT 0,          -- aparece primeiro
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_mural_turma (turma_id, aprovado, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mural_curtidas (
  post_id INT NOT NULL,
  usuario_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (post_id, usuario_id),
  FOREIGN KEY (post_id) REFERENCES mural_posts(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Banco de materiais de estudo por disciplina
CREATE TABLE estudo_materiais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  autor_id INT NULL,
  disciplina VARCHAR(80) NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  tipo ENUM('arquivo','link','resumo') NOT NULL DEFAULT 'arquivo',
  arquivo VARCHAR(255) NULL,                -- caminho em public/ (uploads/estudos/...)
  nome_original VARCHAR(200) NULL,
  url VARCHAR(500) NULL,
  texto TEXT NULL,                          -- resumo escrito
  evento_id INT NULL,                       -- prova/entrega da agenda a que o material ajuda
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (evento_id) REFERENCES eventos_calendario(id) ON DELETE SET NULL,
  INDEX idx_estudo_turma (turma_id, disciplina)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kanban de tarefas do grupo
CREATE TABLE grupo_tarefas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  descricao TEXT NULL,
  responsavel_id INT NULL,
  prazo DATE NULL,
  coluna ENUM('a_fazer','fazendo','revisao','feito') NOT NULL DEFAULT 'a_fazer',
  ordem INT NOT NULL DEFAULT 0,
  ata_id INT NULL,                          -- ata do grupo em que a tarefa foi combinada
  criado_por INT NULL,
  concluida_em DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
  FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (ata_id) REFERENCES atas(id) ON DELETE SET NULL,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_tarefa_grupo (grupo_id, coluna, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Controle de caixa da turma/grêmio
CREATE TABLE caixa_lancamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  tipo ENUM('entrada','saida') NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  descricao VARCHAR(200) NOT NULL,
  categoria VARCHAR(60) NOT NULL DEFAULT 'Outros',
  data DATE NOT NULL,
  pessoa_id INT NULL,
  diretoria_id INT NULL,
  projeto_id INT NULL,                      -- gasto/arrecadação de um projeto (orçamento)
  comprovante VARCHAR(255) NULL,            -- caminho em public/ (uploads/caixa/...)
  comprovante_nome VARCHAR(200) NULL,
  registrado_por INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (pessoa_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (diretoria_id) REFERENCES gremio_diretorias(id) ON DELETE SET NULL,
  FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_caixa_turma (turma_id, data),
  CONSTRAINT fk_caixa_projeto FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orçamento dos projetos (itens previstos)
CREATE TABLE projeto_orcamento_itens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  projeto_id INT NOT NULL,
  descricao VARCHAR(150) NOT NULL,
  valor_previsto DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Página pública do grêmio
CREATE TABLE gremio_paginas (
  turma_id INT PRIMARY KEY,
  slug VARCHAR(60) NOT NULL,
  ativa TINYINT(1) NOT NULL DEFAULT 0,
  apresentacao TEXT NULL,
  instagram VARCHAR(60) NULL,
  mostrar_diretorias TINYINT(1) NOT NULL DEFAULT 1,
  mostrar_eventos TINYINT(1) NOT NULL DEFAULT 1,
  mostrar_conquistas TINYINT(1) NOT NULL DEFAULT 1,
  mostrar_contas TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_gremio_slug (slug),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Resumo semanal por email (controle de envio)
CREATE TABLE resumo_envios (
  usuario_id INT NOT NULL,
  semana CHAR(8) NOT NULL,                  -- ex.: 2026-W48
  enviado_em DATETIME NOT NULL,
  ok TINYINT(1) NOT NULL DEFAULT 1,         -- 0 = não tinha nada para resumir ou o envio falhou
  PRIMARY KEY (usuario_id, semana),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rateio da mensalidade entre os alunos
CREATE TABLE rateios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pagamento_id INT NOT NULL,
  turma_id INT NOT NULL,
  criado_por INT NULL,
  status ENUM('aberto','quitado','cancelado') NOT NULL DEFAULT 'aberto',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  quitado_em DATETIME NULL,
  FOREIGN KEY (pagamento_id) REFERENCES pagamentos(id) ON DELETE CASCADE,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_rateio_pagamento (pagamento_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rateio_partes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rateio_id INT NOT NULL,
  usuario_id INT NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  status ENUM('pendente','pago','coberto') NOT NULL DEFAULT 'pendente',   -- coberto = o representante pagou por ele
  pago_em DATETIME NULL,
  UNIQUE KEY uq_parte (rateio_id, usuario_id),
  FOREIGN KEY (rateio_id) REFERENCES rateios(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pedidos de proposta das escolas
CREATE TABLE escola_contatos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  escola VARCHAR(150) NOT NULL,
  cidade VARCHAR(100) NULL,
  email VARCHAR(150) NOT NULL,
  telefone VARCHAR(30) NULL,
  turmas INT NOT NULL DEFAULT 1,
  plano_id INT NULL,
  mensagem TEXT NULL,
  status ENUM('novo','em_contato','fechado','descartado') NOT NULL DEFAULT 'novo',
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE SET NULL,
  INDEX idx_escola_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- App instalável: inscrições de notificação no aparelho (Web Push)
CREATE TABLE push_inscricoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  endpoint VARCHAR(700) NOT NULL,
  endpoint_hash CHAR(64) NOT NULL,
  p256dh VARCHAR(150) NOT NULL,
  auth VARCHAR(60) NOT NULL,
  aparelho VARCHAR(150) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ultimo_envio DATETIME NULL,
  UNIQUE KEY uq_push_endpoint (endpoint_hash),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tutorial guiado (tours vistos)
CREATE TABLE tutorial_vistos (
  usuario_id INT NOT NULL,
  tour VARCHAR(40) NOT NULL,
  status ENUM('feito','dispensado') NOT NULL DEFAULT 'feito',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, tour),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ---------------------------------------------------------------------
-- USUÁRIO MASTER — senha inicial Teste@1234: TROQUE logo no primeiro acesso
-- ---------------------------------------------------------------------
SET @hash := '$2y$12$fgT/IQ/9F6/IgBBRj7HFWeZAxkchkDobGHs8UdZ8LT6K0pBm3Z2f.';
INSERT INTO usuarios (id, nome, email, senha, tipo, email_confirmado, primeiro_login, ativo, lgpd_aceito, lgpd_data, created_at) VALUES
(1, 'Jose', 'jose@adm.com', @hash, 'master', 1, 0, 1, 1, NOW(), NOW());
INSERT IGNORE INTO preferencias_usuario (usuario_id) SELECT id FROM usuarios;

INSERT INTO cursos (nome, sigla) VALUES
('Informática', 'INFO'),
('Administração', 'ADM'),
('Enfermagem', 'ENF'),
('Edificações', 'EDF');

INSERT INTO periodos (nome, sigla) VALUES
('Manhã', 'MANHA'),
('Tarde', 'TARDE'),
('Noite', 'NOITE'),
('Integral', 'INT');

INSERT INTO planos (codigo, tipo, nome, meses, projetos, pessoas_max, preco, piso, max_parcelas, tabela_parcelas, ordem, ativo) VALUES
  ('trimestral',   'turma',  'Trimestral',         3,    2, 50,  99.90,  69.90,  1, NULL,  1, 1),
  ('semestral',    'turma',  'Semestral',          6,    4, 50, 167.90, 117.50,  2, 'A',   2, 1),
  ('anual',        'turma',  'Anual',             12,    8, 50, 279.90, 195.90,  4, 'A',   3, 1),
  ('completo',     'turma',  'Completo',          18,   12, 50, 359.90, 251.90,  6, 'A',   4, 1),
  ('estendido',    'turma',  'Estendido',         36,   24, 50, 529.90, 370.90, 12, 'B',   5, 1),
  ('vitalicio',    'turma',  'Vitalício',       NULL,   36, 50, 999.90, 699.90, 12, 'B',   6, 1),
  ('gremio20',     'gremio', 'Grêmio 20',         12,   10, 20, 234.90, 199.60,  4, 'A',  11, 0),
  ('gremio25',     'gremio', 'Grêmio 25',         12,   10, 25, 259.90, 220.90,  4, 'A',  12, 0),
  ('gremio30',     'gremio', 'Grêmio 30',         12,   10, 30, 284.90, 242.10,  4, 'A',  13, 0),
  ('gremio40',     'gremio', 'Grêmio 40',         12,   10, 40, 334.90, 284.60,  4, 'A',  14, 0),
  ('g_trimestral', 'gremio', 'Grêmio Trimestral',  3,    2, 50,  69.90,  48.90,  1, NULL, 21, 1),
  ('g_semestral',  'gremio', 'Grêmio Semestral',   6,    4, 50, 117.90,  82.50,  2, 'A',  22, 1),
  ('g_anual',      'gremio', 'Grêmio Anual',      12,    8, 50, 194.90, 136.40,  4, 'A',  23, 1),
  ('g_completo',   'gremio', 'Grêmio Completo',   18,   12, 50, 249.90, 174.90,  6, 'A',  24, 1),
  ('g_estendido',  'gremio', 'Grêmio Estendido',  36,   24, 50, 369.90, 258.90, 12, 'B',  25, 1),
  ('g_vitalicio',  'gremio', 'Grêmio Vitalício', NULL,  36, 50, 699.90, 489.90, 12, 'B',  26, 1);

UPDATE planos SET inclui_dia_a_dia = 0, preco_dia_a_dia = 39.90 WHERE codigo = 'trimestral';

UPDATE planos SET inclui_dia_a_dia = 0, preco_dia_a_dia = 59.90 WHERE codigo = 'semestral';

UPDATE planos SET inclui_dia_a_dia = 0, preco_dia_a_dia = 27.90 WHERE codigo = 'g_trimestral';

UPDATE planos SET inclui_dia_a_dia = 0, preco_dia_a_dia = 41.90 WHERE codigo = 'g_semestral';

INSERT INTO config_comercial (chave, valor, descricao) VALUES
  ('cupom_turma_pct',  '30', 'Desconto padrão de cupom em planos de turma (%)'),
  ('cupom_gremio_pct', '15', 'Desconto padrão de cupom em planos de grêmio (%)'),
  ('indicacao_pct',    '10', 'Desconto da turma indicada na 1ª compra (%)'),
  ('modo_pagamento',   'teste', 'teste = pagamento simulado; mercadopago = cobrança real (futuro)'),
  ('exigir_plano', '1', '1 = turma sem plano ativo é bloqueada (exceto isentas); 0 = cobrança desligada'),
  ('tolerancia_dias', '5', 'Dias de tolerância após vencimento ou parcela atrasada, antes do bloqueio'),
  ('aviso_vencimento_dias', '7', 'Quantos dias antes do fim do plano o representante começa a ser avisado'),
  ('indicacao_recompensa_pct', '10', 'Indicação: desconto (%) que a turma que indicou ganha nas próximas parcelas ou na próxima compra'),
  ('desconto_cruzado_pct', '10', 'Quem já tem plano de turma ganha este desconto (%) no plano do grêmio, e vice-versa'),
  ('teste_dia_dias', '14', 'Dias do teste grátis do Dia a dia (0 = sem teste)'),
  ('renovacao_cupom_pct', '10', 'Cupom automático de renovação: desconto (%) enviado quando o plano está perto de vencer (0 = sem cupom)'),
  ('escola_desc_5',  '10', 'Pacote para escolas: desconto (%) a partir de 5 turmas'),
  ('escola_desc_10', '15', 'Pacote para escolas: desconto (%) a partir de 10 turmas'),
  ('escola_desc_20', '20', 'Pacote para escolas: desconto (%) a partir de 20 turmas');

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;
