SET NAMES utf8mb4;
-- Planos, cupons, assinaturas e pagamentos (modo de teste, sem gateway).
-- Preços e regras: precos.md.

CREATE TABLE IF NOT EXISTS planos (
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
  ordem INT NOT NULL DEFAULT 0,
  ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS config_comercial (
  chave VARCHAR(60) PRIMARY KEY,
  valor VARCHAR(255) DEFAULT NULL,
  descricao VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  tipo_plano ENUM('turma','gremio','todos') NOT NULL DEFAULT 'todos',
  percentual DECIMAL(5,2) DEFAULT NULL,   -- NULL = usa o percentual padrão da configuração
  valido_ate DATE DEFAULT NULL,
  usos_max INT DEFAULT NULL,
  usos INT NOT NULL DEFAULT 0,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assinaturas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  plano_id INT NOT NULL,
  status ENUM('aguardando','ativa','cancelada') NOT NULL DEFAULT 'aguardando',
  inicio DATETIME DEFAULT NULL,           -- definido quando a 1ª parcela é paga
  fim DATETIME DEFAULT NULL,              -- NULL com status ativa = sem vencimento
  projetos_limite INT NOT NULL,
  valor_lista DECIMAL(10,2) NOT NULL,
  desconto_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  valor_total DECIMAL(10,2) NOT NULL,
  parcelas INT NOT NULL DEFAULT 1,
  cupom_id INT DEFAULT NULL,
  comprado_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (plano_id) REFERENCES planos(id),
  FOREIGN KEY (cupom_id) REFERENCES cupons(id) ON DELETE SET NULL,
  FOREIGN KEY (comprado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_ass_turma (turma_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pagamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assinatura_id INT NOT NULL,
  numero INT NOT NULL,                    -- nº da parcela (1..parcelas)
  valor DECIMAL(10,2) NOT NULL,
  vencimento DATE NOT NULL,
  status ENUM('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
  metodo VARCHAR(20) NOT NULL DEFAULT 'pix_teste',
  txid VARCHAR(40) DEFAULT NULL,
  pago_em DATETIME DEFAULT NULL,
  pago_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_parcela (assinatura_id, numero),
  FOREIGN KEY (assinatura_id) REFERENCES assinaturas(id) ON DELETE CASCADE,
  FOREIGN KEY (pago_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO planos (codigo, tipo, nome, meses, projetos, pessoas_max, preco, piso, max_parcelas, tabela_parcelas, ordem) VALUES
  ('trimestral', 'turma',  'Trimestral',  3,    2,  50,  99.90,  69.90,  1, NULL, 1),
  ('semestral',  'turma',  'Semestral',   6,    4,  50, 167.90, 117.50,  2, 'A',  2),
  ('anual',      'turma',  'Anual',       12,   8,  50, 279.90, 195.90,  4, 'A',  3),
  ('completo',   'turma',  'Completo',    18,  12,  50, 359.90, 251.90,  6, 'A',  4),
  ('estendido',  'turma',  'Estendido',   36,  24,  50, 529.90, 370.90, 12, 'B',  5),
  ('vitalicio',  'turma',  'Vitalício',   NULL, 36, 50, 999.90, 699.90, 12, 'B',  6),
  ('gremio20',   'gremio', 'Grêmio 20',   12,  10,  20, 234.90, 199.60,  4, 'A', 11),
  ('gremio25',   'gremio', 'Grêmio 25',   12,  10,  25, 259.90, 220.90,  4, 'A', 12),
  ('gremio30',   'gremio', 'Grêmio 30',   12,  10,  30, 284.90, 242.10,  4, 'A', 13),
  ('gremio40',   'gremio', 'Grêmio 40',   12,  10,  40, 334.90, 284.60,  4, 'A', 14);

INSERT IGNORE INTO config_comercial (chave, valor, descricao) VALUES
  ('cupom_turma_pct',  '30', 'Desconto padrão de cupom em planos de turma (%)'),
  ('cupom_gremio_pct', '15', 'Desconto padrão de cupom em planos de grêmio (%)'),
  ('indicacao_pct',    '10', 'Desconto da turma indicada na 1ª compra (%)'),
  ('modo_pagamento',   'teste', 'teste = pagamento simulado; mercadopago = cobrança real (futuro)');
