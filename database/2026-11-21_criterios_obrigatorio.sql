-- Critério obrigatório: o aluno precisa preencher (pares/autoavaliação) antes do prazo.
ALTER TABLE criterios
  ADD COLUMN obrigatorio TINYINT(1) NOT NULL DEFAULT 0 AFTER prazo_avaliacao;
