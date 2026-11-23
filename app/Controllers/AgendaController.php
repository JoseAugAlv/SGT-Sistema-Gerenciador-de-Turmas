<?php
// app/Controllers/AgendaController.php
// Agenda interativa: página própria (/agenda), widget da página inicial e calendário da turma usam esta API.
// Evento da turma: só representante/vice/master criam, todos veem. Evento pessoal: qualquer um cria, só ele vê.
// Disponível nas turmas com "Dia a dia" (Anual para cima, ou adicional nos planos menores).
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Agenda.php';
require_once __DIR__ . '/../Models/Calendario.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Helpers/PlanoGuard.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Helpers/RespostaHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class AgendaController
{
    private Agenda $agenda;

    public function __construct()
    {
        $this->agenda = new Agenda();
    }

    // ============ PÁGINA ============

    public function pagina()
    {
        $ctx = self::contexto();
        self::gerarLembretes($ctx);
        $this->render('agenda/index', ['ctx' => $ctx, 'soTurma' => null]);
    }

    // ============ API (JSON) ============

    public function eventos()
    {
        $ctx = self::contexto();
        $de  = $this->data($_GET['de'] ?? '') ?? date('Y-m-01');
        $ate = $this->data($_GET['ate'] ?? '') ?? date('Y-m-t');
        if (strtotime($ate) - strtotime($de) > 120 * 86400) $ate = date('Y-m-d', strtotime($de . ' +120 days'));
        $so = isset($_GET['turma']) && $_GET['turma'] !== '' ? (int) $_GET['turma'] : null;
        $this->json(['ok' => true, 'itens' => $this->agenda->itens($ctx['uid'], $ctx['turmasDia'], $de, $ate, $so)]);
    }

    public function salvar()
    {
        CsrfMiddleware::validate();
        $ctx = self::contexto();
        $id  = (int) ($_POST['id'] ?? 0) ?: null;
        $antes = $id ? $this->agenda->porId($id) : null;
        if ($id && (!$antes || !$this->podeEditar($antes, $ctx))) $this->json(['ok' => false, 'erro' => 'Você não pode editar este evento.'], 403);

        $vis    = ($_POST['visibilidade'] ?? 'pessoal') === 'turma' ? 'turma' : 'pessoal';
        $turma  = (int) ($_POST['turma_id'] ?? 0) ?: null;
        $diaTodo = !empty($_POST['dia_inteiro']);
        $d = [
            'turma_id'    => $turma,
            'visibilidade'=> $vis,
            'tipo'        => array_key_exists($_POST['tipo'] ?? '', Agenda::TIPOS) ? $_POST['tipo'] : 'tarefa',
            'prioridade'  => array_key_exists($_POST['prioridade'] ?? '', Agenda::PRIORIDADES) ? $_POST['prioridade'] : 'media',
            'titulo'      => mb_substr(trim((string) ($_POST['titulo'] ?? '')), 0, 150),
            'descricao'   => trim((string) ($_POST['descricao'] ?? '')) ?: null,
            'data'        => $this->data($_POST['data'] ?? ''),
            'hora_inicio' => $diaTodo ? null : $this->hora($_POST['inicio'] ?? ''),
            'hora_fim'    => $diaTodo ? null : $this->hora($_POST['fim'] ?? ''),
            'local'       => mb_substr(trim((string) ($_POST['local'] ?? '')), 0, 120) ?: null,
            'lembrete'    => !empty($_POST['lembrete']) ? 1 : 0,
            'criado_por'  => $ctx['uid'],
        ];

        $erro = null;
        if (mb_strlen($d['titulo']) < 2)                 $erro = 'Dê um título ao evento.';
        elseif (!$d['data'])                             $erro = 'Escolha a data.';
        elseif ($d['hora_inicio'] && $d['hora_fim'] && $d['hora_fim'] <= $d['hora_inicio']) $erro = 'O fim precisa ser depois do início.';
        elseif ($vis === 'turma' && (!$turma || empty($ctx['turmasDia'][$turma]['pode_editar']))) $erro = 'Só o representante (ou o vice) cria eventos para a turma.';
        elseif ($vis === 'pessoal' && !$ctx['podePessoal'])  $erro = 'A agenda faz parte do Dia a dia, incluso a partir do plano Anual.';
        elseif ($vis === 'pessoal' && $turma && !isset($ctx['turmas'][$turma])) $erro = 'Turma inválida.';
        if ($erro) $this->json(['ok' => false, 'erro' => $erro], 422);

        $novoId = $this->agenda->salvar($id, $d);
        $avisar = $vis === 'turma' && (!$id || ($antes['visibilidade'] ?? '') !== 'turma');

        if (!$avisar) $this->json(['ok' => true, 'id' => $novoId]);

        // Evento novo da turma: responde já e avisa a turma depois
        RespostaHelper::jsonEContinuar(['ok' => true, 'id' => $novoId]);
        $ids = array_values(array_filter(array_map(fn($a) => (int) $a['id'], (new TurmaUsuario())->listarAlunos($turma)), fn($x) => $x !== $ctx['uid']));
        $quando = date('d/m', strtotime($d['data'])) . ($d['hora_inicio'] ? ' às ' . substr($d['hora_inicio'], 0, 5) : '');
        $rot = Agenda::TIPOS[$d['tipo']][0];
        (new Notificacao())->criarParaVarios($ids, in_array($d['tipo'], ['prova', 'entrega'], true) ? 'prazo' : 'calendario',
            "{$rot}: {$d['titulo']}", "{$ctx['turmas'][$turma]['nome']} · {$quando}" . ($d['prioridade'] === 'urgente' ? ' · urgente' : ''), '/agenda?data=' . $d['data']);
        exit;
    }

    public function excluir(int $id)
    {
        CsrfMiddleware::validate();
        $ctx = self::contexto();
        $e = $this->agenda->porId($id);
        if (!$e || !$this->podeEditar($e, $ctx)) $this->json(['ok' => false, 'erro' => 'Você não pode excluir este evento.'], 403);
        $this->agenda->excluir($id);
        $this->json(['ok' => true]);
    }

    public function concluir(int $id)
    {
        CsrfMiddleware::validate();
        $ctx = self::contexto();
        $e = $this->agenda->porId($id);
        $ve = $e && (($e['visibilidade'] === 'pessoal' && (int) $e['criado_por'] === $ctx['uid'])
                  || ($e['visibilidade'] === 'turma' && isset($ctx['turmasDia'][(int) $e['turma_id']])));
        if (!$ve) $this->json(['ok' => false, 'erro' => 'Evento não encontrado.'], 404);
        $this->json(['ok' => true, 'concluido' => $this->agenda->alternarConclusao($id, $ctx['uid'])]);
    }

    /** Baixa o evento em .ics (Google Agenda, celular, Outlook). */
    public function ics(int $id)
    {
        $ctx = self::contexto();
        $e = $this->agenda->porId($id);
        $ve = $e && (($e['visibilidade'] === 'pessoal' && (int) $e['criado_por'] === $ctx['uid'])
                  || ($e['visibilidade'] === 'turma' && isset($ctx['turmas'][(int) $e['turma_id']])));
        if (!$ve) { http_response_code(404); exit('Evento não encontrado.'); }
        $esc = fn($t) => str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\,', '\n', '\n'], (string) $t);
        $dt  = str_replace('-', '', $e['data']);
        if ($e['hora_inicio']) {
            $ini = 'DTSTART;TZID=America/Sao_Paulo:' . $dt . 'T' . str_replace(':', '', substr($e['hora_inicio'], 0, 5)) . '00';
            $fimH = $e['hora_fim'] ?: date('H:i', strtotime($e['hora_inicio']) + 3600);
            $fim = 'DTEND;TZID=America/Sao_Paulo:' . $dt . 'T' . str_replace(':', '', substr($fimH, 0, 5)) . '00';
        } else {
            $ini = 'DTSTART;VALUE=DATE:' . $dt;
            $fim = 'DTEND;VALUE=DATE:' . date('Ymd', strtotime($e['data'] . ' +1 day'));
        }
        $linhas = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//' . App::getName() . '//Agenda//PT-BR', 'BEGIN:VEVENT',
            'UID:evento-' . (int) $e['id'] . '@' . preg_replace('/[^a-z0-9.]/i', '', parse_url(App::getUrl(), PHP_URL_HOST) ?: 'prumo'),
            'DTSTAMP:' . gmdate('Ymd\THis\Z'), $ini, $fim,
            'SUMMARY:' . $esc(Agenda::TIPOS[$e['tipo']][0] . ': ' . $e['titulo']),
            'DESCRIPTION:' . $esc(trim(($e['turma_nome'] ? $e['turma_nome'] . '. ' : '') . ($e['descricao'] ?? ''))),
            'LOCATION:' . $esc($e['local'] ?? ''),
            'BEGIN:VALARM', 'TRIGGER:-PT12H', 'ACTION:DISPLAY', 'DESCRIPTION:' . $esc($e['titulo']), 'END:VALARM',
            'END:VEVENT', 'END:VCALENDAR'];
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="evento-' . (int) $e['id'] . '.ics"');
        echo implode("\r\n", $linhas);
        exit;
    }

    // ============ CONTEXTO E PERMISSÕES ============

    /**
     * Turmas do usuário: [id => nome, tipo, pode_editar, tem_dia]; turmasDia (só com Dia a dia);
     * podePessoal (agenda pessoal liberada); turmasEditaveis (onde cria eventos da turma).
     */
    public static function contexto(): array
    {
        $u = $_SESSION['usuario'];
        $uid = (int) $u['id'];
        $master = $u['tipo'] === 'master';
        $tm = new Turma();
        $lista = $master ? $tm->listarTodas() : $tm->listarDoUsuario($uid);
        $turmas = [];
        foreach ($lista as $t) {
            $turmas[(int) $t['id']] = [
                'id' => (int) $t['id'], 'nome' => $t['nome'], 'tipo' => $t['tipo'] ?? 'turma',
                'pode_editar' => $master || ($t['meu_papel'] ?? '') === 'representante',
                'principal' => $master || (($t['meu_papel'] ?? '') === 'representante' && empty($t['sou_vice'])),
                'tem_dia' => $master || PlanoGuard::temDiaADia($t),
            ];
        }
        $dia = array_filter($turmas, fn($t) => $t['tem_dia']);
        return [
            'uid' => $uid, 'master' => $master, 'turmas' => $turmas, 'turmasDia' => $dia,
            'podePessoal' => $master || (bool) $dia,
            'turmasEditaveis' => array_values(array_filter($dia, fn($t) => $t['pode_editar'])),
            'semDia' => array_values(array_filter($turmas, fn($t) => !$t['tem_dia'])),
        ];
    }

    /** Lembrete na véspera: uma vez por dia, quando a pessoa abre a agenda ou a página inicial. */
    public static function gerarLembretes(array $ctx): void
    {
        $chave = 'lembretes_' . date('Ymd');
        if (!empty($_SESSION[$chave]) || !$ctx['turmasDia'] && !$ctx['podePessoal']) return;
        $_SESSION[$chave] = 1;
        $notif = new Notificacao();
        foreach ((new Agenda())->lembretesPendentes($ctx['uid'], $ctx['turmasDia']) as $i) {
            $rot = Agenda::TIPOS[$i['tipo']][0];
            $notif->criar($ctx['uid'], 'lembrete', "Amanhã: {$rot} — {$i['titulo']}",
                ($i['turma_nome'] ? $i['turma_nome'] . ' · ' : '') . ($i['inicio'] ? 'às ' . $i['inicio'] : 'dia inteiro')
                . ($i['prioridade'] === 'urgente' || $i['prioridade'] === 'alta' ? ' · prioridade ' . mb_strtolower(Agenda::PRIORIDADES[$i['prioridade']][0]) : ''),
                '/agenda?data=' . $i['data'], $i['id']);
        }
    }

    private function podeEditar(array $e, array $ctx): bool
    {
        if ($e['visibilidade'] === 'pessoal') return (int) $e['criado_por'] === $ctx['uid'];
        return !empty($ctx['turmasDia'][(int) $e['turma_id']]['pode_editar']);
    }

    private function data(string $v): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : null;
    }

    private function hora(string $v): ?string
    {
        return preg_match('/^\d{2}:\d{2}$/', $v) ? $v . ':00' : null;
    }

    private function json(array $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function render(string $view, array $dados = []): void
    {
        extract($dados);
        require __DIR__ . '/../Views/' . $view . '.php';
    }
}
