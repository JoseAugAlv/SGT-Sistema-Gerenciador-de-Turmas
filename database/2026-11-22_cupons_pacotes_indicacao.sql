SET NAMES utf8mb4;
-- Cupons com desconto por plano (% ou R$), pacotes para escolas (um cupom gratuito por turma)
-- e indicação (cada turma tem um código; quem indica ganha crédito de desconto).

CREATE TABLE IF NOT EXISTS pacotes (
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

ALTER TABLE cupons
  ADD COLUMN origem ENUM('manual','pacote','indicacao') NOT NULL DEFAULT 'manual' AFTER codigo,
  ADD COLUMN pacote_id INT NULL AFTER origem,
  ADD COLUMN turma_dona_id INT NULL AFTER pacote_id,
  ADD CONSTRAINT fk_cupom_pacote FOREIGN KEY (pacote_id) REFERENCES pacotes(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_cupom_turma_dona FOREIGN KEY (turma_dona_id) REFERENCES turmas(id) ON DELETE CASCADE;

-- Desconto do cupom em cada plano. Sem linhas aqui, o cupom segue a regra antiga (tipo de plano + percentual).
CREATE TABLE IF NOT EXISTS cupom_planos (
  cupom_id INT NOT NULL,
  plano_id INT NOT NULL,
  tipo_desconto ENUM('pct','valor') NOT NULL DEFAULT 'pct',
  valor DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (cupom_id, plano_id),
  FOREIGN KEY (cupom_id) REFERENCES cupons(id) ON DELETE CASCADE,
  FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crédito de quem indicou: % de desconto nas próximas parcelas ou na próxima compra
CREATE TABLE IF NOT EXISTS creditos_indicacao (
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

INSERT IGNORE INTO config_comercial (chave, valor, descricao) VALUES
  ('indicacao_recompensa_pct', '10', 'Indicação: desconto (%) que a turma que indicou ganha nas próximas parcelas ou na próxima compra');
