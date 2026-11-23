<?php
// app/Controllers/CalendarioController.php
// Calendário (entregas, provas, eventos, reuniões) e horário semanal das aulas da turma ou do grêmio.
// Todos da turma veem; o representante (ou presidente do grêmio) e o vice editam.
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Calendario.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Helpers/RespostaHelper.php';
require_once __DIR__ . '/../Helpers/PlanoGuard.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class CalendarioController
{
    private TurmaUsuario $tu;
    private Calendario   $cal;

    public function __construct()
    {
        $this->tu  = new TurmaUsuario();
        $this->cal = new Calendario();
    }

    public function index(int $turmaId)
    {
        [$turma, $podeEditar] = $this->carregar($turmaId);
        require_once __DIR__ . '/AgendaController.php';
        $u = $_SESSION['usuario'];

        $this->render('calendario/index', [
            'turma'        => $turma,
            'podeEditar'   => $podeEditar,
            'mes'          => date('Y-m'),
            'ctx'          => AgendaController::contexto(),
            'temDia'       => $u['tipo'] === 'master' || PlanoGuard::temDiaADia($turma),
            'souPrincipal' => $u['tipo'] === 'master' || $this->tu->ehRepresentantePrincipal($turmaId, (int) $u['id']),
            'horarios'     => $this->cal->horarios($turmaId),
        ]);
    }

    public function salvarEvento(int $turmaId)
    {
        CsrfMiddleware::validate();
        [$turma] = $this->carregar($turmaId, true);
        $d = [
            'turma_id'    => $turmaId,
            'tipo'        => array_key_exists($_POST['tipo'] ?? '', Calendario::TIPOS) ? $_POST['tipo'] : 'evento',
            'titulo'      => mb_substr(trim($_POST['titulo'] ?? ''), 0, 150),
            'descricao'   => trim($_POST['descricao'] ?? '') ?: null,
            'data'        => $_POST['data'] ?? '',
            'hora_inicio' => trim($_POST['hora_inicio'] ?? '') ?: null,
            'hora_fim'    => trim($_POST['hora_fim'] ?? '') ?: null,
            'local'       => mb_substr(trim($_POST['local'] ?? ''), 0, 120) ?: null,
            'criado_por'  => (int) $_SESSION['usuario']['id'],
        ];
        $volta = '/turmas/' . $turmaId . '/calendario?mes=' . substr($d['data'], 0, 7);
        $erros = [];
        if (mb_strlen($d['titulo']) < 2)                       $erros[] = 'Dê um título.';
        if (!DateTime::createFromFormat('Y-m-d', $d['data']))  $erros[] = 'Data inválida.';
        if ($d['hora_inicio'] && $d['hora_fim'] && $d['hora_fim'] <= $d['hora_inicio']) $erros[] = 'O fim precisa ser depois do início.';
        if ($erros) { $this->flash(implode(' ', $erros)); $this->redirect('/turmas/' . $turmaId . '/calendario'); }

        $id = $this->cal->criarEvento($d);
        (new Auditoria())->registrar('evento_calendario_criado', 'eventos_calendario', $id, null, ['tipo' => $d['tipo'], 'data' => $d['data']]);
        $rotulo = Calendario::TIPOS[$d['tipo']][0];
        $this->flash("{$rotulo} adicionada ao calendário.", 'sucesso');

        // Avisa a turma depois de responder (o email pode demorar)
        RespostaHelper::redirecionarEContinuar($volta);
        $ids = array_map(fn($a) => (int) $a['id'], $this->tu->listarAlunos($turmaId));
        $ids = array_values(array_filter($ids, fn($x) => $x !== (int) $d['criado_por']));
        $quando = date('d/m/Y', strtotime($d['data'])) . ($d['hora_inicio'] ? ' às ' . substr($d['hora_inicio'], 0, 5) : '');
        (new Notificacao())->criarParaVarios($ids, in_array($d['tipo'], ['entrega', 'prova'], true) ? 'prazo' : 'calendario',
            "{$rotulo}: {$d['titulo']}", "No calendário de {$turma['nome']}: {$quando}.", '/turmas/' . $turmaId . '/calendario?mes=' . substr($d['data'], 0, 7));
        exit;
    }

    public function excluirEvento(int $id)
    {
        CsrfMiddleware::validate();
        $ev = $this->cal->evento($id);
        if (!$ev) { $this->flash('Evento não encontrado.'); $this->redirect('/turmas'); }
        $this->carregar((int) $ev['turma_id'], true);
        $this->cal->excluirEvento($id);
        $this->flash('Removido do calendário.', 'sucesso');
        $this->redirect('/turmas/' . (int) $ev['turma_id'] . '/calendario?mes=' . substr($ev['data'], 0, 7));
    }

    public function salvarHorario(int $turmaId)
    {
        CsrfMiddleware::validate();
        $this->carregar($turmaId, true);
        $dias = array_map('intval', (array) ($_POST['dias'] ?? []));
        $d = [
            'turma_id'   => $turmaId,
            'inicio'     => $_POST['inicio'] ?? '',
            'fim'        => $_POST['fim'] ?? '',
            'disciplina' => mb_substr(trim($_POST['disciplina'] ?? ''), 0, 100),
            'professor'  => mb_substr(trim($_POST['professor'] ?? ''), 0, 100) ?: null,
            'sala'       => mb_substr(trim($_POST['sala'] ?? ''), 0, 50) ?: null,
        ];
        $erros = [];
        $dias = array_values(array_filter($dias, fn($x) => isset(Calendario::DIAS[$x])));
        if (!$dias)                                   $erros[] = 'Marque pelo menos um dia da semana.';
        if (mb_strlen($d['disciplina']) < 2)          $erros[] = 'Informe a disciplina.';
        if (!preg_match('/^\d{2}:\d{2}$/', $d['inicio']) || !preg_match('/^\d{2}:\d{2}$/', $d['fim']) || $d['fim'] <= $d['inicio']) $erros[] = 'Horário inválido: o fim precisa ser depois do início.';
        if ($erros) { $this->flash(implode(' ', $erros)); $this->redirect('/turmas/' . $turmaId . '/calendario#horario'); }

        foreach ($dias as $dia) $this->cal->criarHorario($d + ['dia_semana' => $dia]);
        $this->flash(count($dias) > 1 ? "Aula adicionada em " . count($dias) . " dias." : 'Aula adicionada ao horário.', 'sucesso');
        $this->redirect('/turmas/' . $turmaId . '/calendario#horario');
    }

    public function excluirHorario(int $id)
    {
        CsrfMiddleware::validate();
        $h = $this->cal->horario($id);
        if (!$h) { $this->flash('Aula não encontrada.'); $this->redirect('/turmas'); }
        $this->carregar((int) $h['turma_id'], true);
        $this->cal->excluirHorario($id);
        $this->flash('Aula removida do horário.', 'sucesso');
        $this->redirect('/turmas/' . (int) $h['turma_id'] . '/calendario#horario');
    }

    // ============ HELPERS ============

    /** Turma + se o usuário pode editar. Barra quem não é da turma (ou quem não pode editar, se $editar). */
    private function carregar(int $turmaId, bool $editar = false): array
    {
        $turma = (new Turma())->porId($turmaId);
        if (!$turma) { $this->flash('Turma não encontrada.'); $this->redirect('/turmas'); }
        $u = $_SESSION['usuario'];
        $master = $u['tipo'] === 'master';
        if (!$master && !$this->tu->estaAtivo($turmaId, (int) $u['id'])) { $this->flash('Você não está nesta turma.'); $this->redirect('/turmas'); }
        $pode = $master || $this->tu->ehRepresentante($turmaId, (int) $u['id']);
        if ($editar && !$pode) { http_response_code(403); exit('Só o representante (ou presidente) e o vice editam o calendário.'); }
        if ($editar && !$master && !PlanoGuard::temDiaADia($turma)) {
            $this->flash('Agenda e horário fazem parte do Dia a dia (incluso a partir do plano Anual).');
            $this->redirect('/turmas/' . $turmaId . '/calendario');
        }
        return [$turma, $pode];
    }

    private function render(string $view, array $dados = []): void
    {
        extract($dados);
        require __DIR__ . '/../Views/' . $view . '.php';
    }

    private function redirect(string $path): void
    {
        header('Location: ' . App::getBasePath() . $path);
        exit;
    }

    private function flash(string $msg, string $tipo = 'erro'): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $msg];
    }
}
