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
            INSERT INTO projetos (turma_id, nome, descricao, prazo, modo_avaliacao)
            VALUES (:turma_id, :nome, :descricao, :prazo, :modo_avaliacao)
        ");
        $stmt->execute([
            ':turma_id'       => $d['turma_id'],
            ':nome'           => $d['nome'],
            ':descricao'      => $d['descricao'] ?? null,
            ':prazo'          => $d['prazo'] ?? null,
            ':modo_avaliacao' => $d['modo_avaliacao'] ?? 'cronograma',
        ]);
        return (int) $this->conn->lastInsertId();
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