-- Desconto na nota final do aluno que não preencheu um critério obrigatório.
-- Pontos na escala 0–100, subtraídos da média ponderada do projeto.
CREATE TABLE IF NOT EXISTS descontos_nota (
  id INT AUTO_INCREMENT PRIMARY KEY,
  criterio_id INT NOT NULL,
  aluno_id INT NOT NULL,
  pontos DECIMAL(5,2) NOT NULL,
  motivo VARCHAR(255) NOT NULL,
  aplicado_por INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_desconto (criterio_id, aluno_id),
  FOREIGN KEY (criterio_id) REFERENCES criterios(id) ON DELETE CASCADE,
  FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (aplicado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_desc_aluno (aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
