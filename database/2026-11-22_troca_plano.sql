SET NAMES utf8mb4;
-- Troca de plano (upgrade): a nova assinatura aponta para a antiga; ao pagar a 1ª parcela, a antiga vira 'trocada'.
ALTER TABLE assinaturas
  MODIFY status ENUM('aguardando','ativa','cancelada','trocada') NOT NULL DEFAULT 'aguardando',
  ADD COLUMN troca_de INT NULL AFTER cupom_id,
  ADD CONSTRAINT assinaturas_ibfk_5 FOREIGN KEY (troca_de) REFERENCES assinaturas (id) ON DELETE SET NULL;
