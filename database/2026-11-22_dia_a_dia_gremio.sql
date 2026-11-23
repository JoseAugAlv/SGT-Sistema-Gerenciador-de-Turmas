SET NAMES utf8mb4;
SET SQL_SAFE_UPDATES = 0;
-- Dia a dia (aviso do dia, calendário, horário de aulas, reuniões com ata) e grêmio estudantil
-- (grêmio = turma do tipo 'gremio'; presidente = representante principal; diretorias criadas pelo presidente).

ALTER TABLE alertas ADD COLUMN do_dia DATE NULL AFTER contagem_ate;

ALTER TABLE turmas ADD COLUMN tipo ENUM('turma','gremio') NOT NULL DEFAULT 'turma' AFTER nome;

CREATE TABLE IF NOT EXISTS eventos_calendario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  tipo ENUM('entrega','prova','evento','reuniao','feriado','outro') NOT NULL DEFAULT 'evento',
  titulo VARCHAR(150) NOT NULL,
  descricao TEXT NULL,
  data DATE NOT NULL,
  hora_inicio TIME NULL,
  hora_fim TIME NULL,
  local VARCHAR(120) NULL,
  criado_por INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_evento_turma_data (turma_id, data),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS horarios_aula (
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

CREATE TABLE IF NOT EXISTS gremio_diretorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  nome VARCHAR(100) NOT NULL,
  descricao VARCHAR(500) NULL,
  criado_por INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gremio_diretoria_membros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  diretoria_id INT NOT NULL,
  usuario_id INT NOT NULL,
  cargo ENUM('diretor','coordenador','membro') NOT NULL DEFAULT 'membro',
  UNIQUE KEY uk_diretoria_membro (diretoria_id, usuario_id),
  FOREIGN KEY (diretoria_id) REFERENCES gremio_diretorias(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reunioes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  diretoria_id INT NULL,
  titulo VARCHAR(150) NOT NULL,
  data DATETIME NOT NULL,
  local VARCHAR(120) NULL,
  pauta TEXT NULL,
  ata TEXT NULL,
  status ENUM('agendada','realizada','cancelada') NOT NULL DEFAULT 'agendada',
  criado_por INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_reuniao_turma (turma_id, data),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (diretoria_id) REFERENCES gremio_diretorias(id) ON DELETE SET NULL,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reuniao_presencas (
  reuniao_id INT NOT NULL,
  usuario_id INT NOT NULL,
  presente TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (reuniao_id, usuario_id),
  FOREIGN KEY (reuniao_id) REFERENCES reunioes(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Planos do grêmio: mesmas durações das turmas, cerca de 30% mais baratos (os antigos por número de pessoas saem de venda)
UPDATE planos SET ativo = 0 WHERE tipo = 'gremio';
INSERT IGNORE INTO planos (codigo, tipo, nome, meses, projetos, pessoas_max, preco, piso, max_parcelas, tabela_parcelas, ordem, ativo) VALUES
  ('g_trimestral', 'gremio', 'Grêmio Trimestral',  3,    2, 50,  69.90,  48.90,  1, NULL, 21, 1),
  ('g_semestral',  'gremio', 'Grêmio Semestral',   6,    4, 50, 117.90,  82.50,  2, 'A',  22, 1),
  ('g_anual',      'gremio', 'Grêmio Anual',      12,    8, 50, 194.90, 136.40,  4, 'A',  23, 1),
  ('g_completo',   'gremio', 'Grêmio Completo',   18,   12, 50, 249.90, 174.90,  6, 'A',  24, 1),
  ('g_estendido',  'gremio', 'Grêmio Estendido',  36,   24, 50, 369.90, 258.90, 12, 'B',  25, 1),
  ('g_vitalicio',  'gremio', 'Grêmio Vitalício', NULL,  36, 50, 699.90, 489.90, 12, 'B',  26, 1);

INSERT IGNORE INTO config_comercial (chave, valor, descricao) VALUES
  ('desconto_cruzado_pct', '10', 'Quem já tem plano de turma ganha este desconto (%) no plano do grêmio, e vice-versa');
