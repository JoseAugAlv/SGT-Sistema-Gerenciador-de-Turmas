<?php
// app/Models/DescontoNota.php
// Desconto na nota final de quem não preencheu um critério obrigatório.
// Os pontos (escala 0–100) são subtraídos da média ponderada do projeto.
require_once __DIR__ . '/../Core/Model.php';

class DescontoNota extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'descontos_nota';
    }

    /** [aluno_id => linha] dos descontos de um critério. */
    public function doCriterio(int $criterioId): array
    {
        $stmt = $this->conn->prepare("
            SELECT d.*, u.nome AS aluno_nome
            FROM descontos_nota d
            INNER JOIN usuarios u ON u.id = d.aluno_id
            WHERE d.criterio_id = ?
            ORDER BY u.nome
        ");
        $stmt->execute([$criterioId]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $out[(int) $r['aluno_id']] = $r;
        return $out;
    }

    /** Descontos do aluno em um projeto (para o boletim). */
    public function doAlunoNoProjeto(int $projetoId, int $alunoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT d.pontos, d.motivo, c.nome AS criterio_nome
            FROM descontos_nota d
            INNER JOIN criterios c ON c.id = d.criterio_id
            WHERE c.projeto_id = ? AND d.aluno_id = ?
            ORDER BY d.created_at
        ");
        $stmt->execute([$projetoId, $alunoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function aplicar(int $criterioId, int $alunoId, float $pontos, string $motivo, int $porUsuario): void
    {
        $this->conn->prepare("
            INSERT INTO descontos_nota (criterio_id, aluno_id, pontos, motivo, aplicado_por)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE pontos = VALUES(pontos), motivo = VALUES(motivo),
                                    aplicado_por = VALUES(aplicado_por), created_at = NOW()
        ")->execute([$criterioId, $alunoId, $pontos, $motivo, $porUsuario]);
    }

    public function remover(int $criterioId, int $alunoId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM descontos_nota WHERE criterio_id = ? AND aluno_id = ?");
        $stmt->execute([$criterioId, $alunoId]);
        return $stmt->rowCount() > 0;
    }
}
