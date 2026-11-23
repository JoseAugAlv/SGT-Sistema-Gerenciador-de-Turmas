<?php

// Fuso do Brasil, igual ao do MySQL: prazos e vencimentos comparam datas do PHP com as do banco
date_default_timezone_set('America/Sao_Paulo');

// Hospedagem atrás de proxy/CDN (https termina no proxy): o PHP passa a saber que a conexão é segura,
// para cookies "secure" e links gerados (QR de presença, Pix, emails) saírem com https
if ((($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') && empty($_SERVER['HTTPS'])) {
    $_SERVER['HTTPS'] = 'on';
}

// 1. PRIMEIRO: Carregar o App para poder usar as configurações
require_once __DIR__ . '/../app/Config/SessionConfig.php';
SessionConfig::configure();

require_once __DIR__ . '/../app/Core/App.php';
App::init();

// 2. AGORA podemos usar App::get() para configurações
$debug = App::get('APP_ENV', 'production') !== 'production';
ini_set('display_errors', $debug ? 1 : 0);
// sempre registra no log (antes, em produção, error_reporting(0) escondia até do log); o usuário nunca vê o erro
error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('log_errors', 1);
if (!is_dir(__DIR__ . '/../logs')) @mkdir(__DIR__ . '/../logs', 0755, true);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
if (!$debug) {
    $paginaErro = function () {
        if (headers_sent()) return;
        http_response_code(500);
        $basePath = App::getBasePath();
        require __DIR__ . '/../app/Views/erros/500.php';
    };
    set_exception_handler(function (Throwable $e) use ($paginaErro) {
        error_log('Exceção: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine()
            . ' · ' . ($_SERVER['REQUEST_METHOD'] ?? '') . ' ' . ($_SERVER['REQUEST_URI'] ?? '') . "\n" . $e->getTraceAsString());
        $paginaErro();
    });
    register_shutdown_function(function () use ($paginaErro) {
        $er = error_get_last();
        if ($er && in_array($er['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) $paginaErro();
    });
}

// 3. Carregar o resto do sistema
require_once __DIR__ . '/../app/Config/config.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Router.php';
$router = new Router();

require_once __DIR__ . '/../routes/web.php';

// "Manter conectado": sem sessão, mas com o cookie do aparelho, entra de novo sozinho
if (empty($_SESSION['usuario']) && !empty($_COOKIE['PRUMO_LEMBRAR'])) {
    require_once __DIR__ . '/../app/Services/LembrarLoginService.php';
    $lembrado = LembrarLoginService::usuarioDoCookie();
    if ($lembrado) {
        session_regenerate_id(true);
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->montarSessao($lembrado);
    }
}

// 4. HEADERS DE SEGURANÇA
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (App::get('APP_ENV') === 'production' && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$router->dispatch($_SERVER['REQUEST_URI']);