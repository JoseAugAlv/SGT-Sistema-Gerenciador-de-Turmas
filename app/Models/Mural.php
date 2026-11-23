<?php
// app/Models/Mural.php
// Mural de fotos e conquistas da turma/grêmio, com curtidas.
require_once __DIR__ . '/../Core/Model.php';

class Mural extends Model
{
    const ICONES = [
        'trofeu'  => ['fa-trophy', 'Troféu'],
        'medalha' => ['fa-medal', 'Medalha'],
        'estrela' => ['fa-star', 'Estrela'],
        'foguete' => ['fa-rocket', 'Foguete'],
        'coracao' => ['fa-heart', 'Coração'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'mural_posts';
    }

    /** Publicações aprovadas (fixadas primeiro), com curtidas e se o usuário curtiu. */
    public function aprovados(int $turmaId, int $usuarioId): array
    {
        $st = $this->conn->prepare("
            SELECT p.*, u.nome AS autor,
                   (SELECT COUNT(*) FROM mural_curtidas c WHERE c.post_id = p.id) AS curtidas,
                   EXISTS(SELECT 1 FROM mural_curtidas c WHERE c.post_id = p.id AND c.usuario_id = ?) AS curti
            FROM mural_posts p LEFT JOIN usuarios u ON u.id = p.autor_id
            WHERE p.turma_id = ? AND p.aprovado = 1
            ORDER BY p.fixado DESC, p.created_at DESC
            LIMIT 120
        ");
        $st->execute([$usuarioId, $turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Aguardando aprovação: todas (gestor) ou só as do autor. */
    public function pendentes(int $turmaId, ?int $autorId = null): array
    {
        $sql = "SELECT p.*, u.nome AS autor FROM mural_posts p LEFT JOIN usuarios u ON u.id = p.autor_id
                WHERE p.turma_id = ? AND p.aprovado = 0" . ($autorId ? " AND p.autor_id = ?" : '') . " ORDER BY p.created_at";
        $st = $this->conn->prepare($sql);
        $st->execute($autorId ? [$turmaId, $autorId] : [$turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM mural_posts WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function criar(array $d): int
    {
        $this->conn->prepare("INSERT INTO mural_posts (turma_id, autor_id, tipo, icone, titulo, texto, arquivo, aprovado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                   ->execute([$d['turma_id'], $d['autor_id'], $d['tipo'], $d['icone'], $d['titulo'], $d['texto'], $d['arquivo'], $d['aprovado']]);
        return (int) $this->conn->lastInsertId();
    }

    public function aprovar(int $id): void
    {
        $this->conn->prepare("UPDATE mural_posts SET aprovado = 1, created_at = NOW() WHERE id = ?")->execute([$id]);
    }

    public function fixar(int $id): void
    {
        $this->conn->prepare("UPDATE mural_posts SET fixado = 1 - fixado WHERE id = ?")->execute([$id]);
    }

    public function excluir(int $id): void
    {
        $this->conn->prepare("DELETE FROM mural_posts WHERE id = ?")->execute([$id]);
    }

    /** Liga/desliga a curtida. Retorna [curtiu agora?, total]. */
    public function curtir(int $postId, int $usuarioId): array
    {
        $del = $this->conn->prepare("DELETE FROM mural_curtidas WHERE post_id = ? AND usuario_id = ?");
        $del->execute([$postId, $usuarioId]);
        $curtiu = $del->rowCount() === 0;
        if ($curtiu) $this->conn->prepare("INSERT IGNORE INTO mural_curtidas (post_id, usuario_id) VALUES (?, ?)")->execute([$postId, $usuarioId]);
        $st = $this->conn->prepare("SELECT COUNT(*) FROM mural_curtidas WHERE post_id = ?");
        $st->execute([$postId]);
        return [$curtiu, (int) $st->fetchColumn()];
    }
}
