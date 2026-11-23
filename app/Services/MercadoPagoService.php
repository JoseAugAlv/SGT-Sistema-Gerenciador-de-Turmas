<?php
// app/Services/MercadoPagoService.php
// Pix real pelo Mercado Pago (API de pagamentos). Liga com config modo_pagamento = 'mercadopago' (Comercial)
// e estas linhas no .env:
//   MP_ACCESS_TOKEN=APP_USR-...      (Mercado Pago > Suas integrações > Credenciais de produção; para testar, as de teste)
//   MP_WEBHOOK_SECRET=...            (Suas integrações > Webhooks > assinatura secreta; URL: APP_URL/webhooks/mercadopago)
//   MP_API_URL=https://api.mercadopago.com   (opcional; só muda em testes)
// Fluxo: a tela do Pix cria o pagamento (QR + copia e cola). A confirmação chega pelo webhook (assinatura conferida)
// e, enquanto a tela está aberta, também pela consulta automática (útil em localhost, onde o webhook não chega).
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/PrecoService.php';
require_once __DIR__ . '/PagamentoService.php';

class MercadoPagoService
{
    private string $token;
    private string $api;

    public function __construct()
    {
        $this->token = (string) App::get('MP_ACCESS_TOKEN', '');
        $this->api = rtrim((string) App::get('MP_API_URL', 'https://api.mercadopago.com'), '/');
    }

    public static function ativo(): bool
    {
        return PrecoService::config('modo_pagamento', 'teste') === 'mercadopago' && (string) App::get('MP_ACCESS_TOKEN', '') !== '';
    }

    /**
     * Garante um Pix válido para a parcela (cria no Mercado Pago se não houver ou se venceu). Retorna a parcela atualizada.
     * $pagador: ['email', 'nome', 'cpf'].
     */
    public function pixDaParcela(array $pag, array $assin, array $turma, array $pagador): array
    {
        if ($pag['status'] !== 'pendente') return $pag;
        if (!empty($pag['mp_id']) && !empty($pag['pix_expira']) && strtotime($pag['pix_expira']) > time() + 60) return $pag;

        $partes = preg_split('/\s+/', trim($pagador['nome'] ?? ''));
        $corpo = [
            'transaction_amount' => round((float) $pag['valor'], 2),
            'description'        => App::getName() . ' · plano ' . $assin['plano_nome'] . ' · ' . $turma['nome'] . ' · parcela ' . $pag['numero'],
            'payment_method_id'  => 'pix',
            'external_reference' => 'pagamento-' . (int) $pag['id'],
            'date_of_expiration' => date('Y-m-d\TH:i:s.000P', strtotime('+1 day')),
            'payer' => [
                'email'      => $pagador['email'],
                'first_name' => $partes[0] ?? '',
                'last_name'  => count($partes) > 1 ? implode(' ', array_slice($partes, 1)) : '',
                'identification' => ['type' => 'CPF', 'number' => preg_replace('/\D/', '', (string) ($pagador['cpf'] ?? ''))],
            ],
        ];
        $url = rtrim((string) App::get('APP_URL', ''), '/');
        if (preg_match('#^https://#', $url)) $corpo['notification_url'] = $url . '/webhooks/mercadopago';   // o MP só chama https

        [$codigo, $r] = $this->req('POST', '/v1/payments', $corpo, 'prumo-pag-' . $pag['id'] . '-' . date('YmdH'));
        if ($codigo >= 300 || empty($r['id'])) {
            error_log('Mercado Pago criar Pix (' . $codigo . '): ' . json_encode($r));
            throw new RuntimeException('Não foi possível gerar o Pix agora. Tente de novo em instantes.');
        }
        $td = $r['point_of_interaction']['transaction_data'] ?? [];
        $exp = !empty($r['date_of_expiration']) ? date('Y-m-d H:i:s', strtotime($r['date_of_expiration'])) : date('Y-m-d H:i:s', strtotime('+1 day'));
        Database::getConnection()->prepare("UPDATE pagamentos SET mp_id = ?, pix_copia = ?, pix_qr = ?, pix_expira = ? WHERE id = ?")
            ->execute([(string) $r['id'], $td['qr_code'] ?? null, $td['qr_code_base64'] ?? null, $exp, $pag['id']]);
        if (($r['status'] ?? '') === 'approved') PagamentoService::confirmar((int) $pag['id'], 'pix');
        return array_merge($pag, ['mp_id' => (string) $r['id'], 'pix_copia' => $td['qr_code'] ?? null, 'pix_qr' => $td['qr_code_base64'] ?? null, 'pix_expira' => $exp]);
    }

    /** Consulta o pagamento no Mercado Pago e confirma a parcela se foi aprovado. Retorna o status do MP (ou null). */
    public function sincronizar(string $mpId): ?string
    {
        [$codigo, $r] = $this->req('GET', '/v1/payments/' . rawurlencode($mpId));
        if ($codigo >= 300 || empty($r['status'])) return null;
        if ($r['status'] === 'approved' && preg_match('/^pagamento-(\d+)$/', (string) ($r['external_reference'] ?? ''), $m)) {
            // confere o valor antes de dar baixa
            $st = Database::getConnection()->prepare("SELECT valor, mp_id FROM pagamentos WHERE id = ?");
            $st->execute([(int) $m[1]]);
            $p = $st->fetch(PDO::FETCH_ASSOC);
            if ($p && abs((float) $p['valor'] - (float) ($r['transaction_amount'] ?? 0)) < 0.01) PagamentoService::confirmar((int) $m[1], 'pix');
            else error_log('Mercado Pago: valor ou referência não conferem no pagamento ' . $mpId);
        }
        return (string) $r['status'];
    }

    /**
     * Confere a assinatura do webhook (cabeçalho x-signature: "ts=...,v1=...").
     * Modelo: "id:{data.id};request-id:{x-request-id};ts:{ts};" com HMAC-SHA256 da assinatura secreta.
     */
    public static function assinaturaValida(string $xSignature, string $xRequestId, string $dataId): bool
    {
        $segredo = (string) App::get('MP_WEBHOOK_SECRET', '');
        if ($segredo === '' || $xSignature === '') return false;
        $p = [];
        foreach (explode(',', $xSignature) as $par) { [$k, $v] = array_pad(explode('=', trim($par), 2), 2, ''); $p[$k] = $v; }
        if (empty($p['ts']) || empty($p['v1'])) return false;
        $modelo = 'id:' . strtolower($dataId) . ';' . ($xRequestId !== '' ? 'request-id:' . $xRequestId . ';' : '') . 'ts:' . $p['ts'] . ';';
        return hash_equals(hash_hmac('sha256', $modelo, $segredo), $p['v1']);
    }

    private function req(string $metodo, string $caminho, ?array $corpo = null, ?string $idempotencia = null): array
    {
        $ch = curl_init($this->api . $caminho);
        $cab = ['Authorization: Bearer ' . $this->token, 'Content-Type: application/json'];
        if ($idempotencia) $cab[] = 'X-Idempotency-Key: ' . $idempotencia;
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => $cab]);
        if ($corpo !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corpo, JSON_UNESCAPED_UNICODE));
        $txt = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$codigo, json_decode((string) $txt, true) ?: []];
    }
}
