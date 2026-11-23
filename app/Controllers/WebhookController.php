<?php
// app/Controllers/WebhookController.php
// Aviso do Mercado Pago (POST /webhooks/mercadopago). Não confia no conteúdo do aviso: com a assinatura conferida
// (se MP_WEBHOOK_SECRET estiver no .env), consulta o pagamento na API do Mercado Pago com o nosso token e só dá
// baixa se ele estiver aprovado e o valor bater. Responde 200 rápido (o Mercado Pago repete se não receber).
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Services/MercadoPagoService.php';

class WebhookController
{
    public function mercadopago()
    {
        $corpo = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $tipo = (string) ($corpo['type'] ?? $_GET['type'] ?? $_GET['topic'] ?? '');
        $id = (string) ($corpo['data']['id'] ?? $_GET['data_id'] ?? $_GET['data.id'] ?? $_GET['id'] ?? '');
        header('Content-Type: application/json');

        if ($tipo !== 'payment' || !preg_match('/^\w{1,40}$/', $id)) { echo '{"ok":true,"ignorado":true}'; return; }
        if ((string) App::get('MP_WEBHOOK_SECRET', '') !== ''
            && !MercadoPagoService::assinaturaValida((string) ($_SERVER['HTTP_X_SIGNATURE'] ?? ''), (string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? ''), $id)) {
            http_response_code(401);
            echo '{"ok":false}';
            return;
        }
        if ((string) App::get('MP_ACCESS_TOKEN', '') === '') { echo '{"ok":true,"sem_token":true}'; return; }
        $status = (new MercadoPagoService())->sincronizar($id);
        echo json_encode(['ok' => true, 'status' => $status]);
    }
}
