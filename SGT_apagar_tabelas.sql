-- =====================================================================
-- Prumo — APAGA TODAS AS TABELAS do banco selecionado (não apaga o banco em si)
-- No phpMyAdmin: clique no banco da hospedagem à esquerda > aba SQL > cole tudo > Executar.
-- ATENÇÃO: some com todos os dados, sem volta. Faça um Exportar antes se quiser backup.
-- Depois, importe o SGT_producao.sql para recriar tudo zerado.
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 0;
SET SESSION group_concat_max_len = 1000000;

-- monta "DROP TABLE IF EXISTS `a`, `b`, ..." com todas as tabelas que existem neste banco
SET @tabelas = NULL;
SELECT GROUP_CONCAT('`', table_name, '`') INTO @tabelas
  FROM information_schema.tables
 WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';

SET @sql = IF(@tabelas IS NULL, 'SELECT ''Nenhuma tabela para apagar'' AS aviso', CONCAT('DROP TABLE IF EXISTS ', @tabelas));
PREPARE apagar FROM @sql;
EXECUTE apagar;
DEALLOCATE PREPARE apagar;

SET FOREIGN_KEY_CHECKS = 1;

-- conferência: deve mostrar 0
SELECT COUNT(*) AS tabelas_restantes FROM information_schema.tables WHERE table_schema = DATABASE();
