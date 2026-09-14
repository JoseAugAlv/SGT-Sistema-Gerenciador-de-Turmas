<?php
// app/Models/Etapa.php
require_once __DIR__ . '/../Core/Model.php';

class Etapa extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'projeto_etapas';
    }

    public function listarPorProjeto(int $projetoId): array
    {
        $stmt = $this->conn->prepare("
            SELECT e.*,
                   (SELECT COUNT(*) FROM criterios c WHERE c.etapa_id = e.id) AS total_criterios
            FROM projeto_etapas e
            WHERE e.projeto_id = ?
            ORDER BY e.ordem ASC, e.id ASC
        ");
        $stmt->execute([$projetoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM projeto_etapas WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function proximaOrdem(int $projetoId): int
    {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(MAX(ordem), 0) + 1 FROM projeto_etapas WHERE projeto_id = ?
        ");
        $stmt->execute([$projetoId]);
        return (int) $stmt->fetchColumn();
    }

    public function criar(array $d): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO projeto_etapas (projeto_id, nome, descricao, data_inicio, data_fim, ordem)
            VALUES (:projeto_id, :nome, :descricao, :data_inicio, :data_fim, :ordem)
        ");
        $stmt->execute([
            ':projeto_id'  => $d['projeto_id'],
            ':nome'        => $d['nome'],
            ':descricao'   => $d['descricao']   ?? null,
            ':data_inicio' => $d['data_inicio'] ?? null,
            ':data_fim'    => $d['data_fim']    ?? null,
            ':ordem'       => $d['ordem'],
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizar(int $id, array $d): bool
    {
        return $this->conn->prepare("
            UPDATE projeto_etapas SET nome = ?, descricao = ?, data_inicio = ?, data_fim = ?
            WHERE id = ?
        ")->execute([
            $d['nome'], $d['descricao'] ?? null,
            $d['data_inicio'] ?? null, $d['data_fim'] ?? null, $id,
        ]);
    }

    /**
     * Exclui a etapa. Critérios vinculados têm etapa_id setado para NULL
     * (FK ON DELETE SET NULL — ver descricao.md §4.2).
     */
    public function excluir(int $id): bool
    {
        return $this->conn->prepare("DELETE FROM projeto_etapas WHERE id = ?")->execute([$id]);
    }

    /**
     * Troca a ordem com a etapa vizinha (acima ou abaixo).
     * Retorna true se trocou, false se já estava na ponta.
     */
    public function mover(int $id, string $direcao): bool
    {
        $etapa = $this->porId($id);
        if (!$etapa) return false;

        $sinal = $direcao === 'subir' ? '<' : '>';
        $order = $direcao === 'subir' ? 'DESC' : 'ASC';

        $stmt = $this->conn->prepare("
            SELECT * FROM projeto_etapas
            WHERE projeto_id = ? AND ordem {$sinal} ?
            ORDER BY ordem {$order}
            LIMIT 1
        ");
        $stmt->execute([(int) $etapa['projeto_id'], (int) $etapa['ordem']]);
        $vizinha = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$vizinha) return false;

        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("UPDATE projeto_etapas SET ordem = ? WHERE id = ?")
                       ->execute([(int) $vizinha['ordem'], (int) $etapa['id']]);
            $this->conn->prepare("UPDATE projeto_etapas SET ordem = ? WHERE id = ?")
                       ->execute([(int) $etapa['ordem'], (int) $vizinha['id']]);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}