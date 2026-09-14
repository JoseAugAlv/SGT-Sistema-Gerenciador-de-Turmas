<?php
// app/Models/MovimentacaoMaterial.php
require_once __DIR__ . '/../Core/Model.php';

class MovimentacaoMaterial extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'movimentacoes_materiais';
    }

    public function registrar(
        int $materialId,
        int $usuarioId,
        string $tipo,
        float $qtd,
        float $precoUnit,
        ?string $obs = null
    ): int {
        $stmt = $this->conn->prepare("
            INSERT INTO movimentacoes_materiais
                (material_id, usuario_id, tipo_movimentacao, quantidade, preco_unitario, observacao)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$materialId, $usuarioId, $tipo, $qtd, $precoUnit, $obs]);
        return (int) $this->conn->lastInsertId();
    }

    public function listarPorProjeto(int $projetoId, array $filtros = []): array
    {
        $sql = "
            SELECT mv.*,
                   m.nome AS material_nome, m.unidade,
                   u.nome AS usuario_nome,
                   tu.papel AS usuario_papel
            FROM movimentacoes_materiais mv
            INNER JOIN materiais m ON m.id = mv.material_id
            INNER JOIN usuarios u ON u.id = mv.usuario_id
            LEFT JOIN turma_usuarios tu
                   ON tu.usuario_id = u.id
                  AND tu.turma_id = (SELECT turma_id FROM projetos WHERE id = m.projeto_id)
                  AND tu.ativo = 1
            WHERE m.projeto_id = ?
        ";
        $params = [$projetoId];

        if (!empty($filtros['material_id'])) {
            $sql .= " AND mv.material_id = ?";
            $params[] = (int) $filtros['material_id'];
        }
        if (!empty($filtros['tipo'])) {
            $sql .= " AND mv.tipo_movimentacao = ?";
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['de'])) {
            $sql .= " AND mv.created_at >= ?";
            $params[] = $filtros['de'] . ' 00:00:00';
        }
        if (!empty($filtros['ate'])) {
            $sql .= " AND mv.created_at <= ?";
            $params[] = $filtros['ate'] . ' 23:59:59';
        }

        $sql .= " ORDER BY mv.created_at DESC, mv.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}