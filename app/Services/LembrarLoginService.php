<?php
// app/Services/LembrarLoginService.php
// "Manter conectado" por 30 dias. O cookie leva seletor:validador; o banco guarda só o hash do validador.
// A cada uso o validador é trocado (um cookie roubado e já usado deixa de valer). Sair da conta ou trocar
// a senha apaga os tokens.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';

class LembrarLoginService
{
    const COOKIE = 'PRUMO_LEMBRAR';
    const DIAS   = 30;

    /** Cria o token deste aparelho e grava o cookie. */
    public static function lembrar(int $uid): void
    {
        $seletor   = bin2hex(random_bytes(12));
        $validador = bin2hex(random_bytes(32));
        Database::getConnection()->prepare("
            INSERT INTO login_lembrar (usuario_id, seletor, validador_hash, user_agent, expira_em)
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL " . self::DIAS . " DAY))
        ")->execute([$uid, $seletor, hash('sha256', $validador), mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
        self::gravarCookie($seletor . ':' . $validador, time() + self::DIAS * 86400);
    }

    /**
     * Sem sessão, mas com o cookie: devolve o usuário (ativo) e troca o validador. Token inválido some.
     * Quem chama monta a sessão.
     */
    public static function usuarioDoCookie(): ?array
    {
        $c = (string) ($_COOKIE[self::COOKIE] ?? '');
        if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $c, $m)) { if ($c !== '') self::apagarCookie(); return null; }
        $pdo = Database::getConnection();
        $st = $pdo->prepare("SELECT * FROM login_lembrar WHERE seletor = ? AND expira_em > NOW()");
        $st->execute([$m[1]]);
        $t = $st->fetch(PDO::FETCH_ASSOC);
        if (!$t || !hash_equals($t['validador_hash'], hash('sha256', $m[2]))) {
            // seletor certo com validador errado = cookie copiado e já usado: derruba todos os tokens dessa pessoa
            if ($t) $pdo->prepare("DELETE FROM login_lembrar WHERE usuario_id = ?")->execute([(int) $t['usuario_id']]);
            self::apagarCookie();
            return null;
        }
        $st = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND ativo = 1");
        $st->execute([(int) $t['usuario_id']]);
        $user = $st->fetch(PDO::FETCH_ASSOC);
        if (!$user) { self::esquecer(); return null; }

        // troca o validador e renova o prazo
        $novo = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE login_lembrar SET validador_hash = ?, expira_em = DATE_ADD(NOW(), INTERVAL " . self::DIAS . " DAY) WHERE id = ?")
            ->execute([hash('sha256', $novo), (int) $t['id']]);
        self::gravarCookie($m[1] . ':' . $novo, time() + self::DIAS * 86400);
        return $user;
    }

    /** Sair da conta neste aparelho. */
    public static function esquecer(): void
    {
        $c = (string) ($_COOKIE[self::COOKIE] ?? '');
        if (preg_match('/^([a-f0-9]{24}):/', $c, $m)) {
            Database::getConnection()->prepare("DELETE FROM login_lembrar WHERE seletor = ?")->execute([$m[1]]);
        }
        self::apagarCookie();
    }

    /** Troca de senha: desconecta todos os aparelhos. */
    public static function esquecerTodos(int $uid): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare("DELETE FROM login_lembrar WHERE usuario_id = ?")->execute([$uid]);
        $pdo->exec("DELETE FROM login_lembrar WHERE expira_em < NOW()");   // limpeza dos vencidos
    }

    public static function temCookie(): bool
    {
        return !empty($_COOKIE[self::COOKIE]);
    }

    private static function gravarCookie(string $valor, int $expira): void
    {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie(self::COOKIE, $valor, [
            'expires' => $expira, 'path' => App::getBasePath() ?: '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE] = $valor;
    }

    private static function apagarCookie(): void
    {
        self::gravarCookie('', time() - 3600);
        unset($_COOKIE[self::COOKIE]);
    }
}
