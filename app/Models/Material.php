<?php
// app/Models/Material.php
require_once __DIR__ . '/../Core/Model.php';

class Material extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'materiais';
    }

    public function listarPorProjeto(int $projetoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT m.*,
                   (SELECT COUNT(*) FROM movimentacoes_materiais mv WHERE mv.material_id = m.id) AS total_movimentacoes
            FROM materiais m
            WHERE m.projeto_id = ?
            ORDER BY m.nome ASC
        ");
        $stmt->execute([$projetoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT m.*, p.nome AS projeto_nome, p.turma_id
            FROM materiais m
            INNER JOIN projetos p ON p.id = m.projeto_id
            WHERE m.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function criar(array $d): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO materiais (nome, preco, quantidade, unidade, projeto_id)
            VALUES (:nome, :preco, :qtd, :unidade, :projeto_id)
        ");
        $stmt->execute([
            ':nome'       => $d['nome'],
            ':preco'      => $d['preco'],
            ':qtd'        => $d['quantidade'],
            ':unidade'    => $d['unidade'] ?? null,
            ':projeto_id' => $d['projeto_id'],
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /**
     * Registra compra com preço médio ponderado.
     * Retorna [novo_preco, nova_qtd].
     */
    public function comprar(int $id, float $qtdComprada, float $precoCompra): array
    {
        $m = $this->porId($id);
        if (!$m) return [0, 0];

        $qtdAtual   = (float) $m['quantidade'];
        $precoAtual = (float) $m['preco'];

        $valorAtual  = $qtdAtual   * $precoAtual;
        $valorCompra = $qtdComprada * $precoCompra;
        $novaQtd     = $qtdAtual + $qtdComprada;

        $novoPreco = $novaQtd > 0
            ? ($valorAtual + $valorCompra) / $novaQtd
            : $precoCompra;

        $this->conn->prepare("
            UPDATE materiais SET quantidade = ?, preco = ? WHERE id = ?
        ")->execute([$novaQtd, $novoPreco, $id]);

        return [$novoPreco, $novaQtd];
    }

    public function usar(int $id, float $qtdUsada): bool
    {
        $m = $this->porId($id);
        if (!$m) return false;
        if ($qtdUsada > (float) $m['quantidade']) return false;

        $novaQtd = (float) $m['quantidade'] - $qtdUsada;
        return $this->conn->prepare("UPDATE materiais SET quantidade = ? WHERE id = ?")
                          ->execute([$novaQtd, $id]);
    }

    public function excluir(int $id): bool
    {
        return $this->conn->prepare("DELETE FROM materiais WHERE id = ?")->execute([$id]);
    }
}