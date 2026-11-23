<?php
// app/Models/Agenda.php
// Agenda do usuário: eventos das turmas (com Dia a dia) + eventos pessoais + reuniões, com conclusão e lembretes.
require_once __DIR__ . '/../Core/Model.php';

class Agenda extends Model
{
    const TIPOS = [
        'prova'   => ['Prova',     'fa-pen-to-square'],
        'entrega' => ['Entrega',   'fa-file-arrow-up'],
        'tarefa'  => ['Tarefa',    'fa-list-check'],
        'estudo'  => ['Estudo',    'fa-book-open'],
        'reuniao' => ['Reunião',   'fa-people-group'],
        'evento'  => ['Evento',    'fa-star'],
        'feriado' => ['Feriado',   'fa-umbrella-beach'],
        'outro'   => ['Outro',     'fa-thumbtack'],
    ];
    const PRIORIDADES = [
        'baixa'   => ['Baixa',   '#29946e'],
        'media'   => ['Média',   '#1c6fd6'],
        'alta'    => ['Alta',    '#e08a00'],
        'urgente' => ['Urgente', '#d63548'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'eventos_calendario';
    }

    /**
     * Itens visíveis para o usuário entre duas datas.
     * $turmas: [turma_id => ['nome', 'pode_editar']] apenas das turmas com Dia a dia.
     */
    public function itens(int $uid, array $turmas, string $de, string $ate, ?int $soTurma = null): array
    {
        $ids = array_map('intval', array_keys($turmas));
        if ($soTurma !== null) $ids = array_values(array_intersect($ids, [$soTurma]));
        $marc = $ids ? implode(',', $ids) : '0';

        $st = $this->conn->prepare("
            SELECT e.*, u.nome AS criador_nome, t.nome AS turma_nome,
                   EXISTS(SELECT 1 FROM evento_conclusoes c WHERE c.evento_id = e.id AND c.usuario_id = ?) AS concluido,
                   (SELECT COUNT(*) FROM estudo_materiais m WHERE m.evento_id = e.id) AS materiais
            FROM eventos_calendario e
            LEFT JOIN usuarios u ON u.id = e.criado_por
            LEFT JOIN turmas t ON t.id = e.turma_id
            WHERE e.data BETWEEN ? AND ?
              AND ( (e.visibilidade = 'turma' AND e.turma_id IN ($marc))
                 OR (e.visibilidade = 'pessoal' AND e.criado_por = ?" . ($soTurma !== null ? " AND e.turma_id = " . (int) $soTurma : '') . ") )
            ORDER BY e.data, e.hora_inicio IS NOT NULL, e.hora_inicio
        ");
        $st->execute([$uid, $de, $ate, $uid]);
        $itens = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $e) {
            $ehTurma = $e['visibilidade'] === 'turma';
            $itens[] = [
                'id' => (int) $e['id'], 'origem' => 'evento', 'titulo' => $e['titulo'], 'tipo' => $e['tipo'],
                'prioridade' => $e['prioridade'], 'data' => $e['data'],
                'inicio' => $e['hora_inicio'] ? substr($e['hora_inicio'], 0, 5) : null,
                'fim' => $e['hora_fim'] ? substr($e['hora_fim'], 0, 5) : null,
                'local' => $e['local'], 'descricao' => $e['descricao'], 'lembrete' => (bool) $e['lembrete'],
                'turma_id' => $e['turma_id'] ? (int) $e['turma_id'] : null, 'turma_nome' => $e['turma_nome'],
                'visibilidade' => $e['visibilidade'], 'concluido' => (bool) $e['concluido'],
                'materiais' => (int) $e['materiais'],
                'criador' => (int) $e['criado_por'] === $uid ? 'Você' : $e['criador_nome'],
                'pode_editar' => $ehTurma ? !empty($turmas[(int) $e['turma_id']]['pode_editar']) : (int) $e['criado_por'] === $uid,
            ];
        }

        // Reuniões agendadas das turmas (entram como tipo reunião, abrem na tela da reunião)
        if ($ids) {
            $st = $this->conn->prepare("
                SELECT r.id, r.titulo, r.data, r.local, r.pauta, r.turma_id, t.nome AS turma_nome
                FROM reunioes r INNER JOIN turmas t ON t.id = r.turma_id
                WHERE r.turma_id IN ($marc) AND r.status <> 'cancelada' AND DATE(r.data) BETWEEN ? AND ?
            ");
            $st->execute([$de, $ate]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $itens[] = [
                    'id' => (int) $r['id'], 'origem' => 'reuniao', 'titulo' => $r['titulo'], 'tipo' => 'reuniao',
                    'prioridade' => 'media', 'data' => substr($r['data'], 0, 10), 'inicio' => substr($r['data'], 11, 5), 'fim' => null,
                    'local' => $r['local'], 'descricao' => $r['pauta'], 'lembrete' => true,
                    'turma_id' => (int) $r['turma_id'], 'turma_nome' => $r['turma_nome'], 'visibilidade' => 'turma',
                    'concluido' => false, 'criador' => null, 'pode_editar' => false,
                ];
            }
        }
        usort($itens, fn($a, $b) => [$a['data'], $a['inicio'] ?? '00:00'] <=> [$b['data'], $b['inicio'] ?? '00:00']);
        return $itens;
    }

    public function porId(int $id): ?array
    {
        $st = $this->conn->prepare("SELECT e.*, t.nome AS turma_nome FROM eventos_calendario e LEFT JOIN turmas t ON t.id = e.turma_id WHERE e.id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function salvar(?int $id, array $d): int
    {
        $campos = ['turma_id', 'visibilidade', 'tipo', 'prioridade', 'titulo', 'descricao', 'data', 'hora_inicio', 'hora_fim', 'local', 'lembrete'];
        $vals = array_map(fn($c) => $d[$c], $campos);
        if ($id) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", $campos));
            $this->conn->prepare("UPDATE eventos_calendario SET $set WHERE id = ?")->execute([...$vals, $id]);
            return $id;
        }
        $this->conn->prepare("INSERT INTO eventos_calendario (" . implode(', ', $campos) . ", criado_por) VALUES (" . rtrim(str_repeat('?, ', count($campos)), ', ') . ", ?)")
                   ->execute([...$vals, $d['criado_por']]);
        return (int) $this->conn->lastInsertId();
    }

    public function excluir(int $id): void
    {
        $this->conn->prepare("DELETE FROM eventos_calendario WHERE id = ?")->execute([$id]);
    }

    /** Marca/desmarca como feito para este usuário. Retorna o novo estado. */
    public function alternarConclusao(int $eventoId, int $uid): bool
    {
        $st = $this->conn->prepare("DELETE FROM evento_conclusoes WHERE evento_id = ? AND usuario_id = ?");
        $st->execute([$eventoId, $uid]);
        if ($st->rowCount()) return false;
        $this->conn->prepare("INSERT INTO evento_conclusoes (evento_id, usuario_id) VALUES (?, ?)")->execute([$eventoId, $uid]);
        return true;
    }

    /** Eventos de amanhã (com lembrete, não concluídos) ainda sem lembrete enviado a este usuário. */
    public function lembretesPendentes(int $uid, array $turmas): array
    {
        $amanha = date('Y-m-d', strtotime('+1 day'));
        $pend = array_filter($this->itens($uid, $turmas, $amanha, $amanha),
            fn($i) => $i['origem'] === 'evento' && $i['lembrete'] && !$i['concluido']);
        if (!$pend) return [];
        $st = $this->conn->prepare("SELECT referencia_id FROM notificacoes WHERE usuario_id = ? AND tipo = 'lembrete' AND created_at > NOW() - INTERVAL 3 DAY");
        $st->execute([$uid]);
        $ja = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        return array_values(array_filter($pend, fn($i) => !in_array($i['id'], $ja, true)));
    }
}
