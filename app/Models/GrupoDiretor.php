<?php
// app/Models/GrupoDiretor.php
require_once __DIR__ . '/../Core/Model.php';

class GrupoDiretor extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'grupo_diretores';
    }

    public function listarAtivos(int $grupoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT gd.id, gd.usuario_id, gd.nomeado_em, u.nome, u.email
            FROM grupo_diretores gd
            INNER JOIN usuarios u ON u.id = gd.usuario_id
            WHERE gd.grupo_id = ? AND gd.ativo = 1
            ORDER BY gd.nomeado_em ASC
        ");
        $stmt->execute([$grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarHistorico(int $grupoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT gd.*,
                   u.nome  AS diretor_nome, u.email AS diretor_email,
                   np.nome AS nomeado_por_nome,
                   rp.nome AS removido_por_nome
            FROM grupo_diretores gd
            LEFT JOIN usuarios u  ON u.id  = gd.usuario_id
            LEFT JOIN usuarios np ON np.id = gd.nomeado_por
            LEFT JOIN usuarios rp ON rp.id = gd.removido_por
            WHERE gd.grupo_id = ?
            ORDER BY gd.nomeado_em DESC
        ");
        $stmt->execute([$grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ehDiretorAtivo(int $grupoId, int $usuarioId): bool
    {
        $stmt = $this->conn->prepare("
            SELECT 1 FROM grupo_diretores
            WHERE grupo_id = ? AND usuario_id = ? AND ativo = 1 LIMIT 1
        ");
        $stmt->execute([$grupoId, $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    public function nomear(int $grupoId, int $usuarioId, int $nomeadoPor): int
    {
        $stmt = $this->conn->prepare("
            SELECT id FROM grupo_diretores
            WHERE grupo_id = ? AND usuario_id = ? AND ativo = 1 LIMIT 1
        ");
        $stmt->execute([$grupoId, $usuarioId]);
        if ($id = $stmt->fetchColumn()) {
            return (int) $id;
        }

        $stmt = $this->conn->prepare("
            SELECT id FROM grupo_diretores
            WHERE grupo_id = ? AND usuario_id = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$grupoId, $usuarioId]);
        $idAnterior = $stmt->fetchColumn();

        if ($idAnterior) {
            $this->conn->prepare("
                UPDATE grupo_diretores
                SET ativo = 1, nomeado_por = ?, nomeado_em = NOW(),
                    removido_por = NULL, removido_em = NULL
                WHERE id = ?
            ")->execute([$nomeadoPor, $idAnterior]);
            return (int) $idAnterior;
        }

        $this->conn->prepare("
            INSERT INTO grupo_diretores (grupo_id, usuario_id, ativo, nomeado_por)
            VALUES (?, ?, 1, ?)
        ")->execute([$grupoId, $usuarioId, $nomeadoPor]);
        return (int) $this->conn->lastInsertId();
    }

    public function remover(int $grupoId, int $usuarioId, int $removidoPor): bool
    {
        return $this->conn->prepare("
            UPDATE grupo_diretores
            SET ativo = 0, removido_por = ?, removido_em = NOW()
            WHERE grupo_id = ? AND usuario_id = ? AND ativo = 1
        ")->execute([$removidoPor, $grupoId, $usuarioId]);
    }

    /**
     * Reatribui atas pendentes do diretor removido.
     * Se houver outro diretor ativo, assume ele. Senão, deixa NULL.
     * Retorna o número de atas afetadas.
     */
    public function reatribuirAtasPendentes(int $grupoId, int $diretorRemovido): int
    {
        $stmt = $this->conn->prepare("
            SELECT usuario_id FROM grupo_diretores
            WHERE grupo_id = ? AND ativo = 1 AND usuario_id != ?
            ORDER BY nomeado_em ASC LIMIT 1
        ");
        $stmt->execute([$grupoId, $diretorRemovido]);
        $novoDiretor = $stmt->fetchColumn();

        $stmt = $this->conn->prepare("
            UPDATE atas SET diretor_id = ?
            WHERE grupo_id = ? AND diretor_id = ? AND status = 'pendente'
        ");
        $stmt->execute([$novoDiretor ?: null, $grupoId, $diretorRemovido]);
        return $stmt->rowCount();
    }
}