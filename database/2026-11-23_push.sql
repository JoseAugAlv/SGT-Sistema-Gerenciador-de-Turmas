-- database/2026-11-23_push.sql
-- App instalável (PWA) com notificações no celular/computador (Web Push). Cada aparelho que ativa as
-- notificações vira uma inscrição. As chaves VAPID do servidor ficam em config_comercial (geradas no 1º uso).
CREATE TABLE IF NOT EXISTS push_inscricoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  endpoint VARCHAR(700) NOT NULL,
  endpoint_hash CHAR(64) NOT NULL,
  p256dh VARCHAR(150) NOT NULL,
  auth VARCHAR(60) NOT NULL,
  aparelho VARCHAR(150) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ultimo_envio DATETIME NULL,
  UNIQUE KEY uq_push_endpoint (endpoint_hash),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
