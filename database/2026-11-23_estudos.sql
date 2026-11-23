-- database/2026-11-23_estudos.sql
-- Banco de materiais de estudo da turma, por disciplina (arquivo, link ou resumo), opcionalmente ligado a
-- uma prova/entrega da agenda. Qualquer membro compartilha; quem enviou ou o representante remove.
CREATE TABLE IF NOT EXISTS estudo_materiais (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  autor_id INT NULL,
  disciplina VARCHAR(80) NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  tipo ENUM('arquivo','link','resumo') NOT NULL DEFAULT 'arquivo',
  arquivo VARCHAR(255) NULL,                -- caminho em public/ (uploads/estudos/...)
  nome_original VARCHAR(200) NULL,
  url VARCHAR(500) NULL,
  texto TEXT NULL,                          -- resumo escrito
  evento_id INT NULL,                       -- prova/entrega da agenda a que o material ajuda
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (evento_id) REFERENCES eventos_calendario(id) ON DELETE SET NULL,
  INDEX idx_estudo_turma (turma_id, disciplina)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
