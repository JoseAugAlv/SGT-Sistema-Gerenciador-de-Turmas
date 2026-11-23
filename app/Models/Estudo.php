<?php
// app/Models/Estudo.php
// Banco de materiais de estudo da turma, por disciplina, ligado (opcionalmente) a provas/entregas da agenda.
require_once __DIR__ . '/../Core/Model.php';

class Estudo extends Model
{
    const TIPOS = [
        'arquivo' => ['Arquivo', 'fa-file-arrow-down'],
        'link'    => ['Link', 'fa-link'],
        'resumo'  => ['Resumo', 'fa-file-lines'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'estudo_materiais';
    }

    /** Materiais da turma (filtros opcionais: disciplina, evento, busca). */
    public function listar(int $turmaId, ?string $disciplina = null, ?int $eventoId = null, string $busca = ''): array
    {
        $sql = "SELECT m.*, u.nome AS autor, e.titulo AS evento_titulo, e.data AS evento_data, e.tipo AS evento_tipo
                FROM estudo_materiais m
                LEFT JOIN usuarios u ON u.id = m.autor_id
                LEFT JOIN eventos_calendario e ON e.id = m.evento_id
                WHERE m.turma_id = ?";
        $p = [$turmaId];
        if ($disciplina !== null && $disciplina !== '') { $sql .= " AND m.disciplina = ?"; $p[] = $disciplina; }
        if ($eventoId) { $sql .= " AND m.evento_id = ?"; $p[] = $eventoId; }
        if ($busca !== '') { $sql .= " AND (m.titulo LIKE ? OR m.texto LIKE ? OR m.disciplina LIKE ?)"; $b = '%' . $busca . '%'; array_push($p, $b, $b, $b); }
        $st = $this->conn->prepare($sql . " ORDER BY m.created_at DESC LIMIT 200");
        $st->execute($p);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Disciplinas da turma: as do horário de aulas + as já usadas nos materiais, com a contagem. */
    public function disciplinas(int $turmaId): array
    {
        $st = $this->conn->prepare("
            SELECT d, SUM(n) AS n FROM (
                SELECT TRIM(disciplina) AS d, 0 AS n FROM horarios_aula WHERE turma_id = ? AND TRIM(disciplina) <> ''
                UNION ALL
                SELECT disciplina, 1 FROM estudo_materiais WHERE turma_id = ?
            ) x GROUP BY d ORDER BY d
        ");
        $st->execute([$turmaId, $turmaId]);
        return array_map(fn($r) => ['nome' => $r['d'], 'total' => (int) $r['n']], $st->fetchAll(PDO::FETCH_ASSOC));
    }

    /** Provas, entregas e tarefas da turma de hoje em diante (para ligar o material). */
    public function proximosEventos(int $turmaId): array
    {
        $st = $this->conn->prepare("
            SELECT e.id, e.titulo, e.data, e.tipo,
                   (SELECT COUNT(*) FROM estudo_materiais m WHERE m.evento_id = e.id) AS materiais
            FROM eventos_calendario e
            WHERE e.turma_id = ? AND e.visibilidade = 'turma' AND e.tipo IN ('prova','entrega','tarefa') AND e.data >= CURDATE()
            ORDER BY e.data, e.hora_inicio LIMIT 30
        ");
        $st->execute([$turmaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function evento(int $id, int $turmaId): ?array
    {
        $st = $this->conn->prepare("SELECT id, titulo, data, tipo FROM eventos_calendario WHERE id = ? AND turma_id = ? AND visibilidade = 'turma'");
        $st->execute([$id, $turmaId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function porId(int $id): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM estudo_materiais WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function criar(array $d): int
    {
        $this->conn->prepare("
            INSERT INTO estudo_materiais (turma_id, autor_id, disciplina, titulo, tipo, arquivo, nome_original, url, texto, evento_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$d['turma_id'], $d['autor_id'], $d['disciplina'], $d['titulo'], $d['tipo'], $d['arquivo'], $d['nome_original'], $d['url'], $d['texto'], $d['evento_id']]);
        return (int) $this->conn->lastInsertId();
    }

    public function excluir(int $id): void
    {
        $this->conn->prepare("DELETE FROM estudo_materiais WHERE id = ?")->execute([$id]);
    }
}
