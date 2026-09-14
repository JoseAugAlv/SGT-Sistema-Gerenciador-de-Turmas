<?php
// app/Models/ConfiguracaoConceito.php
require_once __DIR__ . '/../Core/Model.php';

class ConfiguracaoConceito extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'configuracoes_conceito';
    }

    public function porProjeto(int $projetoId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM configuracoes_conceito WHERE projeto_id = ? LIMIT 1");
        $stmt->execute([$projetoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Cria com defaults caso ainda não exista.
     */
    public function garantir(int $projetoId): array
    {
        $cfg = $this->porProjeto($projetoId);
        if ($cfg) return $cfg;

        $this->conn->prepare("
            INSERT INTO configuracoes_conceito (projeto_id) VALUES (?)
        ")->execute([$projetoId]);

        return $this->porProjeto($projetoId);
    }

    public function atualizar(int $projetoId, array $d): bool
    {
        return $this->conn->prepare("
            UPDATE configuracoes_conceito
            SET limite_i_max = ?, limite_r_min = ?, limite_r_max = ?,
                limite_b_min = ?, limite_b_max = ?, limite_mb_min = ?,
                visibilidade_diretor = ?, visibilidade_aluno = ?
            WHERE projeto_id = ?
        ")->execute([
            (int) $d['limite_i_max'],
            (int) $d['limite_r_min'], (int) $d['limite_r_max'],
            (int) $d['limite_b_min'], (int) $d['limite_b_max'],
            (int) $d['limite_mb_min'],
            $d['visibilidade_diretor'], $d['visibilidade_aluno'],
            $projetoId,
        ]);
    }
}