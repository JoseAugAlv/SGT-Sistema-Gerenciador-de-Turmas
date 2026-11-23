<?php
// app/Models/Calendario.php
// Calendário da turma/grêmio (entregas, provas, eventos, feriados) e horário semanal das aulas.
require_once __DIR__ . '/../Core/Model.php';

class Calendario extends Model
{
    const TIPOS = [
        'entrega' => ['Entrega de atividade', 'fa-file-arrow-up'],
        'prova'   => ['Prova',                'fa-pen-to-square'],
        'evento'  => ['Evento',               'fa-star'],
        'reuniao' => ['Reunião',              'fa-people-group'],
        'feriado' => ['Feriado / sem aula',   'fa-umbrella-beach'],
        'outro'   => ['Outro',                'fa-thumbtack'],
    ];
    const DIAS = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado', 7 => 'Domingo'];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'eventos_calendario';
    }

    /**
     * Itens entre duas datas: eventos do calendário + reuniões agendadas (que entram como tipo 'reuniao').
     * Cada item: data, hora_inicio, hora_fim, tipo, titulo, descricao, local, id, origem (evento|reuniao).
     */
    public function itensEntre(int $turmaId, string $de, string $ate): array
    {
        $st = $this->conn->prepare("
            SELECT id, 'evento' AS origem, tipo, titulo, descricao, data, hora_inicio, hora_fim, local
            FROM eventos_calendario WHERE turma_id = ? AND data BETWEEN ? AND ?
            UNION ALL
            SELECT id, 'reuniao' AS origem, 'reuniao' AS tipo, titulo, pauta AS descricao, DATE(data) AS data,
                   TIME(data) AS hora_inicio, NULL AS hora_fim, local
            FROM reunioes WHERE turma_id = ? AND status <> 'cancelada' AND DATE(data) BETWEEN ? AND ?
            ORDER BY data, hora_inicio
        ");
        $st->execute([$turmaId, $de, $ate, $turmaId, $de, $ate]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function proximos(int $turmaId, int $limite = 5): array
    {
        return array_slice($this->itensEntre($turmaId, date('Y-m-d'), date('Y-m-d', strtotime('+120 days'))), 0, $limite);
    }

    public function criarEvento(array $d): int
    {
        $this->conn->prepare("
            INSERT INTO eventos_calendario (turma_id, tipo, titulo, descricao, data, hora_inicio, hora_fim, local, criado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$d['turma_id'], $d['tipo'], $d['titulo'], $d['descricao'], $d['data'], $d['hora_inicio'], $d['hora_fim'], $d['local'], $d['criado_por']]);
        return (int) $this->conn->lastInsertId();
    }

    public function evento(int $id): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM eventos_calendario WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function excluirEvento(int $id): void
    {
        $this->conn->prepare("DELETE FROM eventos_calendario WHERE id = ?")->execute([$id]);
    }

    // ============ HORÁRIO DAS AULAS ============

    /** [dia_semana => [aulas em ordem de horário]] */
    public function horarios(int $turmaId): array
    {
        $st = $this->conn->prepare("SELECT * FROM horarios_aula WHERE turma_id = ? ORDER BY dia_semana, inicio");
        $st->execute([$turmaId]);
        $r = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $h) $r[(int) $h['dia_semana']][] = $h;
        return $r;
    }

    public function criarHorario(array $d): int
    {
        $this->conn->prepare("INSERT INTO horarios_aula (turma_id, dia_semana, inicio, fim, disciplina, professor, sala) VALUES (?, ?, ?, ?, ?, ?, ?)")
                   ->execute([$d['turma_id'], $d['dia_semana'], $d['inicio'], $d['fim'], $d['disciplina'], $d['professor'], $d['sala']]);
        return (int) $this->conn->lastInsertId();
    }

    public function horario(int $id): ?array
    {
        $st = $this->conn->prepare("SELECT * FROM horarios_aula WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function excluirHorario(int $id): void
    {
        $this->conn->prepare("DELETE FROM horarios_aula WHERE id = ?")->execute([$id]);
    }
}
