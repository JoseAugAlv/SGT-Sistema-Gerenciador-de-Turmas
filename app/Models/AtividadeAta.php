<?php
// app/Models/AtividadeAta.php
require_once __DIR__ . '/../Core/Model.php';

class AtividadeAta extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'atividades_ata';
    }

    public function listarPorAta(int $ataId): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM atividades_ata WHERE ata_id = ? ORDER BY id ASC
        ");
        $stmt->execute([$ataId]);
        $atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($atividades as &$a) {
            $a['participantes'] = $this->participantes((int) $a['id']);
        }
        return $atividades;
    }

    public function participantes(int $atividadeId): array
    {
        $stmt = $this->conn->prepare("
            SELECT pa.*, u.nome AS usuario_nome
            FROM participantes_atividade pa
            INNER JOIN usuarios u ON u.id = pa.usuario_id
            WHERE pa.atividade_id = ?
        ");
        $stmt->execute([$atividadeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar(int $ataId, string $nome, ?string $descricao, int $autorId): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO atividades_ata (ata_id, nome, descricao, autor_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$ataId, $nome, $descricao, $autorId]);
        return (int) $this->conn->lastInsertId();
    }

    public function adicionarParticipante(int $atividadeId, int $usuarioId, string $tipo): void
    {
        $this->conn->prepare("
            INSERT INTO participantes_atividade (atividade_id, usuario_id, tipo_participacao)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE tipo_participacao = VALUES(tipo_participacao)
        ")->execute([$atividadeId, $usuarioId, $tipo]);
    }

    public function excluir(int $id): bool
    {
        return $this->conn->prepare("DELETE FROM atividades_ata WHERE id = ?")->execute([$id]);
    }
}