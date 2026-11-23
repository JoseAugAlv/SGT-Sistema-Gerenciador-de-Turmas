<?php
// app/Models/Rateio.php
// Rateio da mensalidade: uma parcela do plano dividida entre os alunos.
require_once __DIR__ . '/../Core/Model.php';

class Rateio extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'rateios';
    }

    public function porId(int $id): ?array
    {
        $st = $this->conn->prepare("
            SELECT r.*, p.valor AS valor_parcela, p.numero, p.status AS status_parcela, p.assinatura_id, t.nome AS turma_nome
            FROM rateios r INNER JOIN pagamentos p ON p.id = r.pagamento_id INNER JOIN turmas t ON t.id = r.turma_id
            WHERE r.id = ?
        ");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Rateio aberto de uma parcela (no máximo um). */
    public function abertoDoPagamento(int $pagamentoId): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM rateios WHERE pagamento_id = ? AND status = 'aberto' LIMIT 1");
        $st->execute([$pagamentoId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function partes(int $rateioId): array
    {
        $st = $this->conn->prepare("SELECT rp.*, u.nome FROM rateio_partes rp INNER JOIN usuarios u ON u.id = rp.usuario_id WHERE rp.rateio_id = ? ORDER BY rp.status <> 'pendente', u.nome");
        $st->execute([$rateioId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function parteDe(int $rateioId, int $usuarioId): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM rateio_partes WHERE rateio_id = ? AND usuario_id = ?");
        $st->execute([$rateioId, $usuarioId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Partes pendentes do usuário em rateios abertos (para o aviso no painel da turma). */
    public function pendentesDoUsuario(int $usuarioId, ?int $turmaId = null): array
    {
        $st = $this->conn->prepare("
            SELECT rp.id, rp.valor, r.id AS rateio_id, r.turma_id FROM rateio_partes rp INNER JOIN rateios r ON r.id = rp.rateio_id
            WHERE rp.usuario_id = ? AND rp.status = 'pendente' AND r.status = 'aberto'" . ($turmaId ? " AND r.turma_id = ?" : '')
        );
        $st->execute($turmaId ? [$usuarioId, $turmaId] : [$usuarioId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Cria o rateio dividindo $valor entre $usuarios (centavos que sobram vão nas primeiras partes). */
    public function criar(int $pagamentoId, int $turmaId, int $criadoPor, float $valor, array $usuarios): int
    {
        $n = count($usuarios);
        $centavos = (int) round($valor * 100);
        $base = intdiv($centavos, $n);
        $sobra = $centavos - $base * $n;
        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("INSERT INTO rateios (pagamento_id, turma_id, criado_por) VALUES (?, ?, ?)")->execute([$pagamentoId, $turmaId, $criadoPor]);
            $id = (int) $this->conn->lastInsertId();
            $ins = $this->conn->prepare("INSERT INTO rateio_partes (rateio_id, usuario_id, valor) VALUES (?, ?, ?)");
            foreach (array_values($usuarios) as $i => $uid) $ins->execute([$id, (int) $uid, ($base + ($i < $sobra ? 1 : 0)) / 100]);
            $this->conn->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function pagarParte(int $parteId): bool
    {
        $st = $this->conn->prepare("UPDATE rateio_partes SET status = 'pago', pago_em = NOW() WHERE id = ? AND status = 'pendente'");
        $st->execute([$parteId]);
        return $st->rowCount() > 0;
    }

    /** O representante cobre as partes que faltam. Retorna o valor coberto. */
    public function cobrirRestante(int $rateioId): float
    {
        $st = $this->conn->prepare("SELECT COALESCE(SUM(valor), 0) FROM rateio_partes WHERE rateio_id = ? AND status = 'pendente'");
        $st->execute([$rateioId]);
        $v = (float) $st->fetchColumn();
        $this->conn->prepare("UPDATE rateio_partes SET status = 'coberto', pago_em = NOW() WHERE rateio_id = ? AND status = 'pendente'")->execute([$rateioId]);
        return $v;
    }

    public function faltam(int $rateioId): int
    {
        $st = $this->conn->prepare("SELECT COUNT(*) FROM rateio_partes WHERE rateio_id = ? AND status = 'pendente'");
        $st->execute([$rateioId]);
        return (int) $st->fetchColumn();
    }

    public function quitar(int $rateioId): void
    {
        $this->conn->prepare("UPDATE rateios SET status = 'quitado', quitado_em = NOW() WHERE id = ?")->execute([$rateioId]);
    }

    public function cancelar(int $rateioId): void
    {
        $this->conn->prepare("UPDATE rateios SET status = 'cancelado' WHERE id = ? AND status = 'aberto'")->execute([$rateioId]);
    }

    public function algumPago(int $rateioId): bool
    {
        $st = $this->conn->prepare("SELECT 1 FROM rateio_partes WHERE rateio_id = ? AND status = 'pago' LIMIT 1");
        $st->execute([$rateioId]);
        return (bool) $st->fetchColumn();
    }
}
