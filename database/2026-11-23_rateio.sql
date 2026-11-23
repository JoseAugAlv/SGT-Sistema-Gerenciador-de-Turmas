-- database/2026-11-23_rateio.sql
-- Rateio da mensalidade: o representante divide uma parcela do plano entre os alunos; cada um paga a sua
-- parte por Pix. Com todas pagas (ou o restante coberto pelo representante), a parcela é quitada.
CREATE TABLE IF NOT EXISTS rateios (
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

CREATE TABLE IF NOT EXISTS rateio_partes (
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
