<?php
// app/Models/Orcamento.php
// Orçamento dos projetos (itens previstos x lançamentos do caixa ligados ao projeto) e prestação de contas
// da turma/grêmio por diretoria e por projeto.
require_once __DIR__ . '/../Core/Model.php';

class Orcamento extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'projeto_orcamento_itens';
    }

    public function itens(int $projetoId): array
    {
        $st = $this->conn->prepare("SELECT * FROM projeto_orcamento_itens WHERE projeto_id = ? ORDER BY id");
        $st->execute([$projetoId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adicionarItem(int $projetoId, string $descricao, float $valor): void
    {
        $this->conn->prepare("INSERT INTO projeto_orcamento_itens (projeto_id, descricao, valor_previsto) VALUES (?, ?, ?)")->execute([$projetoId, $descricao, $valor]);
    }

    public function removerItem(int $itemId, int $projetoId): void
    {
        $this->conn->prepare("DELETE FROM projeto_orcamento_itens WHERE id = ? AND projeto_id = ?")->execute([$itemId, $projetoId]);
    }

    /** Lançamentos do caixa ligados ao projeto. */
    public function lancamentos(int $projetoId): array
    {
        $st = $this->conn->prepare("
            SELECT l.*, u.nome AS registrado FROM caixa_lancamentos l LEFT JOIN usuarios u ON u.id = l.registrado_por
            WHERE l.projeto_id = ? ORDER BY l.data DESC, l.id DESC
        ");
        $st->execute([$projetoId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** ['previsto', 'arrecadado', 'gasto', 'saldo', 'sem_comprovante'] do projeto. */
    public function resumo(int $projetoId): array
    {
        $st = $this->conn->prepare("
            SELECT (SELECT COALESCE(SUM(valor_previsto), 0) FROM projeto_orcamento_itens WHERE projeto_id = ?) previsto,
                   COALESCE(SUM(IF(tipo = 'entrada', valor, 0)), 0) arrecadado,
                   COALESCE(SUM(IF(tipo = 'saida', valor, 0)), 0) gasto,
                   COALESCE(SUM(tipo = 'saida' AND comprovante IS NULL), 0) sem_comprovante
            FROM caixa_lancamentos WHERE projeto_id = ?
        ");
        $st->execute([$projetoId, $projetoId]);
        $r = array_map('floatval', $st->fetch(PDO::FETCH_ASSOC));
        $r['saldo'] = $r['arrecadado'] - $r['gasto'];
        $r['sem_comprovante'] = (int) $r['sem_comprovante'];
        return $r;
    }

    /** Prestação de contas da turma: uma linha por projeto (com a diretoria), mais o caixa geral sem projeto. */
    public function prestacao(int $turmaId): array
    {
        $st = $this->conn->prepare("
            SELECT p.id, p.nome, p.encerrado, d.id AS diretoria_id, d.nome AS diretoria,
                   (SELECT COALESCE(SUM(valor_previsto), 0) FROM projeto_orcamento_itens i WHERE i.projeto_id = p.id) previsto,
                   (SELECT COALESCE(SUM(valor), 0) FROM caixa_lancamentos l WHERE l.projeto_id = p.id AND l.tipo = 'entrada') arrecadado,
                   (SELECT COALESCE(SUM(valor), 0) FROM caixa_lancamentos l WHERE l.projeto_id = p.id AND l.tipo = 'saida') gasto,
                   (SELECT COUNT(*) FROM caixa_lancamentos l WHERE l.projeto_id = p.id AND l.tipo = 'saida' AND l.comprovante IS NULL) sem_comprovante,
                   (SELECT COUNT(*) FROM caixa_lancamentos l WHERE l.projeto_id = p.id) lancamentos
            FROM projetos p LEFT JOIN gremio_diretorias d ON d.id = p.diretoria_id
            WHERE p.turma_id = ? ORDER BY d.nome IS NULL, d.nome, p.nome
        ");
        $st->execute([$turmaId]);
        $projetos = $st->fetchAll(PDO::FETCH_ASSOC);
        $g = $this->conn->prepare("
            SELECT COALESCE(SUM(IF(tipo = 'entrada', valor, 0)), 0) arrecadado, COALESCE(SUM(IF(tipo = 'saida', valor, 0)), 0) gasto,
                   COALESCE(SUM(tipo = 'saida' AND comprovante IS NULL), 0) sem_comprovante, COUNT(*) lancamentos
            FROM caixa_lancamentos WHERE turma_id = ? AND projeto_id IS NULL
        ");
        $g->execute([$turmaId]);
        return ['projetos' => $projetos, 'geral' => $g->fetch(PDO::FETCH_ASSOC)];
    }

    public function definirDiretoria(int $projetoId, ?int $diretoriaId): void
    {
        $this->conn->prepare("UPDATE projetos SET diretoria_id = ? WHERE id = ?")->execute([$diretoriaId, $projetoId]);
    }
}
