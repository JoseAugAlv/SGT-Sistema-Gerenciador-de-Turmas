<?php
// app/Models/TurmaUsuario.php
require_once __DIR__ . '/../Core/Model.php';

class TurmaUsuario extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'turma_usuarios';
    }

    public function estaAtivo(int $turmaId, int $usuarioId): bool
    {
        $stmt = $this->conn->prepare("
            SELECT ativo FROM turma_usuarios WHERE turma_id = ? AND usuario_id = ? LIMIT 1
        ");
        $stmt->execute([$turmaId, $usuarioId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r && (int) $r['ativo'] === 1;
    }

    public function adicionar(int $turmaId, int $usuarioId, string $papel = 'aluno'): int
    {
        $stmt = $this->conn->prepare("SELECT id FROM turma_usuarios WHERE turma_id = ? AND usuario_id = ?");
        $stmt->execute([$turmaId, $usuarioId]);
        $id = $stmt->fetchColumn();

        if ($id) {
            $this->conn->prepare("
                UPDATE turma_usuarios SET ativo = 1, papel = ?, saiu_em = NULL WHERE id = ?
            ")->execute([$papel, $id]);
            return (int) $id;
        }

        $this->conn->prepare("
            INSERT INTO turma_usuarios (turma_id, usuario_id, papel, ativo) VALUES (?, ?, ?, 1)
        ")->execute([$turmaId, $usuarioId, $papel]);

        return (int) $this->conn->lastInsertId();
    }

    public function contarRepresentantes(int $turmaId): int
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM turma_usuarios
            WHERE turma_id = ? AND papel = 'representante' AND ativo = 1
        ");
        $stmt->execute([$turmaId]);
        return (int) $stmt->fetchColumn();
    }

    public function listarRepresentantes(int $turmaId): array
    {
        $stmt = $this->conn->prepare("
            SELECT tu.usuario_id, tu.entrou_em, u.nome, u.email
            FROM turma_usuarios tu
            INNER JOIN usuarios u ON u.id = tu.usuario_id
            WHERE tu.turma_id = ? AND tu.papel = 'representante' AND tu.ativo = 1
            ORDER BY tu.entrou_em ASC
        ");
        $stmt->execute([$turmaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAlunos(int $turmaId): array
    {
        $stmt = $this->conn->prepare("
            SELECT tu.papel, tu.entrou_em, u.id, u.nome, u.email, u.ativo
            FROM turma_usuarios tu
            INNER JOIN usuarios u ON u.id = tu.usuario_id
            WHERE tu.turma_id = ? AND tu.ativo = 1
            ORDER BY tu.papel DESC, u.nome ASC
        ");
        $stmt->execute([$turmaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ehRepresentante(int $turmaId, int $usuarioId): bool
    {
        $stmt = $this->conn->prepare("
            SELECT 1 FROM turma_usuarios
            WHERE turma_id = ? AND usuario_id = ? AND papel = 'representante' AND ativo = 1 LIMIT 1
        ");
        $stmt->execute([$turmaId, $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    public function definirPapel(int $turmaId, int $usuarioId, string $papel): bool
    {
        return $this->conn->prepare("
            UPDATE turma_usuarios SET papel = ?, ativo = 1, saiu_em = NULL
            WHERE turma_id = ? AND usuario_id = ?
        ")->execute([$papel, $turmaId, $usuarioId]);
    }
}