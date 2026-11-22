-- Identidade visual da turma (cor e logo escolhidos pelo representante)
ALTER TABLE turmas
  ADD COLUMN cor_personalizada TINYINT(1) NOT NULL DEFAULT 0 AFTER cor_secundaria,
  ADD COLUMN logo VARCHAR(255) DEFAULT NULL AFTER cor_personalizada;
