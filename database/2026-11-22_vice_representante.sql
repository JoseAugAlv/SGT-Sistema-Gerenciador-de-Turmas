SET NAMES utf8mb4;
SET SQL_SAFE_UPDATES = 0;
-- Vice-representante: escolhido pelo representante principal; avalia e gerencia, mas não acessa plano/pagamento.
-- Projeto: como os dois representantes avaliam (mesma nota compartilhada ou média das duas notas).
ALTER TABLE turma_usuarios
  ADD COLUMN vice TINYINT(1) NOT NULL DEFAULT 0 AFTER papel;

ALTER TABLE projetos
  ADD COLUMN avaliacao_reps ENUM('compartilhada','media') NOT NULL DEFAULT 'compartilhada' AFTER diretores_por_grupo;

-- Projetos que já existiam continuam como antes (média das notas dos representantes)
UPDATE projetos SET avaliacao_reps = 'media';
