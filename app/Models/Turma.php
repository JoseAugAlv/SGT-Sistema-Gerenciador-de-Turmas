<?php
// app/Models/Turma.php
require_once __DIR__ . '/../Core/Model.php';

class Turma extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'turmas';
    }

    public function listarTodas(): array
    {
        return $this->conn->query("
            SELECT t.*,
                   (SELECT COUNT(*) FROM turma_usuarios tu WHERE tu.turma_id = t.id AND tu.ativo = 1) AS total_alunos,
                   (SELECT COUNT(*) FROM turma_usuarios tu WHERE tu.turma_id = t.id AND tu.papel = 'representante' AND tu.ativo = 1) AS total_reps
            FROM turmas t ORDER BY t.nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarDoUsuario(int $usuarioId): array
    {
        $stmt = $this->conn->prepare("
            SELECT t.*, tu.papel AS meu_papel
            FROM turmas t
            INNER JOIN turma_usuarios tu ON tu.turma_id = t.id
            WHERE tu.usuario_id = ? AND tu.ativo = 1
            ORDER BY t.nome ASC
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT t.*, u.nome AS bloqueada_por_nome
            FROM turmas t
            LEFT JOIN usuarios u ON u.id = t.bloqueada_por
            WHERE t.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function porCodigo(string $codigo): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM turmas WHERE codigo_acesso = ? LIMIT 1");
        $stmt->execute([$codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function codigoJaExiste(string $codigo, ?int $ignorarId = null): bool
    {
        if ($ignorarId) {
            $stmt = $this->conn->prepare("SELECT id FROM turmas WHERE codigo_acesso = ? AND id != ? LIMIT 1");
            $stmt->execute([$codigo, $ignorarId]);
        } else {
            $stmt = $this->conn->prepare("SELECT id FROM turmas WHERE codigo_acesso = ? LIMIT 1");
            $stmt->execute([$codigo]);
        }
        return (bool) $stmt->fetchColumn();
    }

    public static function montarNome($codigoEscola, $anoModulo, $curso, $periodo): string
    {
        return strtoupper($codigoEscola . '-' . $anoModulo . '-' . $curso . '-' . $periodo);
    }

    public function criar(array $d): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO turmas (nome, codigo_acesso, codigo_interno, cor_primaria, cor_secundaria)
            VALUES (:nome, :codigo_acesso, :codigo_interno, :cor_primaria, :cor_secundaria)
        ");
        $stmt->execute([
            ':nome'           => $d['nome'],
            ':codigo_acesso'  => $d['codigo_acesso'],
            ':codigo_interno' => $d['codigo_interno'] ?? null,
            ':cor_primaria'   => $d['cor_primaria']   ?? '#3498db',
            ':cor_secundaria' => $d['cor_secundaria'] ?? '#2ecc71',
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function bloquear(int $id, int $porUsuarioId, string $motivo): bool
    {
        return $this->conn->prepare("
            UPDATE turmas SET bloqueada = 1, bloqueada_motivo = ?, bloqueada_em = NOW(), bloqueada_por = ?
            WHERE id = ?
        ")->execute([$motivo, $porUsuarioId, $id]);
    }

    public function desbloquear(int $id): bool
    {
        return $this->conn->prepare("
            UPDATE turmas SET bloqueada = 0, bloqueada_motivo = NULL, bloqueada_em = NULL, bloqueada_por = NULL
            WHERE id = ?
        ")->execute([$id]);
    }

    public function regenerarCodigo(int $id): string
    {
        do {
            $novo = strtoupper(bin2hex(random_bytes(4)));
        } while ($this->codigoJaExiste($novo));

        $this->conn->prepare("UPDATE turmas SET codigo_acesso = ? WHERE id = ?")
                   ->execute([$novo, $id]);
        return $novo;
    }
}