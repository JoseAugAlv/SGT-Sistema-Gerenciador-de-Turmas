<?php
// app/Models/Notificacao.php
require_once __DIR__ . '/../Core/Model.php';

class Notificacao extends Model
{
    const TIPOS = [
        'alerta'    => 'Alerta',
        'prazo'     => 'Prazo',
        'avaliacao' => 'Avaliação',
        'papel'     => 'Papel',
        'ata'       => 'Ata',
        'material'  => 'Material',
        'lgpd'      => 'LGPD',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'notificacoes';
    }

    public function criar(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $mensagem,
        ?string $link = null,
        ?int $referenciaId = null
    ): int {
        $stmt = $this->conn->prepare("
            INSERT INTO notificacoes
                (usuario_id, tipo, referencia_id, titulo, mensagem, link)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$usuarioId, $tipo, $referenciaId, $titulo, $mensagem, $link]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Cria a mesma notificação para vários usuários.
     */
    public function criarParaVarios(array $usuarioIds, string $tipo, string $titulo, string $mensagem, ?string $link = null): int
    {
        $total = 0;
        foreach ($usuarioIds as $uid) {
            $uid = (int) $uid;
            if ($uid > 0) {
                $this->criar($uid, $tipo, $titulo, $mensagem, $link);
                $total++;
            }
        }
        return $total;
    }

    public function listarPorUsuario(int $usuarioId, ?string $filtro = null, int $limit = 50): array
    {
        $sql = "SELECT * FROM notificacoes WHERE usuario_id = ?";
        $params = [$usuarioId];

        if ($filtro === 'nao_lidas') {
            $sql .= " AND lida = 0";
        }

        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarNaoLidas(int $usuarioId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0");
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    public function ultimas(int $usuarioId, int $limit = 10): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM notificacoes
            WHERE usuario_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarLida(int $id, int $usuarioId): bool
    {
        return $this->conn->prepare("
            UPDATE notificacoes SET lida = 1, lida_em = NOW()
            WHERE id = ? AND usuario_id = ?
        ")->execute([$id, $usuarioId]);
    }

    public function marcarTodasLidas(int $usuarioId): int
    {
        $stmt = $this->conn->prepare("
            UPDATE notificacoes SET lida = 1, lida_em = NOW()
            WHERE usuario_id = ? AND lida = 0
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->rowCount();
    }
}