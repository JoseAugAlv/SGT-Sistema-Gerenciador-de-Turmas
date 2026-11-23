SET NAMES utf8mb4;
SET SQL_SAFE_UPDATES = 0;
-- Troca com crédito proporcional: valor_base = quanto o plano "vale" pago (à vista, já com desconto, sem acréscimo de parcelas).
-- Na troca, o tempo que falta do plano atual vira crédito sobre esse valor.
ALTER TABLE assinaturas ADD COLUMN valor_base DECIMAL(10,2) DEFAULT NULL AFTER valor_total;

UPDATE assinaturas a
INNER JOIN planos p ON p.id = a.plano_id
SET a.valor_base = LEAST(a.valor_total,
    CASE WHEN a.desconto_pct > 0 THEN GREATEST(p.piso, ROUND(a.valor_lista * (1 - a.desconto_pct / 100), 2)) ELSE a.valor_lista END)
WHERE a.troca_de IS NULL;

UPDATE assinaturas a INNER JOIN planos p ON p.id = a.plano_id
SET a.valor_base = p.preco
WHERE a.troca_de IS NOT NULL;
