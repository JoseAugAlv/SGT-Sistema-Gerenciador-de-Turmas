-- database/2026-11-23_kanban.sql
-- Kanban de tarefas do grupo: colunas A fazer / Fazendo / Revisão / Feito, responsável, prazo e ata ligada.
CREATE TABLE IF NOT EXISTS grupo_tarefas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  descricao TEXT NULL,
  responsavel_id INT NULL,
  prazo DATE NULL,
  coluna ENUM('a_fazer','fazendo','revisao','feito') NOT NULL DEFAULT 'a_fazer',
  ordem INT NOT NULL DEFAULT 0,
  ata_id INT NULL,                          -- ata do grupo em que a tarefa foi combinada
  criado_por INT NULL,
  concluida_em DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
  FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (ata_id) REFERENCES atas(id) ON DELETE SET NULL,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_tarefa_grupo (grupo_id, coluna, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
