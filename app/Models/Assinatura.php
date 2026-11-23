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

    /**
     * Assinatura em andamento da turma (aguardando 1º pagamento ou ativa), com dados do plano.
     * Uma troca de plano ainda não paga não conta: o plano antigo segue valendo até ela ser paga.
     */
    public function atualDaTurma(int $turmaId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, p.nome AS plano_nome, p.codigo AS plano_codigo, p.meses, p.tipo AS plano_tipo, p.preco AS plano_preco,
                   p.inclui_dia_a_dia, p.preco_dia_a_dia
            FROM assinaturas a
            INNER JOIN planos p ON p.id = a.plano_id
            WHERE a.turma_id = ? AND a.status IN ('aguardando', 'ativa')
              AND NOT (a.status = 'aguardando' AND (a.troca_de IS NOT NULL OR a.renovacao_de IS NOT NULL))
            ORDER BY a.id DESC LIMIT 1
        ");
        $stmt->execute([$turmaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Renovação pedida e ainda não paga (no máximo uma por turma). */
    public function renovacaoPendente(int $turmaId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, p.nome AS plano_nome, p.meses
            FROM assinaturas a
            INNER JOIN planos p ON p.id = a.plano_id
            WHERE a.turma_id = ? AND a.status = 'aguardando' AND a.renovacao_de IS NOT NULL
            ORDER BY a.id DESC LIMIT 1
        ");
        $stmt->execute([$turmaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Troca de plano pedida e ainda não paga (no máximo uma por turma). */
    public function trocaPendente(int $turmaId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, p.nome AS plano_nome, p.meses
            FROM assinaturas a
            INNER JOIN planos p ON p.id = a.plano_id
            WHERE a.turma_id = ? AND a.status = 'aguardando' AND a.troca_de IS NOT NULL
            ORDER BY a.id DESC LIMIT 1
        ");
        $stmt->execute([$turmaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT a.*, p.nome AS plano_nome, p.meses, p.tipo AS plano_tipo, t.nome AS turma_nome,
                   c.codigo AS cupom_codigo, pa.nome AS troca_de_nome
            FROM assinaturas a
            INNER JOIN planos p ON p.id = a.plano_id
            INNER JOIN turmas t ON t.id = a.turma_id
            LEFT JOIN cupons c ON c.id = a.cupom_id
            LEFT JOIN assinaturas aa ON aa.id = a.troca_de
            LEFT JOIN planos pa ON pa.id = aa.plano_id
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
                                         valor_total, valor_base, parcelas, dia_a_dia, cupom_id, troca_de, renovacao_de, comprado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $d['turma_id'], $d['plano_id'], $d['projetos_limite'], $preco['lista'], $preco['desconto_pct'],
                $preco['total'], $d['valor_base'] ?? round($preco['avista'] - ($preco['dia_a_dia'] ?? 0), 2), $preco['parcelas'],
                (int) ($d['dia_a_dia'] ?? 0), $d['cupom_id'], $d['troca_de'] ?? null, $d['renovacao_de'] ?? null, $d['comprado_por'],
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
        $stmt = $this->conn->prepare("SELECT * FROM pagamentos WHERE assinatura_id = ? AND status = 'pendente' AND tipo = 'parcela' ORDER BY numero LIMIT 1");
        $stmt->execute([$assinaturaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * A troca ainda pode ser paga? O crédito foi calculado com o plano antigo ativo e no prazo;
     * se ele venceu ou foi encerrado depois do pedido, a troca precisa ser pedida de novo.
     */
    public function trocaAindaValida(array $assinatura): bool
    {
        if (empty($assinatura['troca_de']) || $assinatura['status'] !== 'aguardando') return true;
        $stmt = $this->conn->prepare("SELECT status, fim FROM assinaturas WHERE id = ?");
        $stmt->execute([$assinatura['troca_de']]);
        $antiga = $stmt->fetch(PDO::FETCH_ASSOC);
        return $antiga && $antiga['status'] === 'ativa' && (!$antiga['fim'] || strtotime($antiga['fim']) > time());
    }

    /**
     * Marca a parcela como paga. Na 1ª, ativa a assinatura: o acesso conta a partir de agora
     * (também na troca: o plano novo começa do zero e o antigo passa a 'trocada').
     * Retorna true se foi a parcela que ativou o plano.
     */
    public function registrarPagamento(array $pag, array $assinatura, int $porUsuario, string $metodo = 'pix_teste'): bool
    {
        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("UPDATE pagamentos SET status = 'pago', pago_em = NOW(), pago_por = ?, metodo = ? WHERE id = ? AND status = 'pendente'")
                       ->execute([$porUsuario, $metodo, $pag['id']]);
            // Adicional Dia a dia pago: libera na assinatura
            if (($pag['tipo'] ?? 'parcela') === 'adicional') {
                $this->conn->prepare("UPDATE assinaturas SET dia_a_dia = 1 WHERE id = ?")->execute([$pag['assinatura_id']]);
            }
            $ativou = false;
            if ($assinatura['status'] === 'aguardando') {
                $inicio = date('Y-m-d H:i:s');
                if (!empty($assinatura['troca_de'])) {
                    $this->conn->prepare("UPDATE assinaturas SET status = 'trocada' WHERE id = ? AND status = 'ativa'")
                               ->execute([$assinatura['troca_de']]);
                }
                // Renovação: o novo período começa no fim do atual (sem perder dias); se já venceu, começa agora
                if (!empty($assinatura['renovacao_de'])) {
                    $ant = $this->conn->prepare("SELECT fim FROM assinaturas WHERE id = ?");
                    $ant->execute([$assinatura['renovacao_de']]);
                    $fimAnt = $ant->fetchColumn();
                    if ($fimAnt && strtotime($fimAnt) > time()) $inicio = $fimAnt;
                    $this->conn->prepare("UPDATE assinaturas SET status = 'renovada' WHERE id = ? AND status = 'ativa'")
                               ->execute([$assinatura['renovacao_de']]);
                }
                $fim = $assinatura['meses'] ? date('Y-m-d H:i:s', strtotime($inicio . ' +' . (int) $assinatura['meses'] . ' months')) : null;
                $this->conn->prepare("UPDATE assinaturas SET status = 'ativa', inicio = ?, fim = ? WHERE id = ?")
                           ->execute([$inicio, $fim, $assinatura['id']]);
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

    // ============ ADICIONAL DIA A DIA ============

    public function adicionalPendente(int $assinaturaId): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM pagamentos WHERE assinatura_id = ? AND tipo = 'adicional' AND status = 'pendente' LIMIT 1");
        $st->execute([$assinaturaId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Cria o pagamento do adicional (à vista, vence hoje). Retorna o id do pagamento. */
    public function criarAdicional(int $assinaturaId, float $valor): int
    {
        $st = $this->conn->prepare("SELECT COALESCE(MAX(numero), 0) + 1 FROM pagamentos WHERE assinatura_id = ?");
        $st->execute([$assinaturaId]);
        $this->conn->prepare("INSERT INTO pagamentos (assinatura_id, numero, tipo, valor, vencimento, txid) VALUES (?, ?, 'adicional', ?, CURDATE(), ?)")
                   ->execute([$assinaturaId, (int) $st->fetchColumn(), $valor, 'TESTE' . strtoupper(bin2hex(random_bytes(8)))]);
        return (int) $this->conn->lastInsertId();
    }

    public function cancelarPagamento(int $id): void
    {
        $this->conn->prepare("UPDATE pagamentos SET status = 'cancelado' WHERE id = ? AND status = 'pendente'")->execute([$id]);
    }

    // ============ AJUSTES DO MASTER ============

    /** Soma (ou tira, com número negativo) meses do fim do plano. */
    public function prorrogar(int $id, int $meses): void
    {
        $this->conn->prepare("UPDATE assinaturas SET fim = DATE_ADD(fim, INTERVAL ? MONTH) WHERE id = ? AND fim IS NOT NULL")
                   ->execute([$meses, $id]);
    }

    public function alterarLimiteProjetos(int $id, int $limite): void
    {
        $this->conn->prepare("UPDATE assinaturas SET projetos_limite = ? WHERE id = ?")->execute([$limite, $id]);
    }

    /** Troca o plano sem cobrança: novo plano, novo limite e fim recalculado a partir do início. */
    public function trocarPlanoSemCobranca(array $ass, array $plano): void
    {
        $fim = $ass['fim'];
        if ($ass['inicio']) {
            $fim = $plano['meses'] ? date('Y-m-d H:i:s', strtotime($ass['inicio'] . ' +' . (int) $plano['meses'] . ' months')) : null;
        }
        $this->conn->prepare("UPDATE assinaturas SET plano_id = ?, projetos_limite = ?, fim = ? WHERE id = ?")
                   ->execute([$plano['id'], $plano['projetos'], $fim, $ass['id']]);
    }

    /** Muda valor e vencimento de uma parcela pendente e refaz o total da assinatura. */
    public function editarParcela(array $pag, float $valor, string $vencimento): void
    {
        $this->conn->prepare("UPDATE pagamentos SET valor = ?, vencimento = ? WHERE id = ? AND status = 'pendente'")
                   ->execute([$valor, $vencimento, $pag['id']]);
        $this->conn->prepare("
            UPDATE assinaturas SET valor_total = (SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE assinatura_id = ? AND status <> 'cancelado')
            WHERE id = ?
        ")->execute([$pag['assinatura_id'], $pag['assinatura_id']]);
    }

    /** Vendas para o master: assinaturas com turma, plano e quanto já foi pago. */
    public function listarVendas(int $limite = 100): array
    {
        return $this->conn->query("
            SELECT a.*, p.nome AS plano_nome, t.nome AS turma_nome, c.codigo AS cupom_codigo, pa.nome AS troca_de_nome,
                   (SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE assinatura_id = a.id AND status = 'pago') AS pago,
                   (SELECT COUNT(*) FROM pagamentos WHERE assinatura_id = a.id AND status = 'pago') AS parcelas_pagas
            FROM assinaturas a
            INNER JOIN planos p ON p.id = a.plano_id
            INNER JOIN turmas t ON t.id = a.turma_id
            LEFT JOIN cupons c ON c.id = a.cupom_id
            LEFT JOIN assinaturas aa ON aa.id = a.troca_de
            LEFT JOIN planos pa ON pa.id = aa.plano_id
            ORDER BY a.id DESC
            LIMIT " . (int) $limite
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Projetos do período atual do plano. Renovação começa um período novo; troca de plano continua o
     * período de antes (senão trocar daria projetos extras). Sem assinatura: todos os projetos da turma.
     */
    public function projetosUsados(int $turmaId): int
    {
        $desde = null;
        $a = $this->atualDaTurma($turmaId);
        $busca = $this->conn->prepare("SELECT id, inicio, troca_de, renovacao_de FROM assinaturas WHERE id = ?");
        for ($i = 0; $a && $i < 20; $i++) {
            // renovação paga mas o período novo ainda não começou: vale o período anterior
            if (!empty($a['renovacao_de']) && $a['inicio'] && strtotime($a['inicio']) > time()) {
                $busca->execute([$a['renovacao_de']]); $a = $busca->fetch(PDO::FETCH_ASSOC) ?: null; continue;
            }
            $desde = $a['inicio'];
            if (empty($a['troca_de'])) break;
            $busca->execute([$a['troca_de']]); $a = $busca->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        $sql = "SELECT COUNT(*) FROM projetos WHERE turma_id = ?" . ($desde ? " AND created_at >= ?" : "");
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($desde ? [$turmaId, $desde] : [$turmaId]);
        return (int) $stmt->fetchColumn();
    }
}
