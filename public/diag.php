<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "PASSO 1: arquivo executou<br>";
flush();

require_once __DIR__ . '/../app/Config/SessionConfig.php';
echo "PASSO 2: SessionConfig.php carregado<br>";
flush();

SessionConfig::configure();
echo "PASSO 3: sessão iniciada<br>";
flush();

require_once __DIR__ . '/../app/Core/App.php';
echo "PASSO 4: App.php carregado<br>";
flush();

App::init();
echo "PASSO 5: App inicializado<br>";
flush();

echo "<pre>CONFIG LIDO:<br>";
print_r(App::getAll());
echo "</pre>";
flush();

require_once __DIR__ . '/../app/Config/config.php';
echo "PASSO 6: config.php carregado<br>";
flush();

require_once __DIR__ . '/../app/Config/database.php';
echo "PASSO 7: database.php carregado<br>";
flush();

try {
    Database::getConnection();
    echo "PASSO 8: conexão ao banco OK<br>";
} catch (Throwable $e) {
    echo "ERRO BANCO: " . $e->getMessage() . "<br>";
}
flush();

require_once __DIR__ . '/../app/Core/Router.php';
echo "PASSO 9: Router.php carregado<br>";
flush();

$router = new Router();
echo "PASSO 10: Router instanciado<br>";
flush();

require_once __DIR__ . '/../routes/web.php';
echo "PASSO 11: routes/web.php carregado<br>";
flush();

echo "<br><b>TUDO CARREGOU SEM FATAL ERROR</b>";