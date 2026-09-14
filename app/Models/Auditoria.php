<?php
// app/Models/Auditoria.php
require_once __DIR__ . '/../Core/Model.php';

class Auditoria extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'auditoria_log';
    }

    public function registrar(string $acao, ?string $tabela = null, ?int $registroId = null, $antes = null, $depois = null): void
    {
        $this->conn->prepare("
            INSERT INTO auditoria_log
                (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $_SESSION['usuario']['id'] ?? null,
            $acao,
            $tabela,
            $registroId,
            $antes  !== null ? json_encode($antes, JSON_UNESCAPED_UNICODE)  : null,
            $depois !== null ? json_encode($depois, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }
}