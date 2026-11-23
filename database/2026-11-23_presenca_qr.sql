-- database/2026-11-23_presenca_qr.sql
-- Presença por QR Code nas reuniões: o organizador abre o QR (que muda a cada 30 s) e cada convocado marca
-- a própria presença escaneando. via = como a presença foi marcada.
SET SQL_SAFE_UPDATES = 0;
ALTER TABLE reunioes
  ADD COLUMN qr_token VARCHAR(40) NULL AFTER status,
  ADD COLUMN qr_ate DATETIME NULL AFTER qr_token;
ALTER TABLE reuniao_presencas
  ADD COLUMN via ENUM('manual','qr') NOT NULL DEFAULT 'manual' AFTER presente,
  ADD COLUMN marcado_em DATETIME NULL AFTER via;
SET SQL_SAFE_UPDATES = 1;
