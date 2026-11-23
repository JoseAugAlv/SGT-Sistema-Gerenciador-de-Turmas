-- database/2026-11-23_renovacao.sql
-- Renovação do plano: o representante escolhe o próximo plano antes do fim; pago, o novo período começa
-- no fim do atual (sem perder dias). Perto do fim, a turma ganha um cupom exclusivo de renovação.
SET SQL_SAFE_UPDATES = 0;
ALTER TABLE assinaturas
  MODIFY status ENUM('aguardando','ativa','cancelada','trocada','renovada') NOT NULL DEFAULT 'aguardando',
  ADD COLUMN renovacao_de INT NULL AFTER troca_de,
  ADD COLUMN cupom_renovacao_id INT NULL AFTER renovacao_de;
ALTER TABLE cupons MODIFY origem ENUM('manual','pacote','indicacao','renovacao') NOT NULL DEFAULT 'manual';
INSERT INTO config_comercial (chave, valor, descricao) VALUES
  ('renovacao_cupom_pct', '10', 'Cupom automático de renovação: desconto (%) enviado quando o plano está perto de vencer (0 = sem cupom)')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);
SET SQL_SAFE_UPDATES = 1;
