-- database/2026-11-23_tutorial.sql
-- Tutorial guiado: quais tours cada pessoa já fez ou dispensou (tour '*' = não sugerir mais nenhum).
CREATE TABLE IF NOT EXISTS tutorial_vistos (
  usuario_id INT NOT NULL,
  tour VARCHAR(40) NOT NULL,
  status ENUM('feito','dispensado') NOT NULL DEFAULT 'feito',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, tour),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
