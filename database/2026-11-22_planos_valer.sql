SET NAMES utf8mb4;
SET SQL_SAFE_UPDATES = 0;
-- Plano passa a valer: turma sem plano ativo fica bloqueada (exceto isentas).
-- As turmas que já existiam ficam isentas, para nada parar de funcionar; o master pode tirar a isenção.

ALTER TABLE turmas
  ADD COLUMN isenta_plano TINYINT(1) NOT NULL DEFAULT 0 AFTER bloqueada_por;

UPDATE turmas SET isenta_plano = 1;

INSERT IGNORE INTO config_comercial (chave, valor, descricao) VALUES
  ('exigir_plano', '1', '1 = turma sem plano ativo é bloqueada (exceto isentas); 0 = cobrança desligada'),
  ('tolerancia_dias', '5', 'Dias de tolerância após vencimento ou parcela atrasada, antes do bloqueio'),
  ('aviso_vencimento_dias', '7', 'Quantos dias antes do fim do plano o representante começa a ser avisado');
