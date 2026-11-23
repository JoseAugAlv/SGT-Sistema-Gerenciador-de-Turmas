<?php
// app/Models/SolicitacaoGrupo.php
// Pedidos dos alunos: entrar num grupo ou ser diretor (quando o projeto permite). O representante aprova ou recusa.
require_once __DIR__ . '/../Core/Model.php';

class SolicitacaoGrupo extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'solicitacoes_grupo';
    }

    public function criar(int $grupoId, int $usuarioId, string $tipo): int
    {
        $this->conn->prepare("INSERT INTO solicitacoes_grupo (grupo_id, usuario_id, tipo) VALUES (?, ?, ?)")
                   ->execute([$grupoId, $usuarioId, $tipo]);
        return (int) $this->conn->lastInsertId();
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT s.*, g.nome AS grupo_nome, g.projeto_id, u.nome AS usuario_nome
            FROM solicitacoes_grupo s
            INNER JOIN grupos g ON g.id = s.grupo_id
            INNER JOIN usuarios u ON u.id = s.usuario_id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Pedido pendente do mesmo tipo no projeto (um aluno só pede para entrar em um grupo por vez). */
    public function pendenteNoProjeto(int $projetoId, int $usuarioId, string $tipo): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT s.* FROM solicitacoes_grupo s INNER JOIN grupos g ON g.id = s.grupo_id
            WHERE g.projeto_id = ? AND s.usuario_id = ? AND s.tipo = ? AND s.status = 'pendente' LIMIT 1
        ");
        $stmt->execute([$projetoId, $usuarioId, $tipo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Pendentes do projeto (para o representante). */
    public function pendentesDoProjeto(int $projetoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT s.*, g.nome AS grupo_nome, u.nome AS usuario_nome
            FROM solicitacoes_grupo s
            INNER JOIN grupos g ON g.id = s.grupo_id
            INNER JOIN usuarios u ON u.id = s.usuario_id
            WHERE g.projeto_id = ? AND s.status = 'pendente'
            ORDER BY s.created_at
        ");
        $stmt->execute([$projetoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Pendentes de um aluno no projeto: [grupo_id => [tipo => solicitação]]. */
    public function minhasNoProjeto(int $projetoId, int $usuarioId): array
    {
        $stmt = $this->conn->prepare("
            SELECT s.* FROM solicitacoes_grupo s INNER JOIN grupos g ON g.id = s.grupo_id
            WHERE g.projeto_id = ? AND s.usuario_id = ? AND s.status = 'pendente'
        ");
        $stmt->execute([$projetoId, $usuarioId]);
        $r = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) $r[(int) $s['grupo_id']][$s['tipo']] = $s;
        return $r;
    }

    public function responder(int $id, string $status, ?int $porId): void
    {
        $this->conn->prepare("UPDATE solicitacoes_grupo SET status = ?, respondida_por = ?, respondida_em = NOW() WHERE id = ? AND status = 'pendente'")
                   ->execute([$status, $porId, $id]);
    }
}
