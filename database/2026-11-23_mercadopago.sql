-- database/2026-11-23_mercadopago.sql
-- Integração real com o Mercado Pago (Pix): id do pagamento no Mercado Pago, Pix copia e cola, QR (imagem)
-- e validade. Usado só com config modo_pagamento = 'mercadopago' e MP_ACCESS_TOKEN no .env.
SET SQL_SAFE_UPDATES = 0;
ALTER TABLE pagamentos
  ADD COLUMN mp_id VARCHAR(40) NULL AFTER txid,
  ADD COLUMN pix_copia TEXT NULL AFTER mp_id,
  ADD COLUMN pix_qr MEDIUMTEXT NULL AFTER pix_copia,
  ADD COLUMN pix_expira DATETIME NULL AFTER pix_qr;
SET SQL_SAFE_UPDATES = 1;
