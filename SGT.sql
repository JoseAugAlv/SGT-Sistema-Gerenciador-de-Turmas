
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