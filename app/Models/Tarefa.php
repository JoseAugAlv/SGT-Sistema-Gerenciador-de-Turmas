<?php
// app/Models/Tarefa.php
// Kanban de tarefas do grupo.
require_once __DIR__ . '/../Core/Model.php';

class Tarefa extends Model
{
    const COLUNAS = [
        'a_fazer' => ['A fazer', 'fa-list'],
        'fazendo' => ['Fazendo', 'fa-person-running'],
        'revisao' => ['Revisão', 'fa-magnifying-glass'],
        'feito'   => ['Feito', 'fa-circle-check'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'grupo_tarefas';
    }

    /** Tarefas do grupo agrupadas por coluna. */
    public function quadro(int $grupoId): array
    {
        $st = $this->conn->prepare("
            SELECT t.*, u.nome AS responsavel, a.titulo AS ata_titulo
            FROM grupo_tarefas t
            LEFT JOIN usuarios u ON u.id = t.responsavel_id
            LEFT JOIN atas a ON a.id = t.ata_id
            WHERE t.grupo_id = ?
            ORDER BY t.ordem, t.id
        ");
        $st->execute([$grupoId]);
        $q = array_fill_keys(array_keys(self::COLUNAS), []);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $t) $q[$t['coluna']][] = $t;
        return $q;
    }

    /** Tarefas concluídas e em aberto por integrante (ajuda na avaliação). */
    public function participacao(int $grupoId): array
    {
        $st = $this->conn->prepare("
            SELECT u.id, u.nome, SUM(t.coluna = 'feito') AS feitas, SUM(t.coluna <> 'feito') AS abertas
            FROM grupo_tarefas t INNER JOIN usuarios u ON u.id = t.responsavel_id
            WHERE t.grupo_id = ? GROUP BY u.id, u.nome ORDER BY feitas DESC, u.nome
        ");
        $st->execute([$grupoId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM grupo_tarefas WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function criar(array $d): int
    {
        $max = $this->conn->prepare("SELECT COALESCE(MAX(ordem), 0) + 1 FROM grupo_tarefas WHERE grupo_id = ? AND coluna = 'a_fazer'");
        $max->execute([$d['grupo_id']]);
        $this->conn->prepare("INSERT INTO grupo_tarefas (grupo_id, titulo, descricao, responsavel_id, prazo, ata_id, criado_por, ordem) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                   ->execute([$d['grupo_id'], $d['titulo'], $d['descricao'], $d['responsavel_id'], $d['prazo'], $d['ata_id'], $d['criado_por'], (int) $max->fetchColumn()]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizar(int $id, array $d): void
    {
        $this->conn->prepare("UPDATE grupo_tarefas SET titulo = ?, descricao = ?, responsavel_id = ?, prazo = ?, ata_id = ? WHERE id = ?")
                   ->execute([$d['titulo'], $d['descricao'], $d['responsavel_id'], $d['prazo'], $d['ata_id'], $id]);
    }

    /** Move para a coluna e reordena a coluna conforme $ordemIds (ids na nova ordem). */
    public function mover(int $id, int $grupoId, string $coluna, array $ordemIds): void
    {
        $this->conn->beginTransaction();
        try {
            $this->conn->prepare("UPDATE grupo_tarefas SET coluna = ?, concluida_em = IF(? = 'feito', COALESCE(concluida_em, NOW()), NULL) WHERE id = ? AND grupo_id = ?")
                       ->execute([$coluna, $coluna, $id, $grupoId]);
            $up = $this->conn->prepare("UPDATE grupo_tarefas SET ordem = ? WHERE id = ? AND grupo_id = ? AND coluna = ?");
            foreach (array_values($ordemIds) as $i => $tid) $up->execute([$i + 1, (int) $tid, $grupoId, $coluna]);
            $this->conn->commit();
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): void
    {
        $this->conn->prepare("DELETE FROM grupo_tarefas WHERE id = ?")->execute([$id]);
    }

    /** Atas do grupo (para ligar a tarefa). */
    public function atasDoGrupo(int $grupoId): array
    {
        $st = $this->conn->prepare("SELECT id, titulo, data_ata FROM atas WHERE grupo_id = ? ORDER BY data_ata DESC, id DESC LIMIT 30");
        $st->execute([$grupoId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
