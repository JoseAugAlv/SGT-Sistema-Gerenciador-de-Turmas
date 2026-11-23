-- database/2026-11-23_mural.sql
-- Mural de fotos e conquistas da turma/grêmio. Representante/vice publicam direto; o que os alunos enviam
-- espera a aprovação do representante. Curtidas: uma por pessoa por publicação.
CREATE TABLE IF NOT EXISTS mural_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  autor_id INT NULL,
  tipo ENUM('foto','conquista') NOT NULL DEFAULT 'foto',
  icone ENUM('trofeu','medalha','estrela','foguete','coracao') NULL,   -- conquista sem foto
  titulo VARCHAR(120) NOT NULL,
  texto VARCHAR(600) NULL,
  arquivo VARCHAR(255) NULL,                     -- caminho em public/ (uploads/mural/...)
  aprovado TINYINT(1) NOT NULL DEFAULT 0,
  fixado TINYINT(1) NOT NULL DEFAULT 0,          -- aparece primeiro
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_mural_turma (turma_id, aprovado, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mural_curtidas (
  post_id INT NOT NULL,
  usuario_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (post_id, usuario_id),
  FOREIGN KEY (post_id) REFERENCES mural_posts(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
