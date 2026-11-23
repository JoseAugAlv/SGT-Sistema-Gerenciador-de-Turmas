-- database/2026-11-23_teste_dia_a_dia.sql
-- Teste grátis do "Dia a dia" (agenda, horário, aviso do dia, contagem) para turmas cujo plano não o inclui.
-- Cada turma pode testar uma única vez; teste_dia_ate guarda até quando vale (NULL = nunca testou).
SET SQL_SAFE_UPDATES = 0;
ALTER TABLE turmas ADD COLUMN teste_dia_ate DATETIME NULL AFTER isenta_plano;
INSERT INTO config_comercial (chave, valor, descricao) VALUES
  ('teste_dia_dias', '14', 'Dias do teste grátis do Dia a dia (0 = sem teste)')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);
SET SQL_SAFE_UPDATES = 1;
