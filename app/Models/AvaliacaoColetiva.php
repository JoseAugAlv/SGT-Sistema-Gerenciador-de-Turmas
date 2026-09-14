<?php
// app/Models/AvaliacaoColetiva.php
require_once __DIR__ . '/../Core/Model.php';

class AvaliacaoColetiva extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'avaliacoes_coletivas';
    }

    public function porCriterioEGrupo(int $criterioId, int $grupoId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT ac.*, u.nome AS avaliador_nome
            FROM avaliacoes_coletivas ac
            LEFT JOIN usuarios u ON u.id = ac.avaliador_id
            WHERE ac.criterio_id = ? AND ac.grupo_id = ?
            ORDER BY ac.created_at ASC
            LIMIT 1
        ");
        $stmt->execute([$criterioId, $grupoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function upsert(
        int $criterioId,
        int $grupoId,
        int $avaliadorId,
        string $conceito,
        float $valor,
        ?string $justificativa = null
    ): void {
        $existente = $this->porCriterioEGrupo($criterioId, $grupoId);

        if ($existente) {
            $this->conn->prepare("
                UPDATE avaliacoes_coletivas
                SET conceito = ?, valor_numerico = ?, justificativa = ?, avaliador_id = ?
                WHERE id = ?
            ")->execute([$conceito, $valor, $justificativa, $avaliadorId, $existente['id']]);
            return;
        }

        $this->conn->prepare("
            INSERT INTO avaliacoes_coletivas
                (criterio_id, grupo_id, avaliador_id, conceito, valor_numerico, justificativa)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$criterioId, $grupoId, $avaliadorId, $conceito, $valor, $justificativa]);
    }

    public function listarPorCriterio(int $criterioId): array
    {
        $stmt = $this->conn->prepare("
            SELECT ac.*, g.nome AS grupo_nome, u.nome AS avaliador_nome
            FROM avaliacoes_coletivas ac
            INNER JOIN grupos g ON g.id = ac.grupo_id
            LEFT JOIN usuarios u ON u.id = ac.avaliador_id
            WHERE ac.criterio_id = ?
        ");
        $stmt->execute([$criterioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorCriterio(int $criterioId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM avaliacoes_coletivas WHERE criterio_id = ?");
        $stmt->execute([$criterioId]);
        return (int) $stmt->fetchColumn();
    }
}