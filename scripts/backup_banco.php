<?php
// scripts/backup_banco.php
// Cópia do banco em backups/prumo_AAAAMMDD_HHMM.sql.gz, guardando as 8 mais recentes.
// Feito só com PHP (PDO), porque na hospedagem compartilhada nem sempre dá para rodar o mysqldump.
// A pasta backups/ fica fora de public/: não é acessível pelo navegador.
// Hostinger (Cron Jobs, ex.: todo domingo às 3h):  php /home/USUARIO/domains/SEU_DOMINIO/public_html/projetos/Prumo/scripts/backup_banco.php
// Para baixar: Gerenciador de Arquivos da Hostinger > projetos/Prumo/backups.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Só pela linha de comando.'); }
date_default_timezone_set('America/Sao_Paulo');
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../app/Core/App.php';
App::init();
require_once __DIR__ . '/../app/Config/database.php';

const GUARDAR = 8;
$pdo = Database::getConnection();
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);   // tabelas grandes sem estourar a memória
$dir = __DIR__ . '/../backups';
if (!is_dir($dir)) mkdir($dir, 0750, true);
$arquivo = $dir . '/prumo_' . date('Ymd_Hi') . '.sql.gz';
$gz = gzopen($arquivo, 'wb6');
$escrever = fn(string $s) => gzwrite($gz, $s);

$escrever("-- Backup do Prumo · " . date('Y-m-d H:i:s') . "\n-- Restaurar: phpMyAdmin > banco > Importar (pode enviar o .gz direto)\n");
$escrever("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

$tabelas = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
$linhas = 0;
foreach ($tabelas as $t) {
    $criar = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_NUM)[1];
    $escrever("DROP TABLE IF EXISTS `{$t}`;\n{$criar};\n");
    $st = $pdo->query("SELECT * FROM `{$t}`");
    $lote = [];
    while ($r = $st->fetch(PDO::FETCH_NUM)) {
        $lote[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $r)) . ')';
        $linhas++;
        if (count($lote) >= 200) { $escrever("INSERT INTO `{$t}` VALUES\n" . implode(",\n", $lote) . ";\n"); $lote = []; }
    }
    if ($lote) $escrever("INSERT INTO `{$t}` VALUES\n" . implode(",\n", $lote) . ";\n");
    $st->closeCursor();
    $escrever("\n");
}
$escrever("SET FOREIGN_KEY_CHECKS = 1;\n");
gzclose($gz);

// guarda só as mais recentes
$todos = glob($dir . '/prumo_*.sql.gz');
rsort($todos);
foreach (array_slice($todos, GUARDAR) as $velho) @unlink($velho);

printf("%s · %d tabelas · %d linhas · %s (%.1f KB)\n", date('Y-m-d H:i'), count($tabelas), $linhas, basename($arquivo), filesize($arquivo) / 1024);
