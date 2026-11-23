<?php
// app/Models/Caixa.php
// Controle de caixa da turma/grêmio: lançamentos, saldo, resumo por mês e contribuições por pessoa.
require_once __DIR__ . '/../Core/Model.php';

class Caixa extends Model
{
    const CATEGORIAS = [
        'entrada' => ['Contribuição', 'Rifa / venda', 'Doação', 'Evento', 'Outros'],
        'saida'   => ['Material', 'Lanche / festa', 'Transporte', 'Impressão', 'Presente', 'Outros'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'caixa_lancamentos';
    }

    /** Lançamentos (filtro opcional por mês 'Y-m'), mais recentes primeiro. */
    public function listar(int $turmaId, ?string $mes = null): array
    {
        $sql = "SELECT l.*, p.nome AS pessoa, r.nome AS registrado, d.nome AS diretoria, pj.nome AS projeto
                FROM caixa_lancamentos l
                LEFT JOIN usuarios p ON p.id = l.pessoa_id
                LEFT JOIN usuarios r ON r.id = l.registrado_por
                LEFT JOIN gremio_diretorias d ON d.id = l.diretoria_id
                LEFT JOIN projetos pj ON pj.id = l.projeto_id
                WHERE l.turma_id = ?" . ($mes ? " AND DATE_FORMAT(l.data, '%Y-%m') = ?" : '') . "
                ORDER BY l.data DESC, l.id DESC LIMIT 500";
        $st = $this->conn->prepare($sql);
        $st->execute($mes ? [$turmaId, $mes] : [$turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** ['entradas', 'saidas', 'saldo'] de tudo, ou até um mês. */
    public function totais(int $turmaId, ?string $mes = null): array
    {
        $sql = "SELECT COALESCE(SUM(IF(tipo = 'entrada', valor, 0)), 0) e, COALESCE(SUM(IF(tipo = 'saida', valor, 0)), 0) s
                FROM caixa_lancamentos WHERE turma_id = ?" . ($mes ? " AND DATE_FORMAT(data, '%Y-%m') = ?" : '');
        $st = $this->conn->prepare($sql);
        $st->execute($mes ? [$turmaId, $mes] : [$turmaId]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return ['entradas' => (float) $r['e'], 'saidas' => (float) $r['s'], 'saldo' => (float) $r['e'] - (float) $r['s']];
    }

    /** Últimos N meses: [['mes' => 'Y-m', 'entradas', 'saidas'], ...] (meses sem lançamento entram com zero). */
    public function porMes(int $turmaId, int $meses = 6): array
    {
        $st = $this->conn->prepare("
            SELECT DATE_FORMAT(data, '%Y-%m') m, SUM(IF(tipo = 'entrada', valor, 0)) e, SUM(IF(tipo = 'saida', valor, 0)) s
            FROM caixa_lancamentos WHERE turma_id = ? AND data >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL ? MONTH)
            GROUP BY m
        ");
        $st->execute([$turmaId, $meses - 1]);
        $dados = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $dados[$r['m']] = $r;
        $out = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $m = date('Y-m', strtotime(date('Y-m-01') . " -{$i} months"));
            $out[] = ['mes' => $m, 'entradas' => (float) ($dados[$m]['e'] ?? 0), 'saidas' => (float) ($dados[$m]['s'] ?? 0)];
        }
        return $out;
    }

    /** Quanto cada pessoa já contribuiu (entradas com pessoa). */
    public function contribuicoes(int $turmaId): array
    {
        $st = $this->conn->prepare("
            SELECT u.id, u.nome, SUM(l.valor) total, COUNT(*) vezes, MAX(l.data) ultima
            FROM caixa_lancamentos l INNER JOIN usuarios u ON u.id = l.pessoa_id
            WHERE l.turma_id = ? AND l.tipo = 'entrada' GROUP BY u.id, u.nome ORDER BY total DESC
        ");
        $st->execute([$turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM caixa_lancamentos WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function criar(array $d): int
    {
        $this->conn->prepare("
            INSERT INTO caixa_lancamentos (turma_id, tipo, valor, descricao, categoria, data, pessoa_id, diretoria_id, comprovante, comprovante_nome, registrado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$d['turma_id'], $d['tipo'], $d['valor'], $d['descricao'], $d['categoria'], $d['data'], $d['pessoa_id'], $d['diretoria_id'],
                     $d['comprovante'], $d['comprovante_nome'], $d['registrado_por']]);
        return (int) $this->conn->lastInsertId();
    }

    public function excluir(int $id): void
    {
        $this->conn->prepare("DELETE FROM caixa_lancamentos WHERE id = ?")->execute([$id]);
    }
}
