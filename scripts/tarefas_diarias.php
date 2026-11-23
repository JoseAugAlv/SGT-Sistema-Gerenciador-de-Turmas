<?php
// scripts/tarefas_diarias.php
// Uma vez por dia (cron da Hostinger, ex.: todo dia às 7h):
//   1. lembrete de véspera dos eventos da agenda para todos (antes só saía quando a pessoa abria o site);
//   2. aviso de plano vencendo / cupom de renovação para as turmas com plano ativo;
//   3. limpeza dos tokens de "manter conectado" vencidos.
// Hostinger (hPanel > Avançado > Cron Jobs):  php /home/USUARIO/domains/SEU_DOMINIO/public_html/projetos/Prumo/scripts/tarefas_diarias.php
// Local (Windows):                           C:\xampp\php\php.exe C:\xampp\htdocs\SGT\scripts\tarefas_diarias.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Só pela linha de comando.'); }
date_default_timezone_set('America/Sao_Paulo');
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../app/Core/App.php';
App::init();
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Models/Notificacao.php';
require_once __DIR__ . '/../app/Controllers/AgendaController.php';
require_once __DIR__ . '/../app/Helpers/PlanoGuard.php';

$pdo = Database::getConnection();
$inicio = microtime(true);

// 1. lembretes de véspera (a mesma regra da agenda: um por evento e pessoa)
$lembretes = 0;
$usuarios = $pdo->query("
    SELECT DISTINCT u.id, u.tipo FROM usuarios u
    INNER JOIN turma_usuarios tu ON tu.usuario_id = u.id AND tu.ativo = 1
    WHERE u.ativo = 1 AND u.cadastro_pendente = 0
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($usuarios as $u) {
    try {
        $_SESSION = ['usuario' => ['id' => (int) $u['id'], 'tipo' => $u['tipo']]];
        $ctx = AgendaController::contexto();
        $antes = (int) $pdo->query("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = " . (int) $u['id'] . " AND tipo = 'lembrete'")->fetchColumn();
        AgendaController::gerarLembretes($ctx);
        $lembretes += (int) $pdo->query("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = " . (int) $u['id'] . " AND tipo = 'lembrete'")->fetchColumn() - $antes;
    } catch (Throwable $e) {
        error_log('tarefas_diarias (lembrete, usuário ' . $u['id'] . '): ' . $e->getMessage());
    }
}
$_SESSION = [];

// 2. planos vencendo: PlanoGuard::situacao dispara o aviso/cupom de renovação (uma vez por assinatura)
$turmasVerificadas = 0;
$turmas = $pdo->query("SELECT DISTINCT t.* FROM turmas t INNER JOIN assinaturas a ON a.turma_id = t.id AND a.status = 'ativa'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($turmas as $t) {
    try { PlanoGuard::situacao($t); $turmasVerificadas++; }
    catch (Throwable $e) { error_log('tarefas_diarias (plano, turma ' . $t['id'] . '): ' . $e->getMessage()); }
}

// 3. limpeza
$limpos = $pdo->exec("DELETE FROM login_lembrar WHERE expira_em < NOW()");

Notificacao::despacharFila();   // envia já os emails/pushes dos lembretes
printf("%s · lembretes: %d · turmas com plano verificadas: %d · logins vencidos apagados: %d · %.1fs\n",
    date('Y-m-d H:i'), $lembretes, $turmasVerificadas, (int) $limpos, microtime(true) - $inicio);
