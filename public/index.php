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
error_reporting($debug ? E_ALL : 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// 3. Carregar o resto do sistema
require_once __DIR__ . '/../app/Config/config.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Router.php';
$router = new Router();

require_once __DIR__ . '/../routes/web.php';

// 4. HEADERS DE SEGURANÇA
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (App::get('APP_ENV') === 'production' && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$router->dispatch($_SERVER['REQUEST_URI']);