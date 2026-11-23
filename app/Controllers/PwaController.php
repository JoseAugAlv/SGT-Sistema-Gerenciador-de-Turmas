<?php
// app/Controllers/PwaController.php
// App instalável (PWA): manifesto, service worker (servido na raiz do app para cobrir todas as páginas),
// página offline e inscrição das notificações no aparelho (Web Push).
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Services/PushService.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class PwaController
{
    public function manifesto()
    {
        $b = App::getBasePath();
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo json_encode([
            'name' => App::getName() . ' · turmas e grêmios',
            'short_name' => App::getName(),
            'description' => 'Projetos, avaliações, atas, agenda e avisos da turma e do grêmio.',
            'lang' => 'pt-BR',
            'start_url' => $b . '/?app=1',
            'scope' => $b . '/',
            'display' => 'standalone',
            'background_color' => '#f6f7fb',
            'theme_color' => '#1d4ed8',
            'icons' => [
                ['src' => $b . '/public/img/logo-prumo.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $b . '/public/img/logo-prumo.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ],
            'shortcuts' => [
                ['name' => 'Agenda', 'url' => $b . '/agenda'],
                ['name' => 'Notificações', 'url' => $b . '/notificacoes'],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** O service worker precisa ser servido da raiz do app (escopo /SGT/). */
    public function sw()
    {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Service-Worker-Allowed: ' . App::getBasePath() . '/');
        $js = file_get_contents(__DIR__ . '/../../public/js/sw.js');
        echo str_replace('__BASE__', App::getBasePath(), $js);
    }

    public function offline()
    {
        $b = htmlspecialchars(App::getBasePath());
        echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sem conexão</title>'
           . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui;background:#f6f7fb;color:#0f1222;text-align:center;padding:16px}'
           . 'img{width:72px;height:72px}button{margin-top:16px;padding:12px 20px;border:0;border-radius:10px;background:#1d4ed8;color:#fff;font-weight:600;font-size:15px}</style></head>'
           . '<body><main><img src="' . $b . '/public/img/logo-prumo.png" alt=""><h1>Você está sem internet</h1><p>Assim que a conexão voltar, o ' . htmlspecialchars(App::getName()) . ' abre normalmente.</p>'
           . '<button onclick="location.reload()">Tentar de novo</button></main></body></html>';
    }

    /** Chave pública VAPID (para o navegador se inscrever). */
    public function chave()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['chave' => (new PushService())->vapid()['publica']]);
    }

    public function inscrever()
    {
        CsrfMiddleware::validate();
        header('Content-Type: application/json; charset=utf-8');
        $d = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $endpoint = (string) ($d['endpoint'] ?? '');
        $p256 = (string) ($d['keys']['p256dh'] ?? '');
        $auth = (string) ($d['keys']['auth'] ?? '');
        if (!preg_match('#^https://#', $endpoint) || strlen(PushService::unb64($p256)) !== 65 || strlen(PushService::unb64($auth)) !== 16) {
            http_response_code(422); echo json_encode(['ok' => false, 'erro' => 'Inscrição inválida.']); exit;
        }
        (new PushService())->inscrever((int) $_SESSION['usuario']['id'], $endpoint, $p256, $auth, $_SERVER['HTTP_USER_AGENT'] ?? null);
        echo json_encode(['ok' => true]);
    }

    public function cancelar()
    {
        CsrfMiddleware::validate();
        header('Content-Type: application/json; charset=utf-8');
        $d = json_decode((string) file_get_contents('php://input'), true) ?: [];
        if (!empty($d['endpoint'])) (new PushService())->cancelar((string) $d['endpoint']);
        echo json_encode(['ok' => true]);
    }

    /** Manda uma notificação de teste para os aparelhos da própria pessoa. */
    public function teste()
    {
        CsrfMiddleware::validate();
        header('Content-Type: application/json; charset=utf-8');
        $n = (new PushService())->enviar((int) $_SESSION['usuario']['id'], 'Notificações ligadas!', 'É assim que os avisos da turma vão chegar no seu aparelho.', '/notificacoes');
        echo json_encode(['ok' => $n > 0, 'aparelhos' => $n]);
    }
}
