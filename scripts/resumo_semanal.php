<?php
// scripts/resumo_semanal.php
// Envia o resumo semanal por email para quem ainda não recebeu nesta semana.
// Agende para toda segunda às 7h (Agendador de Tarefas do Windows):
//   C:\xampp\php\php.exe C:\xampp\htdocs\SGT\scripts\resumo_semanal.php
// Uso: php scripts/resumo_semanal.php [--limite=500]
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Só pela linha de comando.'); }
date_default_timezone_set('America/Sao_Paulo');
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../app/Core/App.php';
App::init();
require_once __DIR__ . '/../app/Services/ResumoSemanalService.php';

$limite = 500;
foreach ($argv as $a) if (preg_match('/^--limite=(\d+)$/', $a, $m)) $limite = (int) $m[1];
$r = (new ResumoSemanalService())->enviarLote($limite);
echo date('Y-m-d H:i') . " · semana " . ResumoSemanalService::semana() . " · enviados: {$r['enviados']} · sem nada para resumir: {$r['vazios']}\n";
