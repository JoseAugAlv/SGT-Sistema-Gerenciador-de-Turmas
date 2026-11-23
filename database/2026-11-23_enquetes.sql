-- database/2026-11-23_enquetes.sql
-- Enquetes e votações da turma/grêmio: o representante (ou vice/presidente) cria; todos da turma votam.
CREATE TABLE IF NOT EXISTS enquetes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  criado_por INT NOT NULL,
  pergunta VARCHAR(200) NOT NULL,
  descricao TEXT NULL,
  multipla TINYINT(1) NOT NULL DEFAULT 0,         -- 1 = pode marcar mais de uma opção
  anonima TINYINT(1) NOT NULL DEFAULT 1,          -- 1 = ninguém vê quem votou em quê
  resultado ENUM('sempre','apos_votar','apos_encerrar') NOT NULL DEFAULT 'apos_votar',
  encerra_em DATETIME NULL,                       -- NULL = fica aberta até encerrar à mão
  encerrada_em DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_enquete_turma (turma_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS enquete_opcoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  enquete_id INT NOT NULL,
  texto VARCHAR(150) NOT NULL,
  ordem INT NOT NULL DEFAULT 0,
  FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS enquete_votos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  enquete_id INT NOT NULL,
  opcao_id INT NOT NULL,
  usuario_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_voto (enquete_id, opcao_id, usuario_id),
  FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE,
  FOREIGN KEY (opcao_id) REFERENCES enquete_opcoes(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
