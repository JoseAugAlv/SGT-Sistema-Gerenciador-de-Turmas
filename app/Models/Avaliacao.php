<?php
// app/Models/Avaliacao.php
require_once __DIR__ . '/../Core/Model.php';

class Avaliacao extends Model
{
    // Mapeamento criterios.tipo_avaliacao -> avaliacoes.tipo
    const MAPA_TIPO = [
        'diretor'       => 'diretor',
        'representante' => 'representante',
        'pares'         => 'par',
        'autoavaliacao' => 'auto',
        'misto'         => 'misto',
        // 'coletiva' NAO usa esta tabela
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'avaliacoes';
    }

    public function porChave(int $criterioId, int $alunoId, int $avaliadorId, string $tipo): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM avaliacoes
            WHERE criterio_id = ? AND aluno_id = ? AND avaliador_id = ? AND tipo = ?
            LIMIT 1
        ");
        $stmt->execute([$criterioId, $alunoId, $avaliadorId, $tipo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Insere ou atualiza uma avaliação.
     */
    public function upsert(
        int $criterioId,
        int $alunoId,
        int $avaliadorId,
        string $tipo,
        string $conceito,
        float $valor,
        ?string $justificativa = null,
        ?int $editadoPor = null
    ): void {
        $existente = $this->porChave($criterioId, $alunoId, $avaliadorId, $tipo);

        if ($existente) {
            $this->conn->prepare("
                UPDATE avaliacoes
                SET conceito = ?, valor_numerico = ?, justificativa = ?,
                    editado_por = ?, editado_em = NOW()
                WHERE id = ?
            ")->execute([$conceito, $valor, $justificativa, $editadoPor, $existente['id']]);
            return;
        }

        $this->conn->prepare("
            INSERT INTO avaliacoes
                (criterio_id, aluno_id, avaliador_id, tipo, conceito, valor_numerico, justificativa)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$criterioId, $alunoId, $avaliadorId, $tipo, $conceito, $valor, $justificativa]);
    }

    public function deletar(
        int $criterioId,
        int $alunoId,
        int $avaliadorId,
        string $tipo
    ): void {
        $this->conn->prepare("
            DELETE FROM avaliacoes
            WHERE criterio_id = ? AND aluno_id = ? AND avaliador_id = ? AND tipo = ?
        ")->execute([$criterioId, $alunoId, $avaliadorId, $tipo]);
    }

    public function deletarDoAvaliadorNoProjeto(int $projetoId, int $avaliadorId): void
    {
        $this->conn->prepare("
            DELETE a FROM avaliacoes a
            INNER JOIN criterios c ON c.id = a.criterio_id
            WHERE c.projeto_id = ? AND a.avaliador_id = ?
        ")->execute([$projetoId, $avaliadorId]);
    }

    public function listarPorCriterioEAluno(int $criterioId, int $alunoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, u.nome AS avaliador_nome, u.email AS avaliador_email
            FROM avaliacoes a
            LEFT JOIN usuarios u ON u.id = a.avaliador_id
            WHERE a.criterio_id = ? AND a.aluno_id = ?
            ORDER BY a.created_at ASC
        ");
        $stmt->execute([$criterioId, $alunoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarDoAvaliador(int $criterioId, int $avaliadorId): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM avaliacoes
            WHERE criterio_id = ? AND avaliador_id = ?
        ");
        $stmt->execute([$criterioId, $avaliadorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorCriterio(int $criterioId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM avaliacoes WHERE criterio_id = ?");
        $stmt->execute([$criterioId]);
        return (int) $stmt->fetchColumn();
    }
}