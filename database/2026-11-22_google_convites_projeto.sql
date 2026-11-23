SET NAMES utf8mb4;
-- Login com Google, cadastro pendente (Google/convite), preferência de email, convites por email,
-- contagem regressiva nos avisos, configuração do projeto (grupos/diretores) e esquema de notas.

ALTER TABLE usuarios
  ADD COLUMN google_id VARCHAR(64) NULL AFTER senha,
  ADD COLUMN cadastro_pendente TINYINT(1) NOT NULL DEFAULT 0 AFTER primeiro_login,
  ADD UNIQUE KEY uk_google_id (google_id);

ALTER TABLE preferencias_usuario
  ADD COLUMN nivel_email ENUM('todas','importantes','mencoes','nenhuma') NOT NULL DEFAULT 'importantes' AFTER usuario_id;

CREATE TABLE IF NOT EXISTS convites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  turma_id INT NOT NULL,
  usuario_id INT NOT NULL,
  email VARCHAR(150) NOT NULL,
  token VARCHAR(64) NOT NULL,
  status ENUM('pendente','aceito','cancelado') NOT NULL DEFAULT 'pendente',
  convidado_por INT DEFAULT NULL,
  expira_em DATETIME NOT NULL,
  aceito_em DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_convite_token (token),
  KEY idx_convite_turma (turma_id, status),
  FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (convidado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE alertas
  ADD COLUMN contagem_ate DATETIME NULL AFTER urgente;

ALTER TABLE projetos
  ADD COLUMN alunos_pedem_grupo TINYINT(1) NOT NULL DEFAULT 0 AFTER modo_avaliacao,
  ADD COLUMN alunos_pedem_diretor TINYINT(1) NOT NULL DEFAULT 0 AFTER alunos_pedem_grupo,
  ADD COLUMN diretores_por_grupo INT NOT NULL DEFAULT 1 AFTER alunos_pedem_diretor;

ALTER TABLE configuracoes_conceito
  ADD COLUMN esquema_nota ENUM('mencao','0a10','0a100') NOT NULL DEFAULT 'mencao' AFTER projeto_id;

CREATE TABLE IF NOT EXISTS solicitacoes_grupo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  usuario_id INT NOT NULL,
  tipo ENUM('entrar','diretor') NOT NULL,
  status ENUM('pendente','aprovada','recusada','cancelada') NOT NULL DEFAULT 'pendente',
  respondida_por INT DEFAULT NULL,
  respondida_em DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_solic_grupo (grupo_id, status),
  KEY idx_solic_usuario (usuario_id, status),
  FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (respondida_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
