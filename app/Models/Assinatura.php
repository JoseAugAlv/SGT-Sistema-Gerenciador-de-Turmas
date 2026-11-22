<?php
// app/Models/Assinatura.php
// Assinatura de plano por turma e as parcelas (pagamentos) dela.
require_once __DIR__ . '/../Core/Model.php';

class Assinatura extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'assinaturas';
    }

    /** Assinatura em andamento da turma (aguardando 1º pagamento ou ativa), com dados do plano. */
    public function atualDaTurma(int $turmaId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, p.nome AS plano_nome, p.codigo AS plano_codigo, p.meses, p.tipo AS plano_tipo
            FROM assinaturas a
            INNER JOIN planos p ON p.id = a.plano_id
            WHERE a.turma_id = ? AND a.status IN ('aguardando', 'ativa')
            ORDER BY a.id DESC LIMIT 1
        ");
        $stmt->execute([$turmaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, p.nome AS plano_nome, p.meses, t.nome AS turma_nome
            FROM assinaturas a
            INNER JOIN planos p ON p.id = a.plano_id
            INNER JOIN turmas t ON t.id = a.turma_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Cria a assinatura aguardando pagamento e as parcelas (vencimento a cada 30 dias). */
    public function criar(array $d, array $preco): int
    {
        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("
                INSERT INTO assinaturas (turma_id, plano_id, projetos_limite, valor_lista, desconto_pct,
                                         valor_total, parcelas, cupom_id, comprado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $d['turma_id'], $d['plano_id'], $d['projetos_limite'], $preco['lista'], $preco['desconto_pct'],
                $preco['total'], $preco['parcelas'], $d['cupom_id'], $d['comprado_por'],
            ]);
            $id = (int) $this->conn->lastInsertId();

            $ins = $this->conn->prepare("INSERT INTO pagamentos (assinatura_id, numero, valor, vencimento, txid) VALUES (?, ?, ?, ?, ?)");
            for ($n = 1; $n <= $preco['parcelas']; $n++) {
                $valor = $n === $preco['parcelas'] ? $preco['ultima_parcela'] : $preco['parcela'];
                $venc  = date('Y-m-d', strtotime('+' . (30 * ($n - 1)) . ' days'));
                $ins->execute([$id, $n, $valor, $venc, 'TESTE' . strtoupper(bin2hex(random_bytes(8)))]);
            }
            $this->conn->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function parcelas(int $assinaturaId): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM pagamentos WHERE assinatura_id = ? ORDER BY numero");
        $stmt->execute([$assinaturaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function pagamento(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM pagamentos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Próxima parcela a pagar (as parcelas são pagas em ordem). */
    public function proximaParcela(int $assinaturaId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM pagamentos WHERE assinatura_id = ? AND status = 'pendente' ORDER BY numero LIMIT 1");
        $stmt->execute([$assinaturaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Marca a parcela como paga. Na 1ª, ativa a assinatura: o acesso conta a partir de agora.
     * Retorna true se foi a parcela que ativou o plano.
     */
    public function registrarPagamento(array $pag, array $assinatura, int $porUsuario): bool
    {
        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("UPDATE pagamentos SET status = 'pago', pago_em = NOW(), pago_por = ? WHERE id = ? AND status = 'pendente'")
                       ->execute([$porUsuario, $pag['id']]);
            $ativou = false;
            if ($assinatura['status'] === 'aguardando') {
                $fim = $assinatura['meses'] ? date('Y-m-d H:i:s', strtotime('+' . (int) $assinatura['meses'] . ' months')) : null;
                $this->conn->prepare("UPDATE assinaturas SET status = 'ativa', inicio = NOW(), fim = ? WHERE id = ?")
                           ->execute([$fim, $assinatura['id']]);
                $ativou = true;
            }
            $this->conn->commit();
            return $ativou;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function cancelar(int $id): void
    {
        $this->conn->prepare("UPDATE assinaturas SET status = 'cancelada' WHERE id = ?")->execute([$id]);
        $this->conn->prepare("UPDATE pagamentos SET status = 'cancelado' WHERE assinatura_id = ? AND status = 'pendente'")->execute([$id]);
    }

    /** Vendas para o master: assinaturas com turma, plano e quanto já foi pago. */
    public function listarVendas(int $limite = 100): array
    {
        return $this->conn->query("
            SELECT a.*, p.nome AS plano_nome, t.nome AS turma_nome, c.codigo AS cupom_codigo,
                   (SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE assinatura_id = a.id AND status = 'pago') AS pago,
                   (SELECT COUNT(*) FROM pagamentos WHERE assinatura_id = a.id AND status = 'pago') AS parcelas_pagas
            FROM assinaturas a
            INNER JOIN planos p ON p.id = a.plano_id
            INNER JOIN turmas t ON t.id = a.turma_id
            LEFT JOIN cupons c ON c.id = a.cupom_id
            ORDER BY a.id DESC
            LIMIT " . (int) $limite
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function projetosUsados(int $turmaId): int
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM projetos WHERE turma_id = ?");
        $stmt->execute([$turmaId]);
        return (int) $stmt->fetchColumn();
    }
}
