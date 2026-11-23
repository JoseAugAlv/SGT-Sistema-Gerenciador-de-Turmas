-- database/2026-11-23_escolas.sql
-- Página "Para escolas": descontos por volume (configuráveis) e pedidos de proposta das escolas.
CREATE TABLE IF NOT EXISTS escola_contatos (
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
INSERT INTO config_comercial (chave, valor, descricao) VALUES
  ('escola_desc_5',  '10', 'Pacote para escolas: desconto (%) a partir de 5 turmas'),
  ('escola_desc_10', '15', 'Pacote para escolas: desconto (%) a partir de 10 turmas'),
  ('escola_desc_20', '20', 'Pacote para escolas: desconto (%) a partir de 20 turmas')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);
