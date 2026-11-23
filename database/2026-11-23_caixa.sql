-- database/2026-11-23_caixa.sql
-- Controle de caixa da turma/grêmio: entradas e saídas com comprovante. Todos da turma veem (transparência);
-- só representante/vice/master lançam. pessoa_id = quem pagou (contribuição); diretoria_id = diretoria do grêmio.
CREATE TABLE IF NOT EXISTS caixa_lancamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  tipo ENUM('entrada','saida') NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  descricao VARCHAR(200) NOT NULL,
  categoria VARCHAR(60) NOT NULL DEFAULT 'Outros',
  data DATE NOT NULL,
  pessoa_id INT NULL,
  diretoria_id INT NULL,
  comprovante VARCHAR(255) NULL,            -- caminho em public/ (uploads/caixa/...)
  comprovante_nome VARCHAR(200) NULL,
  registrado_por INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (pessoa_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (diretoria_id) REFERENCES gremio_diretorias(id) ON DELETE SET NULL,
  FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_caixa_turma (turma_id, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
