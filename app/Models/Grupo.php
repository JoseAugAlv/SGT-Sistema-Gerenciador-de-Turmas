<?php
// app/Models/Grupo.php
require_once __DIR__ . '/../Core/Model.php';

class Grupo extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'grupos';
    }

    public function listarPorProjeto(int $projetoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT g.*,
                   (SELECT COUNT(*) FROM grupo_alunos ga WHERE ga.grupo_id = g.id AND ga.saiu_em IS NULL) AS total_membros,
                   (SELECT COUNT(*) FROM grupo_diretores gd WHERE gd.grupo_id = g.id AND gd.ativo = 1) AS total_diretores
            FROM grupos g
            WHERE g.projeto_id = ?
            ORDER BY g.nome ASC
        ");
        $stmt->execute([$projetoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT g.*, p.nome AS projeto_nome, p.turma_id,
                   t.nome AS turma_nome, p.encerrado AS projeto_encerrado
            FROM grupos g
            INNER JOIN projetos p ON p.id = g.projeto_id
            INNER JOIN turmas t ON t.id = p.turma_id
            WHERE g.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO grupos (projeto_id, nome, modo_avaliacao_grupo, modo_avaliacao_por)
            VALUES (:projeto_id, :nome, :modo_grupo, :modo_por)
        ");
        $stmt->execute([
            ':projeto_id' => $d['projeto_id'],
            ':nome'       => $d['nome'],
            ':modo_grupo' => $d['modo_avaliacao_grupo'] ?? 'individual',
            ':modo_por'   => $d['modo_avaliacao_por']   ?? 'diretor',
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        return $this->conn->prepare("
            UPDATE grupos SET nome = ?, modo_avaliacao_grupo = ?, modo_avaliacao_por = ?
            WHERE id = ?
        ")->execute([
            $d['nome'],
            $d['modo_avaliacao_grupo'],
            $d['modo_avaliacao_por'],
            $id,
        ]);
    }

    public function excluir(int $id): bool
    {
        return $this->conn->prepare("DELETE FROM grupos WHERE id = ?")->execute([$id]);
    }
}