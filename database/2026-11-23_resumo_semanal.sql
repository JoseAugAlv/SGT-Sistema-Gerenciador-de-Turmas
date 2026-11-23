-- database/2026-11-23_resumo_semanal.sql
-- Resumo semanal por email (segunda-feira): preferência de cada um e controle de envio (uma vez por semana).
SET SQL_SAFE_UPDATES = 0;
ALTER TABLE preferencias_usuario ADD COLUMN resumo_semanal TINYINT(1) NOT NULL DEFAULT 1 AFTER nivel_email;
CREATE TABLE IF NOT EXISTS resumo_envios (
  usuario_id INT NOT NULL,
  semana CHAR(8) NOT NULL,                  -- ex.: 2026-W48
  enviado_em DATETIME NOT NULL,
  ok TINYINT(1) NOT NULL DEFAULT 1,         -- 0 = não tinha nada para resumir ou o envio falhou
  PRIMARY KEY (usuario_id, semana),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SET SQL_SAFE_UPDATES = 1;
