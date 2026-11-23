<?php
// app/Services/MetricasService.php
// Números do painel de métricas do master: receita, turmas e planos, usuários ativos, uso dos recursos,
// conversão, renovações e funil das escolas. Só leitura.
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Helpers/PlanoGuard.php';

class MetricasService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function um(string $sql, array $p = [])
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchColumn();
    }

    private function todos(string $sql, array $p = []): array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function montar(): array
    {
        // ---------- receita
        $mes = date('Y-m');
        $receitaMes = (float) $this->um("SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE status = 'pago' AND metodo <> 'cortesia' AND DATE_FORMAT(pago_em, '%Y-%m') = ?", [$mes]);
        $receitaMesAnt = (float) $this->um("SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE status = 'pago' AND metodo <> 'cortesia' AND DATE_FORMAT(pago_em, '%Y-%m') = ?", [date('Y-m', strtotime('first day of last month'))]);
        $porMes = [];
        foreach ($this->todos("SELECT DATE_FORMAT(pago_em, '%Y-%m') m, SUM(valor) v FROM pagamentos WHERE status = 'pago' AND metodo <> 'cortesia'
                               AND pago_em >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH) GROUP BY m") as $r) $porMes[$r['m']] = (float) $r['v'];
        $serie = [];
        for ($i = 11; $i >= 0; $i--) { $m = date('Y-m', strtotime(date('Y-m-01') . " -{$i} months")); $serie[] = ['mes' => $m, 'valor' => $porMes[$m] ?? 0]; }
        $aReceber = (float) $this->um("SELECT COALESCE(SUM(p.valor), 0) FROM pagamentos p INNER JOIN assinaturas a ON a.id = p.assinatura_id
                                        WHERE p.status = 'pendente' AND a.status IN ('ativa','aguardando') AND p.vencimento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        $atrasado = (float) $this->um("SELECT COALESCE(SUM(p.valor), 0) FROM pagamentos p INNER JOIN assinaturas a ON a.id = p.assinatura_id
                                        WHERE p.status = 'pendente' AND a.status = 'ativa' AND p.vencimento < CURDATE()");
        $total = (float) $this->um("SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE status = 'pago' AND metodo <> 'cortesia'");

        // ---------- turmas por situação do plano
        $sit = ['ativa' => 0, 'vencendo' => 0, 'pendencia' => 0, 'sem_plano' => 0, 'isenta' => 0];
        foreach ($this->todos("SELECT * FROM turmas") as $t) {
            $c = PlanoGuard::situacao($t)['codigo'];
            if ($c === 'isenta') $sit['isenta']++;
            elseif ($c === 'ativa') $sit['ativa']++;
            elseif ($c === 'vencendo') $sit['vencendo']++;
            elseif (in_array($c, ['tolerancia', 'atraso', 'atraso_bloqueio', 'vencido'], true)) $sit['pendencia']++;
            else $sit['sem_plano']++;
        }

        // ---------- planos vendidos (assinaturas que chegaram a ser pagas)
        $planos = $this->todos("SELECT p.nome, p.tipo, COUNT(*) qtd, SUM(a.valor_total) valor FROM assinaturas a INNER JOIN planos p ON p.id = a.plano_id
                                WHERE a.status IN ('ativa','trocada','renovada') GROUP BY p.id, p.nome, p.tipo ORDER BY qtd DESC");

        // ---------- conversão e renovação
        $criadas30 = (int) $this->um("SELECT COUNT(*) FROM turmas WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND isenta_plano = 0");
        $pagaram30 = (int) $this->um("SELECT COUNT(DISTINCT t.id) FROM turmas t INNER JOIN assinaturas a ON a.turma_id = t.id
                                      WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND t.isenta_plano = 0 AND a.status IN ('ativa','trocada','renovada')");
        $renovadas = (int) $this->um("SELECT COUNT(*) FROM assinaturas WHERE renovacao_de IS NOT NULL AND status IN ('ativa','renovada')");
        $venceram = (int) $this->um("SELECT COUNT(*) FROM assinaturas WHERE status IN ('ativa','renovada') AND fim IS NOT NULL AND fim < NOW()");

        // ---------- usuários
        $usuarios = [
            'total'  => (int) $this->um("SELECT COUNT(*) FROM usuarios WHERE tipo <> 'master'"),
            'dia'    => (int) $this->um("SELECT COUNT(*) FROM usuarios WHERE tipo <> 'master' AND ultimo_acesso >= DATE_SUB(NOW(), INTERVAL 1 DAY)"),
            'semana' => (int) $this->um("SELECT COUNT(*) FROM usuarios WHERE tipo <> 'master' AND ultimo_acesso >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
            'mes'    => (int) $this->um("SELECT COUNT(*) FROM usuarios WHERE tipo <> 'master' AND ultimo_acesso >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
            'novos'  => (int) $this->um("SELECT COUNT(*) FROM usuarios WHERE tipo <> 'master' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
        ];

        // ---------- uso dos recursos (últimos 30 dias)
        $d30 = "DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $uso = [
            'Projetos'       => (int) $this->um("SELECT COUNT(*) FROM projetos WHERE created_at >= $d30"),
            'Atas'           => (int) $this->um("SELECT COUNT(*) FROM atas WHERE created_at >= $d30"),
            'Avisos'         => (int) $this->um("SELECT COUNT(*) FROM alertas WHERE created_at >= $d30"),
            'Eventos na agenda' => (int) $this->um("SELECT COUNT(*) FROM eventos_calendario WHERE created_at >= $d30"),
            'Reuniões'       => (int) $this->um("SELECT COUNT(*) FROM reunioes WHERE created_at >= $d30"),
            'Enquetes'       => (int) $this->um("SELECT COUNT(*) FROM enquetes WHERE created_at >= $d30"),
            'Tarefas (Kanban)' => (int) $this->um("SELECT COUNT(*) FROM grupo_tarefas WHERE created_at >= $d30"),
            'Mural'          => (int) $this->um("SELECT COUNT(*) FROM mural_posts WHERE created_at >= $d30"),
            'Materiais de estudo' => (int) $this->um("SELECT COUNT(*) FROM estudo_materiais WHERE created_at >= $d30"),
            'Lançamentos no caixa' => (int) $this->um("SELECT COUNT(*) FROM caixa_lancamentos WHERE created_at >= $d30"),
            'Mensagens na caixinha' => (int) $this->um("SELECT COUNT(*) FROM caixinha_mensagens WHERE created_at >= $d30"),
        ];
        arsort($uso);

        // ---------- cupons e escolas
        $cupons = $this->todos("SELECT codigo, origem, usos, usos_max FROM cupons WHERE usos > 0 ORDER BY usos DESC LIMIT 6");
        $escolas = [];
        foreach ($this->todos("SELECT status, COUNT(*) n, COALESCE(SUM(turmas), 0) t FROM escola_contatos GROUP BY status") as $r) $escolas[$r['status']] = $r;
        $testeDia = ['ativos' => (int) $this->um("SELECT COUNT(*) FROM turmas WHERE teste_dia_ate > NOW()"), 'usados' => (int) $this->um("SELECT COUNT(*) FROM turmas WHERE teste_dia_ate IS NOT NULL")];

        return compact('receitaMes', 'receitaMesAnt', 'serie', 'aReceber', 'atrasado', 'total', 'sit', 'planos', 'criadas30', 'pagaram30',
                       'renovadas', 'venceram', 'usuarios', 'uso', 'cupons', 'escolas', 'testeDia');
    }
}
