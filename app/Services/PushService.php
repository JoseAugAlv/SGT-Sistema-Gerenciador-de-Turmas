<?php
// app/Services/PushService.php
// Notificações no celular/computador (Web Push), sem biblioteca externa: só OpenSSL do PHP.
//   - Chaves VAPID do servidor (RFC 8292): geradas no 1º uso e guardadas em config_comercial (vapid_privada em PEM).
//   - Conteúdo criptografado no formato aes128gcm (RFC 8291) com a chave de cada aparelho.
//   - Inscrições que o serviço de push recusa de vez (404/410) são apagadas.
// Funciona em https ou em http://localhost (o navegador só deixa ativar push em "contexto seguro").
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Core/App.php';

class PushService
{
    private const SSL_CNF = ['C:/xampp/php/extras/ssl/openssl.cnf', 'C:/xampp/apache/conf/openssl.cnf'];
    private const DER_P256 = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    // ============ BASE64URL E CHAVES ============

    public static function b64(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function unb64(string $s): string
    {
        return (string) base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4));
    }

    private static function opcoesEc(): array
    {
        $o = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        foreach (self::SSL_CNF as $c) if (is_file($c)) { $o['config'] = $c; break; }
        return $o;
    }

    /** Chave pública EC (65 bytes, 0x04||x||y) a partir da chave OpenSSL. */
    private static function publicaCrua($chave): string
    {
        $d = openssl_pkey_get_details($chave);
        return "\x04" . str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);
    }

    /** Chave pública OpenSSL a partir dos 65 bytes crus. */
    private static function publicaDeCrua(string $crua)
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode(hex2bin(self::DER_P256) . $crua), 64, "\n") . "-----END PUBLIC KEY-----\n";
        return openssl_pkey_get_public($pem);
    }

    /** Chaves VAPID do servidor: ['privada' => recurso OpenSSL, 'publica' => base64url dos 65 bytes]. */
    public function vapid(): array
    {
        $st = $this->pdo->prepare("SELECT chave, valor FROM config_comercial WHERE chave IN ('vapid_privada', 'vapid_publica')");
        $st->execute();
        $cfg = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'valor', 'chave');
        if (empty($cfg['vapid_privada']) || empty($cfg['vapid_publica'])) {
            $k = openssl_pkey_new(self::opcoesEc());
            if (!$k) throw new RuntimeException('Não foi possível gerar as chaves de notificação (OpenSSL).');
            openssl_pkey_export($k, $pem, null, self::opcoesEc());
            $pub = self::b64(self::publicaCrua($k));
            $ins = $this->pdo->prepare("INSERT INTO config_comercial (chave, valor, descricao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = valor");
            $ins->execute(['vapid_privada', $pem, 'Notificações no aparelho: chave privada do servidor (não compartilhe)']);
            $ins->execute(['vapid_publica', $pub, 'Notificações no aparelho: chave pública do servidor']);
            return $this->vapid();
        }
        return ['privada' => openssl_pkey_get_private($cfg['vapid_privada']), 'publica' => $cfg['vapid_publica']];
    }

    // ============ INSCRIÇÕES ============

    public function inscrever(int $uid, string $endpoint, string $p256dh, string $auth, ?string $aparelho): void
    {
        $this->pdo->prepare("
            INSERT INTO push_inscricoes (usuario_id, endpoint, endpoint_hash, p256dh, auth, aparelho) VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id), p256dh = VALUES(p256dh), auth = VALUES(auth), aparelho = VALUES(aparelho)
        ")->execute([$uid, $endpoint, hash('sha256', $endpoint), $p256dh, $auth, $aparelho ? mb_substr($aparelho, 0, 150) : null]);
    }

    public function cancelar(string $endpoint): void
    {
        $this->pdo->prepare("DELETE FROM push_inscricoes WHERE endpoint_hash = ?")->execute([hash('sha256', $endpoint)]);
    }

    public function temInscricao(int $uid): bool
    {
        $st = $this->pdo->prepare("SELECT 1 FROM push_inscricoes WHERE usuario_id = ? LIMIT 1");
        $st->execute([$uid]);
        return (bool) $st->fetchColumn();
    }

    // ============ ENVIO ============

    /** Manda a notificação para todos os aparelhos da pessoa. Retorna quantos aceitaram. */
    public function enviar(int $uid, string $titulo, string $corpo, ?string $link = null): int
    {
        $st = $this->pdo->prepare("SELECT * FROM push_inscricoes WHERE usuario_id = ?");
        $st->execute([$uid]);
        $inscricoes = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$inscricoes) return 0;
        $payload = json_encode([
            'titulo' => mb_strimwidth($titulo, 0, 120, '…'),
            'corpo'  => mb_strimwidth($corpo, 0, 240, '…'),
            'link'   => App::getBasePath() . ($link ? '/' . ltrim($link, '/') : '/notificacoes'),
            'icone'  => App::getBasePath() . '/public/img/logo-prumo.png',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ok = 0;
        foreach ($inscricoes as $i) {
            try {
                $codigo = $this->post($i, $payload);
                if ($codigo >= 200 && $codigo < 300) {
                    $ok++;
                    $this->pdo->prepare("UPDATE push_inscricoes SET ultimo_envio = NOW() WHERE id = ?")->execute([$i['id']]);
                } elseif (in_array($codigo, [404, 410], true)) {
                    $this->pdo->prepare("DELETE FROM push_inscricoes WHERE id = ?")->execute([$i['id']]);   // aparelho desinscrito
                }
            } catch (\Throwable $e) {
                error_log('Push: ' . $e->getMessage());
            }
        }
        return $ok;
    }

    /** Criptografa (RFC 8291, aes128gcm). Retorna o corpo binário da requisição. Público para os testes. */
    public static function criptografar(string $payload, string $uaPublicaCrua, string $authSecret): string
    {
        $efemera = openssl_pkey_new(self::opcoesEc());
        $asPublica = self::publicaCrua($efemera);
        $segredo = openssl_pkey_derive(self::publicaDeCrua($uaPublicaCrua), $efemera, 32);
        if ($segredo === false) throw new RuntimeException('ECDH falhou');
        $ikm = hash_hkdf('sha256', $segredo, 32, "WebPush: info\0" . $uaPublicaCrua . $asPublica, $authSecret);
        $sal = random_bytes(16);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $sal);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $sal);
        $cifrado = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
        return $sal . pack('N', 4096) . chr(65) . $asPublica . $cifrado . $tag;
    }

    /** JWT VAPID (ES256) para a origem do serviço de push. */
    private function jwt(string $endpoint, $privada): string
    {
        $p = parse_url($endpoint);
        $contato = (string) App::get('MAIL_USER', '');
        $claims = ['aud' => $p['scheme'] . '://' . $p['host'], 'exp' => time() + 12 * 3600,
                   'sub' => filter_var($contato, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $contato : 'mailto:contato@prumo.app'];
        $dados = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256'])) . '.' . self::b64(json_encode($claims, JSON_UNESCAPED_SLASHES));
        openssl_sign($dados, $der, $privada, OPENSSL_ALGO_SHA256);
        return $dados . '.' . self::b64(self::derParaCru($der));
    }

    /** Assinatura ECDSA em DER -> 64 bytes R||S (formato do JWT). */
    public static function derParaCru(string $der): string
    {
        $pos = 2;
        if (ord($der[1]) & 0x80) $pos += ord($der[1]) & 0x7f;
        $partes = [];
        for ($i = 0; $i < 2; $i++) {
            $pos++;                                   // 0x02
            $len = ord($der[$pos++]);
            $partes[] = str_pad(ltrim(substr($der, $pos, $len), "\0"), 32, "\0", STR_PAD_LEFT);
            $pos += $len;
        }
        return $partes[0] . $partes[1];
    }

    private function post(array $insc, string $payload): int
    {
        $v = $this->vapid();
        $corpo = self::criptografar($payload, self::unb64($insc['p256dh']), self::unb64($insc['auth']));
        $ch = curl_init($insc['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $corpo, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/octet-stream', 'Content-Encoding: aes128gcm', 'TTL: 86400', 'Urgency: normal',
                'Authorization: vapid t=' . $this->jwt($insc['endpoint'], $v['privada']) . ', k=' . $v['publica'],
            ],
        ]);
        curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $codigo;
    }
}
