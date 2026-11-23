<?php
// app/Services/GoogleAuthService.php
// Login com Google (OAuth 2.0 / OpenID Connect) sem biblioteca externa: só cURL.
// Configuração no .env: GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET.
// No Google Cloud Console, o "URI de redirecionamento autorizado" deve ser: APP_URL + /auth/google/callback
require_once __DIR__ . '/../Core/App.php';

class GoogleAuthService
{
    const URL_AUTORIZAR = 'https://accounts.google.com/o/oauth2/v2/auth';
    const URL_TOKEN     = 'https://oauth2.googleapis.com/token';
    const URL_PERFIL    = 'https://openidconnect.googleapis.com/v1/userinfo';

    public static function configurado(): bool
    {
        return App::get('GOOGLE_CLIENT_ID', '') !== '' && App::get('GOOGLE_CLIENT_SECRET', '') !== '';
    }

    public static function urlRetorno(): string
    {
        return rtrim(App::getUrl(), '/') . '/auth/google/callback';
    }

    /** URL para mandar a pessoa ao Google. $state protege contra CSRF (guardado na sessão). */
    public static function urlLogin(string $state): string
    {
        return self::URL_AUTORIZAR . '?' . http_build_query([
            'client_id'     => App::get('GOOGLE_CLIENT_ID'),
            'redirect_uri'  => self::urlRetorno(),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
        ]);
    }

    /**
     * Troca o código pelo perfil. Retorna ['sub', 'email', 'nome', 'foto'] ou lança Exception com a mensagem.
     * Só aceita email verificado pelo Google.
     */
    public static function perfilPeloCodigo(string $codigo): array
    {
        [$st, $tok] = self::http(self::URL_TOKEN, [
            'code'          => $codigo,
            'client_id'     => App::get('GOOGLE_CLIENT_ID'),
            'client_secret' => App::get('GOOGLE_CLIENT_SECRET'),
            'redirect_uri'  => self::urlRetorno(),
            'grant_type'    => 'authorization_code',
        ]);
        if ($st !== 200 || empty($tok['access_token'])) {
            throw new Exception('O Google não confirmou o login (' . ($tok['error_description'] ?? $tok['error'] ?? "HTTP {$st}") . ').');
        }

        [$st, $p] = self::http(self::URL_PERFIL, null, $tok['access_token']);
        if ($st !== 200 || empty($p['sub']) || empty($p['email'])) {
            throw new Exception('Não foi possível ler seu perfil do Google.');
        }
        if (empty($p['email_verified'])) {
            throw new Exception('Seu email no Google ainda não foi verificado.');
        }
        return [
            'sub'   => (string) $p['sub'],
            'email' => strtolower((string) $p['email']),
            'nome'  => trim((string) ($p['name'] ?? '')),
            'foto'  => (string) ($p['picture'] ?? ''),
        ];
    }

    /** [status, json]. POST quando $form não é null. */
    private static function http(string $url, ?array $form, ?string $bearer = null): array
    {
        $ch = curl_init($url);
        $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_HTTPHEADER => ['Accept: application/json']];
        if ($form !== null) { $opts[CURLOPT_POST] = true; $opts[CURLOPT_POSTFIELDS] = http_build_query($form); }
        if ($bearer) $opts[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $bearer;
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $st   = $body === false ? 0 : (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$st, $body ? (json_decode($body, true) ?: []) : []];
    }
}
