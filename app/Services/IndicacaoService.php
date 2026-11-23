<?php
// app/Services/IndicacaoService.php
// Indicação: cada turma com plano tem um código. A turma indicada ganha desconto (config indicacao_pct) na 1ª compra;
// quando ela ativa o plano, a turma que indicou ganha um crédito (config indicacao_recompensa_pct), usado:
// 1) nas parcelas que ainda faltam pagar do plano atual, ou 2) na próxima compra (se não houver parcelas).
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/PrecoService.php';
require_once __DIR__ . '/../Models/Notificacao.php';

class IndicacaoService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /** Código de indicação da turma (cria na primeira vez). */
    public function codigoDaTurma(int $turmaId): string
    {
        $st = $this->pdo->prepare("SELECT codigo FROM cupons WHERE origem = 'indicacao' AND turma_dona_id = ? LIMIT 1");
        $st->execute([$turmaId]);
        if ($c = $st->fetchColumn()) return $c;

        $existe = $this->pdo->prepare("SELECT 1 FROM cupons WHERE codigo = ?");
        do {
            $codigo = 'IND' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $existe->execute([$codigo]);
        } while ($existe->fetchColumn());
        $this->pdo->prepare("INSERT INTO cupons (codigo, origem, turma_dona_id, tipo_plano, percentual) VALUES (?, 'indicacao', ?, 'turma', NULL)")
                  ->execute([$codigo, $turmaId]);
        return $codigo;
    }

    /** Chamado quando uma assinatura é ativada (1ª parcela paga). Dá o crédito a quem indicou. */
    public function aoAtivar(array $assinatura): void
    {
        if (empty($assinatura['cupom_id'])) return;
        $st = $this->pdo->prepare("SELECT turma_dona_id FROM cupons WHERE id = ? AND origem = 'indicacao'");
        $st->execute([(int) $assinatura['cupom_id']]);
        $dona = (int) $st->fetchColumn();
        if (!$dona || $dona === (int) $assinatura['turma_id']) return;

        $pct = (float) PrecoService::config('indicacao_recompensa_pct', '10');
        if ($pct <= 0) return;
        $nomeIndicada = $this->nomeTurma((int) $assinatura['turma_id']);
        $this->pdo->prepare("INSERT INTO creditos_indicacao (turma_id, origem_turma_id, origem_assinatura_id, pct, descricao) VALUES (?, ?, ?, ?, ?)")
                  ->execute([$dona, (int) $assinatura['turma_id'], (int) $assinatura['id'], $pct, "Indicou a turma {$nomeIndicada}"]);

        $this->avisar($dona, 'Você ganhou um desconto por indicação',
            "A turma {$nomeIndicada} usou o código de vocês. Vocês ganharam " . $this->fmtPct($pct) . " de desconto.");
        $this->aplicarNasParcelas($dona);
    }

    /**
     * Usa os créditos disponíveis nas parcelas que ainda faltam pagar do plano atual (cada crédito tira X% de cada parcela pendente).
     * Sem parcelas pendentes, os créditos ficam guardados para a próxima compra.
     */
    public function aplicarNasParcelas(int $turmaId): void
    {
        $st = $this->pdo->prepare("SELECT id FROM assinaturas WHERE turma_id = ? AND status = 'ativa' ORDER BY id DESC LIMIT 1");
        $st->execute([$turmaId]);
        $assId = (int) $st->fetchColumn();
        if (!$assId) return;
        $pend = $this->pdo->prepare("SELECT COUNT(*) FROM pagamentos WHERE assinatura_id = ? AND status = 'pendente' AND tipo = 'parcela'");
        $pend->execute([$assId]);
        if (!(int) $pend->fetchColumn()) return;

        foreach ($this->disponiveis($turmaId) as $cr) {
            $f = 1 - (float) $cr['pct'] / 100;
            $this->pdo->prepare("UPDATE pagamentos SET valor = ROUND(valor * ?, 2) WHERE assinatura_id = ? AND status = 'pendente' AND tipo = 'parcela'")->execute([$f, $assId]);
            $this->pdo->prepare("UPDATE assinaturas SET valor_total = (SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE assinatura_id = ? AND status <> 'cancelado') WHERE id = ?")
                      ->execute([$assId, $assId]);
            $this->marcarUsado((int) $cr['id'], $assId);
            $this->avisar($turmaId, 'Desconto aplicado nas parcelas', 'O desconto de indicação de ' . $this->fmtPct((float) $cr['pct']) . ' foi aplicado nas parcelas que faltam pagar.');
        }
    }

    /** Créditos ainda não usados da turma (o maior primeiro). */
    public function disponiveis(int $turmaId): array
    {
        $st = $this->pdo->prepare("SELECT * FROM creditos_indicacao WHERE turma_id = ? AND status = 'disponivel' ORDER BY pct DESC, id");
        $st->execute([$turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Maior crédito disponível, como "cupom virtual" para PrecoService (ou null). */
    public function creditoComoCupom(int $turmaId): ?array
    {
        $cr = $this->disponiveis($turmaId)[0] ?? null;
        return $cr ? ['id' => null, 'credito_id' => (int) $cr['id'], 'origem' => 'credito', 'codigo' => 'Crédito de indicação',
                      'percentual' => (float) $cr['pct'], 'regras' => []] : null;
    }

    public function marcarUsado(int $creditoId, int $assinaturaId): void
    {
        $this->pdo->prepare("UPDATE creditos_indicacao SET status = 'usado', usado_assinatura_id = ?, usado_em = NOW() WHERE id = ? AND status = 'disponivel'")
                  ->execute([$assinaturaId, $creditoId]);
    }

    /** Pedido cancelado antes de pagar: o crédito usado nele volta a ficar disponível. */
    public function devolverDoPedido(int $assinaturaId): void
    {
        $this->pdo->prepare("UPDATE creditos_indicacao SET status = 'disponivel', usado_assinatura_id = NULL, usado_em = NULL WHERE usado_assinatura_id = ?")
                  ->execute([$assinaturaId]);
    }

    /** Histórico de créditos da turma (para a tela do plano). */
    public function historico(int $turmaId): array
    {
        $st = $this->pdo->prepare("SELECT * FROM creditos_indicacao WHERE turma_id = ? ORDER BY id DESC");
        $st->execute([$turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Todas as indicações (para o master). */
    public function todas(int $limite = 100): array
    {
        return $this->pdo->query("
            SELECT ci.*, t.nome AS turma_nome, o.nome AS origem_nome
            FROM creditos_indicacao ci INNER JOIN turmas t ON t.id = ci.turma_id LEFT JOIN turmas o ON o.id = ci.origem_turma_id
            ORDER BY ci.id DESC LIMIT " . (int) $limite
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function nomeTurma(int $id): string
    {
        $st = $this->pdo->prepare("SELECT nome FROM turmas WHERE id = ?");
        $st->execute([$id]);
        return (string) $st->fetchColumn();
    }

    private function avisar(int $turmaId, string $titulo, string $msg): void
    {
        $st = $this->pdo->prepare("SELECT usuario_id FROM turma_usuarios WHERE turma_id = ? AND papel = 'representante' AND vice = 0 AND ativo = 1");
        $st->execute([$turmaId]);
        (new Notificacao())->criarParaVarios($st->fetchAll(PDO::FETCH_COLUMN), 'plano', $titulo, $msg, '/turmas/' . $turmaId . '/plano');
    }

    private function fmtPct(float $p): string
    {
        return rtrim(rtrim(number_format($p, 2, ',', ''), '0'), ',') . '%';
    }
}
