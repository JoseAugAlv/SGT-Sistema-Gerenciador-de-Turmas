<?php
// app/Models/AvaliacaoArquivada.php
require_once __DIR__ . '/../Core/Model.php';

class AvaliacaoArquivada extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'avaliacoes_arquivadas';
    }

    public function arquivar(
        int $criterioOriginalId,
        int $projetoId,
        array $dados,
        ?int $arquivadoPor
    ): int {
        $expira = date('Y-m-d H:i:s', strtotime('+30 days'));

        $this->conn->prepare("
            INSERT INTO avaliacoes_arquivadas
                (criterio_original_id, projeto_id, dados_json, arquivado_por, expira_em)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $criterioOriginalId,
            $projetoId,
            json_encode($dados, JSON_UNESCAPED_UNICODE),
            $arquivadoPor,
            $expira,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function listarPorProjeto(int $projetoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT aa.*, u.nome AS arquivado_por_nome
            FROM avaliacoes_arquivadas aa
            LEFT JOIN usuarios u ON u.id = aa.arquivado_por
            WHERE aa.projeto_id = ? AND aa.restaurado = 0
            ORDER BY aa.arquivado_em DESC
        ");
        $stmt->execute([$projetoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}