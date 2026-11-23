-- database/2026-11-24_lembrar_login.sql
-- "Manter conectado": cada aparelho guarda um cookie seletor:validador; aqui fica só o hash do validador.
CREATE TABLE IF NOT EXISTS login_lembrar (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  seletor CHAR(24) NOT NULL UNIQUE,
  validador_hash CHAR(64) NOT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  expira_em DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lembrar_usuario (usuario_id),
  INDEX idx_lembrar_expira (expira_em),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
