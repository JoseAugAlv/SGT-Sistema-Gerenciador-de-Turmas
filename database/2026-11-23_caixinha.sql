-- database/2026-11-23_caixinha.sql
-- Caixinha anônima: dúvidas, sugestões, reclamações e elogios para o representante.
-- autor_id existe só para avisar o autor quando houver resposta e limitar abusos: nenhuma tela mostra quem enviou.
CREATE TABLE IF NOT EXISTS caixinha_mensagens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  autor_id INT NULL,
  tipo ENUM('duvida','sugestao','reclamacao','elogio') NOT NULL DEFAULT 'duvida',
  texto TEXT NOT NULL,
  status ENUM('nova','lida','respondida','arquivada') NOT NULL DEFAULT 'nova',
  resposta TEXT NULL,
  respondida_por INT NULL,
  respondida_em DATETIME NULL,
  publica TINYINT(1) NOT NULL DEFAULT 0,          -- 1 = pergunta e resposta aparecem para a turma toda
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (respondida_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_caixinha_turma (turma_id, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
