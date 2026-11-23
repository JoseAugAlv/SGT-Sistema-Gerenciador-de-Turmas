<?php
// app/Models/Projeto.php
require_once __DIR__ . '/../Core/Model.php';

class Projeto extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'projetos';
    }

    public function listarPorTurma(int $turmaId): array
    {
        $stmt = $this->conn->prepare("
            SELECT p.*,
                   u.nome AS encerrado_por_nome,
                   (SELECT COUNT(*) FROM projeto_etapas e WHERE e.projeto_id = p.id) AS total_etapas
            FROM projetos p
            LEFT JOIN usuarios u ON u.id = p.encerrado_por
            WHERE p.turma_id = ?
            ORDER BY p.encerrado ASC, p.created_at DESC
        ");
        $stmt->execute([$turmaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT p.*, t.nome AS turma_nome, t.codigo_acesso AS turma_codigo,
                   u.nome AS encerrado_por_nome
            FROM projetos p
            INNER JOIN turmas t ON t.id = p.turma_id
            LEFT JOIN usuarios u ON u.id = p.encerrado_por
            WHERE p.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO projetos (turma_id, nome, descricao, prazo, modo_avaliacao,
                                  alunos_pedem_grupo, alunos_pedem_diretor, diretores_por_grupo, avaliacao_reps)
            VALUES (:turma_id, :nome, :descricao, :prazo, :modo_avaliacao, :pede_grupo, :pede_diretor, :diretores, :aval_reps)
        ");
        $stmt->execute([
            ':turma_id'       => $d['turma_id'],
            ':nome'           => $d['nome'],
            ':descricao'      => $d['descricao'] ?? null,
            ':prazo'          => $d['prazo'] ?? null,
            ':modo_avaliacao' => $d['modo_avaliacao'] ?? 'cronograma',
            ':pede_grupo'     => (int) ($d['alunos_pedem_grupo'] ?? 0),
            ':pede_diretor'   => (int) ($d['alunos_pedem_diretor'] ?? 0),
            ':diretores'      => (int) ($d['diretores_por_grupo'] ?? 1),
            ':aval_reps'      => ($d['avaliacao_reps'] ?? 'compartilhada') === 'media' ? 'media' : 'compartilhada',
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /** Como o representante e o vice avaliam: 'compartilhada' (mesma nota) ou 'media'. */
    public function atualizarAvaliacaoReps(int $id, string $modo): bool
    {
        return $this->conn->prepare("UPDATE projetos SET avaliacao_reps = ? WHERE id = ?")
                          ->execute([$modo === 'media' ? 'media' : 'compartilhada', $id]);
    }

    /** Regras dos grupos: alunos pedem para entrar / para ser diretor e quantos diretores por grupo. */
    public function atualizarRegrasGrupos(int $id, array $d): bool
    {
        return $this->conn->prepare("
            UPDATE projetos SET alunos_pedem_grupo = ?, alunos_pedem_diretor = ?, diretores_por_grupo = ? WHERE id = ?
        ")->execute([(int) $d['alunos_pedem_grupo'], (int) $d['alunos_pedem_diretor'], (int) $d['diretores_por_grupo'], $id]);
    }

    public function atualizar(int $id, array $d): bool
    {
        return $this->conn->prepare("
            UPDATE projetos SET nome = ?, descricao = ?, prazo = ?, modo_avaliacao = ?
            WHERE id = ?
        ")->execute([
            $d['nome'], $d['descricao'] ?? null, $d['prazo'] ?? null,
            $d['modo_avaliacao'], $id,
        ]);
    }

    public function encerrar(int $id, int $porUsuarioId): bool
    {
        return $this->conn->prepare("
            UPDATE projetos SET encerrado = 1, encerrado_em = NOW(), encerrado_por = ?
            WHERE id = ? AND encerrado = 0
        ")->execute([$porUsuarioId, $id]);
    }
}