<?php
// app/Models/Convite.php
// Convites por email: o representante informa o email; o aluno só entra na turma quando aceita pelo link
// (quem não tem conta completa o cadastro; quem já tem confirma com um botão).
require_once __DIR__ . '/../Core/Model.php';

class Convite extends Model
{
    const DIAS_VALIDADE = 14;

    public function __construct()
    {
        parent::__construct();
        $this->table = 'convites';
    }

    /** Cria um convite (cancela o pendente anterior da mesma pessoa na mesma turma). Retorna o token. */
    public function criar(int $turmaId, int $usuarioId, string $email, int $porId): string
    {
        $this->conn->prepare("UPDATE convites SET status = 'cancelado' WHERE turma_id = ? AND usuario_id = ? AND status = 'pendente'")
                   ->execute([$turmaId, $usuarioId]);
        $token = bin2hex(random_bytes(24));
        $this->conn->prepare("
            INSERT INTO convites (turma_id, usuario_id, email, token, convidado_por, expira_em)
            VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL " . self::DIAS_VALIDADE . " DAY))
        ")->execute([$turmaId, $usuarioId, $email, $token, $porId]);
        return $token;
    }

    /** Convite pendente e no prazo, com turma e usuário. */
    public function validoPorToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) return null;
        $stmt = $this->conn->prepare("
            SELECT c.*, t.nome AS turma_nome, t.tipo AS turma_tipo, u.nome AS usuario_nome, u.cadastro_pendente, u.ativo AS usuario_ativo
            FROM convites c
            INNER JOIN turmas t ON t.id = c.turma_id
            INNER JOIN usuarios u ON u.id = c.usuario_id
            WHERE c.token = ? AND c.status = 'pendente' AND c.expira_em > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM convites WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Aceita o convite e coloca a pessoa na turma (só aqui ela entra na lista). */
    public function aceitar(int $id): void
    {
        $st = $this->conn->prepare("UPDATE convites SET status = 'aceito', aceito_em = NOW() WHERE id = ? AND status = 'pendente'");
        $st->execute([$id]);
        if (!$st->rowCount()) return;
        $c = $this->porId($id);
        require_once __DIR__ . '/TurmaUsuario.php';
        $tu = new TurmaUsuario();
        // quem já está ativo (ex.: virou representante) não tem o papel trocado
        if (!$tu->estaAtivo((int) $c['turma_id'], (int) $c['usuario_id'])) $tu->adicionar((int) $c['turma_id'], (int) $c['usuario_id'], 'aluno');
    }

    public function cancelar(int $id): void
    {
        $this->conn->prepare("UPDATE convites SET status = 'cancelado' WHERE id = ? AND status = 'pendente'")->execute([$id]);
    }

    /** Convites ainda sem resposta das turmas informadas. */
    public function pendentes(array $turmaIds): array
    {
        if (!$turmaIds) return [];
        $marc = implode(',', array_fill(0, count($turmaIds), '?'));
        $stmt = $this->conn->prepare("
            SELECT c.*, t.nome AS turma_nome
            FROM convites c INNER JOIN turmas t ON t.id = c.turma_id
            WHERE c.status = 'pendente' AND c.turma_id IN ({$marc})
            ORDER BY c.created_at DESC
        ");
        $stmt->execute(array_map('intval', $turmaIds));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
