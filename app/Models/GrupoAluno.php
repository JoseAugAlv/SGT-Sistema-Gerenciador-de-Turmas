<?php
// app/Models/GrupoAluno.php
require_once __DIR__ . '/../Core/Model.php';

class GrupoAluno extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'grupo_alunos';
    }

    public function listarAtivos(int $grupoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT ga.id, ga.usuario_id, ga.entrou_em, u.nome, u.email
            FROM grupo_alunos ga
            INNER JOIN usuarios u ON u.id = ga.usuario_id
            WHERE ga.grupo_id = ? AND ga.saiu_em IS NULL
            ORDER BY u.nome ASC
        ");
        $stmt->execute([$grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function estaAtivo(int $grupoId, int $usuarioId): bool
    {
        $stmt = $this->conn->prepare("
            SELECT 1 FROM grupo_alunos
            WHERE grupo_id = ? AND usuario_id = ? AND saiu_em IS NULL LIMIT 1
        ");
        $stmt->execute([$grupoId, $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    public function adicionar(int $grupoId, int $usuarioId): int
    {
        $stmt = $this->conn->prepare("
            SELECT id FROM grupo_alunos WHERE grupo_id = ? AND usuario_id = ? LIMIT 1
        ");
        $stmt->execute([$grupoId, $usuarioId]);
        $id = $stmt->fetchColumn();

        if ($id) {
            $this->conn->prepare("
                UPDATE grupo_alunos SET saiu_em = NULL, entrou_em = NOW() WHERE id = ?
            ")->execute([$id]);
            return (int) $id;
        }

        $this->conn->prepare("
            INSERT INTO grupo_alunos (grupo_id, usuario_id) VALUES (?, ?)
        ")->execute([$grupoId, $usuarioId]);
        return (int) $this->conn->lastInsertId();
    }

    public function remover(int $grupoId, int $usuarioId): bool
    {
        return $this->conn->prepare("
            UPDATE grupo_alunos SET saiu_em = NOW()
            WHERE grupo_id = ? AND usuario_id = ? AND saiu_em IS NULL
        ")->execute([$grupoId, $usuarioId]);
    }

    public function candidatosNaTurma(int $grupoId, int $turmaId): array
    {
        $stmt = $this->conn->prepare("
            SELECT u.id, u.nome, u.email
            FROM turma_usuarios tu
            INNER JOIN usuarios u ON u.id = tu.usuario_id
            WHERE tu.turma_id = ? AND tu.ativo = 1
              AND NOT EXISTS (
                  SELECT 1 FROM grupo_alunos ga
                  WHERE ga.grupo_id = ? AND ga.usuario_id = u.id AND ga.saiu_em IS NULL
              )
            ORDER BY u.nome ASC
        ");
        $stmt->execute([$turmaId, $grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}