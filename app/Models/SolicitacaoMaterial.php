<?php
// app/Models/SolicitacaoMaterial.php
require_once __DIR__ . '/../Core/Model.php';

class SolicitacaoMaterial extends Model
{
    const STATUS = [
        'pendente'  => 'Aguardando análise',
        'aprovada'  => 'Falta comprar',
        'comprada'  => 'Comprada',
        'recusada'  => 'Recusada',
        'cancelada' => 'Cancelada',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'solicitacoes_materiais';
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT sm.*,
                   u.nome AS solicitante_nome, u.email AS solicitante_email,
                   ap.nome AS aprovador_nome,
                   cp.nome AS comprador_nome,
                   rp.nome AS recusador_nome,
                   m.nome AS material_nome_final
            FROM solicitacoes_materiais sm
            INNER JOIN usuarios u  ON u.id  = sm.solicitante_id
            LEFT JOIN usuarios ap  ON ap.id = sm.aprovado_por
            LEFT JOIN usuarios cp  ON cp.id = sm.comprado_por
            LEFT JOIN usuarios rp  ON rp.id = sm.recusado_por
            LEFT JOIN materiais m  ON m.id  = sm.material_id
            WHERE sm.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listarPorProjeto(int $projetoId, ?string $status = null): array
    {
        $sql = "
            SELECT sm.*,
                   u.nome AS solicitante_nome,
                   ap.nome AS aprovador_nome,
                   cp.nome AS comprador_nome,
                   m.nome AS material_nome_final
            FROM solicitacoes_materiais sm
            INNER JOIN usuarios u ON u.id = sm.solicitante_id
            LEFT JOIN usuarios ap ON ap.id = sm.aprovado_por
            LEFT JOIN usuarios cp ON cp.id = sm.comprado_por
            LEFT JOIN materiais m ON m.id = sm.material_id
            WHERE sm.projeto_id = ?
        ";
        $params = [$projetoId];

        if ($status && array_key_exists($status, self::STATUS)) {
            $sql .= " AND sm.status = ?";
            $params[] = $status;
        }

        // Pendentes e aprovadas primeiro, depois por data
        $sql .= "
            ORDER BY
                FIELD(sm.status, 'pendente', 'aprovada', 'comprada', 'recusada', 'cancelada'),
                sm.created_at DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAbertas(int $projetoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT sm.*, u.nome AS solicitante_nome
            FROM solicitacoes_materiais sm
            INNER JOIN usuarios u ON u.id = sm.solicitante_id
            WHERE sm.projeto_id = ? AND sm.status IN ('pendente', 'aprovada')
            ORDER BY FIELD(sm.status, 'pendente', 'aprovada'), sm.created_at DESC
        ");
        $stmt->execute([$projetoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarAbertas(int $projetoId): int
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM solicitacoes_materiais
            WHERE projeto_id = ? AND status IN ('pendente', 'aprovada')
        ");
        $stmt->execute([$projetoId]);
        return (int) $stmt->fetchColumn();
    }

    public function criar(array $d): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO solicitacoes_materiais
                (projeto_id, turma_id, solicitante_id, nome_material,
                 quantidade, unidade, justificativa, preco_estimado)
            VALUES
                (:projeto_id, :turma_id, :solicitante_id, :nome_material,
                 :quantidade, :unidade, :justificativa, :preco_estimado)
        ");
        $stmt->execute([
            ':projeto_id'      => $d['projeto_id'],
            ':turma_id'        => $d['turma_id'],
            ':solicitante_id'  => $d['solicitante_id'],
            ':nome_material'   => $d['nome_material'],
            ':quantidade'      => $d['quantidade'],
            ':unidade'         => $d['unidade'] ?? null,
            ':justificativa'   => $d['justificativa'] ?? null,
            ':preco_estimado'  => $d['preco_estimado'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function aprovar(int $id, int $porId): bool
    {
        return $this->conn->prepare("
            UPDATE solicitacoes_materiais
            SET status = 'aprovada', aprovado_por = ?, aprovado_em = NOW()
            WHERE id = ? AND status = 'pendente'
        ")->execute([$porId, $id]);
    }

    public function recusar(int $id, int $porId, string $motivo): bool
    {
        return $this->conn->prepare("
            UPDATE solicitacoes_materiais
            SET status = 'recusada', recusado_por = ?, recusado_em = NOW(), motivo_recusa = ?
            WHERE id = ? AND status IN ('pendente', 'aprovada')
        ")->execute([$porId, $motivo, $id]);
    }

    public function marcarComprada(int $id, int $porId, int $materialId): bool
    {
        return $this->conn->prepare("
            UPDATE solicitacoes_materiais
            SET status = 'comprada', comprado_por = ?, comprado_em = NOW(), material_id = ?
            WHERE id = ? AND status = 'aprovada'
        ")->execute([$porId, $materialId, $id]);
    }

    public function cancelar(int $id, int $porId): bool
    {
        return $this->conn->prepare("
            UPDATE solicitacoes_materiais
            SET status = 'cancelada'
            WHERE id = ? AND solicitante_id = ? AND status IN ('pendente', 'aprovada')
        ")->execute([$id, $porId]);
    }

    public function excluir(int $id): bool
    {
        return $this->conn->prepare("DELETE FROM solicitacoes_materiais WHERE id = ?")
                          ->execute([$id]);
    }
}