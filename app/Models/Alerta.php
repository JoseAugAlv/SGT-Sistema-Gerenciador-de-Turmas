<?php
// app/Models/Alerta.php
require_once __DIR__ . '/../Core/Model.php';

class Alerta extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'alertas';
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, t.nome AS turma_nome, p.nome AS projeto_nome, g.nome AS grupo_nome,
                   u.nome AS autor_nome
            FROM alertas a
            INNER JOIN turmas t ON t.id = a.turma_id
            LEFT JOIN projetos p ON p.id = a.projeto_id
            LEFT JOIN grupos g ON g.id = a.grupo_id
            LEFT JOIN usuarios u ON u.id = a.autor_id
            WHERE a.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listarVisiveis(int $turmaId, int $usuarioId): array
    {
        // Alertas da turma inteira + dos projetos/grupos onde o usuário está
        $stmt = $this->conn->prepare("
            SELECT DISTINCT a.*, u.nome AS autor_nome
            FROM alertas a
            INNER JOIN usuarios u ON u.id = a.autor_id
            LEFT JOIN projetos p ON p.id = a.projeto_id
            WHERE a.turma_id = ?
              AND (a.expira_em IS NULL OR a.expira_em >= NOW())
              AND (
                (a.projeto_id IS NULL AND a.grupo_id IS NULL)
                OR (a.projeto_id IS NOT NULL AND a.grupo_id IS NULL AND EXISTS (
                    SELECT 1 FROM turma_usuarios tu
                    WHERE tu.turma_id = a.turma_id AND tu.usuario_id = ? AND tu.ativo = 1
                ))
                OR (a.grupo_id IS NOT NULL AND EXISTS (
                    SELECT 1 FROM grupo_alunos ga
                    WHERE ga.grupo_id = a.grupo_id AND ga.usuario_id = ? AND ga.saiu_em IS NULL
                ))
              )
            ORDER BY a.urgente DESC, a.created_at DESC
        ");
        $stmt->execute([$turmaId, $usuarioId, $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar(array $d): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO alertas
                (autor_id, turma_id, projeto_id, grupo_id, titulo, mensagem, urgente, expira_em)
            VALUES
                (:autor_id, :turma_id, :projeto_id, :grupo_id, :titulo, :mensagem, :urgente, :expira_em)
        ");
        $stmt->execute([
            ':autor_id'   => $d['autor_id'],
            ':turma_id'   => $d['turma_id'],
            ':projeto_id' => $d['projeto_id'] ?: null,
            ':grupo_id'   => $d['grupo_id']   ?: null,
            ':titulo'     => $d['titulo'],
            ':mensagem'   => $d['mensagem'],
            ':urgente'    => $d['urgente'] ?? 0,
            ':expira_em'  => $d['expira_em'] ?: null,
        ]);
        return (int) $this->conn->lastInsertId();
    }
}