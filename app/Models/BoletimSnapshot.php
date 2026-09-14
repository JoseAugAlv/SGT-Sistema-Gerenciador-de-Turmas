<?php
// app/Models/BoletimSnapshot.php
require_once __DIR__ . '/../Core/Model.php';

class BoletimSnapshot extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'boletins_snapshot';
    }

    public function porProjetoEAluno(int $projetoId, int $alunoId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM boletins_snapshot
            WHERE projeto_id = ? AND aluno_id = ?
            LIMIT 1
        ");
        $stmt->execute([$projetoId, $alunoId]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$reg) return null;

        $reg['dados'] = json_decode($reg['dados_json'], true) ?: [];
        return $reg;
    }

    public function listarPorProjeto(int $projetoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT bs.*, u.nome AS aluno_nome, u.email AS aluno_email
            FROM boletins_snapshot bs
            INNER JOIN usuarios u ON u.id = bs.aluno_id
            WHERE bs.projeto_id = ?
            ORDER BY u.nome ASC
        ");
        $stmt->execute([$projetoId]);
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($linhas as &$l) {
            $l['dados'] = json_decode($l['dados_json'], true) ?: [];
        }
        return $linhas;
    }

    /**
     * Insere ou atualiza o snapshot (upsert).
     */
    public function gravar(
        int $projetoId,
        int $alunoId,
        array $dados,
        ?float $mediaGeral,
        ?string $conceitoGeral
    ): void {
        $this->conn->prepare("
            INSERT INTO boletins_snapshot
                (projeto_id, aluno_id, dados_json, media_geral, conceito_geral)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                dados_json = VALUES(dados_json),
                media_geral = VALUES(media_geral),
                conceito_geral = VALUES(conceito_geral)
        ")->execute([
            $projetoId,
            $alunoId,
            json_encode($dados, JSON_UNESCAPED_UNICODE),
            $mediaGeral,
            $conceitoGeral,
        ]);
    }

    public function existeParaProjeto(int $projetoId): bool
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM boletins_snapshot WHERE projeto_id = ? LIMIT 1");
        $stmt->execute([$projetoId]);
        return (bool) $stmt->fetchColumn();
    }
}