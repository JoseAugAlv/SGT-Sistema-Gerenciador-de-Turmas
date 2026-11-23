<?php
// app/Controllers/AlertaController.php
require_once __DIR__ . '/../Core/Mail.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Alerta.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Grupo.php';
require_once __DIR__ . '/../Models/GrupoAluno.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Services/EmailService.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class AlertaController
{
    private Alerta       $alerta;
    private Notificacao  $notif;
    private Projeto      $projeto;
    private Grupo        $grupo;
    private GrupoAluno   $membro;
    private TurmaUsuario $tu;
    private Auditoria    $audit;
    private EmailService $mail;

    public function __construct()
    {
        $this->alerta = new Alerta();
        $this->notif  = new Notificacao();
        $this->projeto = new Projeto();
        $this->grupo  = new Grupo();
        $this->membro = new GrupoAluno();
        $this->tu     = new TurmaUsuario();
        $this->audit  = new Auditoria();
        $this->mail   = new EmailService();
    }

    // ============ LISTAGEM ============

    public function index(int $turmaId)
    {
        $u = $_SESSION['usuario'];

        // Verifica se está na turma
        $isMaster = $u['tipo'] === 'master';
        if (!$isMaster && !$this->tu->estaAtivo($turmaId, (int) $u['id'])) {
            http_response_code(403); exit('Sem acesso.');
        }

        $alertas = $this->alerta->listarVisiveis($turmaId, (int) $u['id']);

        $this->render('alertas/index', [
            'turmaId' => $turmaId,
            'turma'   => (new Turma())->porId($turmaId),
            'alertas' => $alertas,
            'isRep'   => $this->tu->ehRepresentante($turmaId, (int) $u['id']),
            'isMaster'=> $isMaster,
        ]);
    }

    // ============ CRIAR (REP OU MASTER) ============

    public function criarForm(int $turmaId)
    {
        $u = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';
        $isRep    = $this->tu->ehRepresentante($turmaId, (int) $u['id']);

        if (!$isMaster && !$isRep) {
            http_response_code(403); exit('Apenas representante ou master.');
        }

        // Dados da turma
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, nome, tipo, codigo_acesso FROM turmas WHERE id = ? LIMIT 1");
        $stmt->execute([$turmaId]);
        $turma = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$turma) { $this->flash('Turma não encontrada.'); $this->redirect('/turmas'); }

        $projetos = $this->projeto->listarPorTurma($turmaId);
        $grupos   = [];
        foreach ($projetos as $p) {
            $grupos[$p['id']] = $this->grupo->listarPorProjeto((int) $p['id']);
        }

        require_once __DIR__ . '/../Helpers/PlanoGuard.php';
        $this->render('alertas/criar', [
            'temDia'   => $isMaster || PlanoGuard::temDiaADia((new Turma())->porId($turmaId)),
            'turma'    => $turma,
            'projetos' => $projetos,
            'gruposPorProjeto' => $grupos,
        ]);
    }

    public function salvar(int $turmaId)
    {
        $u = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';
        $isRep    = $this->tu->ehRepresentante($turmaId, (int) $u['id']);

        if (!$isMaster && !$isRep) {
            http_response_code(403); exit('Apenas representante ou master.');
        }

        CsrfMiddleware::validate();

        $titulo     = trim($_POST['titulo'] ?? '');
        $mensagem   = trim($_POST['mensagem'] ?? '');
        $escopo     = $_POST['escopo'] ?? 'turma';
        $projetoId  = (int) ($_POST['projeto_id'] ?? 0) ?: null;
        $grupoId    = (int) ($_POST['grupo_id'] ?? 0) ?: null;
        $urgente    = !empty($_POST['urgente']);
        $expiraEm   = trim($_POST['expira_em'] ?? '') ?: null;
        $contagem   = !empty($_POST['tem_contagem']) ? (trim($_POST['contagem_ate'] ?? '') ?: null) : null;
        // Aviso do dia: aparece só no dia escolhido (some à meia-noite)
        $doDia      = ($_POST['tipo_aviso'] ?? '') === 'dia' ? (trim($_POST['do_dia'] ?? '') ?: date('Y-m-d')) : null;

        $erros = [];
        if (strlen($titulo) < 3)     $erros[] = 'Título muito curto.';
        if (strlen($mensagem) < 3)   $erros[] = 'Mensagem muito curta.';
        if (!in_array($escopo, ['turma', 'projeto', 'grupo'], true)) {
            $erros[] = 'Escopo inválido.';
        }
        if ($escopo === 'projeto' && !$projetoId) $erros[] = 'Selecione um projeto.';
        if ($escopo === 'grupo'   && !$grupoId)   $erros[] = 'Selecione um grupo.';

        if ($expiraEm) {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $expiraEm);
            if (!$dt) $erros[] = 'Data de expiração inválida.';
            else $expiraEm = str_replace('T', ' ', $expiraEm) . ':00';
        }

        if (!empty($_POST['tem_contagem'])) {
            $dt = $contagem ? DateTime::createFromFormat('Y-m-d\TH:i', $contagem) : false;
            if (!$dt)                        $erros[] = 'Informe a data e a hora da contagem regressiva.';
            elseif ($dt->getTimestamp() <= time()) $erros[] = 'A contagem regressiva precisa terminar no futuro.';
            else {
                $contagem = $dt->format('Y-m-d H:i:00');
                // Sem expiração informada: o aviso some 1 dia depois de a contagem zerar
                if (!$expiraEm) $expiraEm = date('Y-m-d H:i:00', $dt->getTimestamp() + 86400);
            }
        }

        // Aviso do dia e contagem regressiva fazem parte do Dia a dia (Anual para cima, ou adicional)
        require_once __DIR__ . '/../Helpers/PlanoGuard.php';
        $turmaAv = (new Turma())->porId($turmaId);
        if (($doDia || !empty($_POST['tem_contagem'])) && !$isMaster && $turmaAv && !PlanoGuard::temDiaADia($turmaAv)) {
            $erros[] = 'Aviso do dia e contagem regressiva fazem parte do Dia a dia (incluso a partir do plano Anual).';
        }
        if ($doDia) {
            if (!DateTime::createFromFormat('Y-m-d', $doDia) || $doDia < date('Y-m-d')) $erros[] = 'O aviso do dia precisa ser para hoje ou uma data futura.';
            else $expiraEm = $doDia . ' 23:59:59';
        }

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/turmas/' . $turmaId . '/alertas/criar');
        }

        // Clique repetido (o envio de emails demora): o mesmo aviso nos últimos 2 minutos não é criado de novo
        $dup = Database::getConnection()->prepare("
            SELECT id FROM alertas WHERE turma_id = ? AND autor_id = ? AND titulo = ? AND mensagem = ? AND created_at > NOW() - INTERVAL 2 MINUTE LIMIT 1
        ");
        $dup->execute([$turmaId, (int) $u['id'], $titulo, $mensagem]);
        if ($dup->fetchColumn()) {
            $this->flash('Esse aviso já foi enviado agora há pouco.', 'aviso');
            $this->redirect('/turmas/' . $turmaId . '/alertas');
        }

        // Normaliza: se escopo é turma, zera projeto/grupo
        if ($escopo === 'turma')   { $projetoId = null; $grupoId = null; }
        if ($escopo === 'projeto') { $grupoId = null; }

        $id = $this->alerta->criar([
            'autor_id'   => (int) $u['id'],
            'turma_id'   => $turmaId,
            'projeto_id' => $projetoId,
            'grupo_id'   => $grupoId,
            'titulo'     => $titulo,
            'mensagem'   => $mensagem,
            'urgente'    => $urgente ? 1 : 0,
            'contagem_ate' => $contagem,
            'do_dia'     => $doDia,
            'expira_em'  => $expiraEm,
        ]);

        // Destinatários
        $destinatarios = $this->destinatariosPorEscopo($turmaId, $escopo, $projetoId, $grupoId);

        // Notifica cada um
        $link = App::getBasePath() . '/turmas/' . $turmaId . '/alertas';
        $total = $this->notif->criarParaVarios(
            $destinatarios, 'alerta', "[Alerta] {$titulo}", $mensagem, $link
        );

        $this->audit->registrar('alerta_criado', 'alertas', $id, null, [
            'titulo' => $titulo, 'escopo' => $escopo,
            'urgente' => $urgente, 'destinatarios' => $total,
        ]);
        $this->flash("Aviso enviado para {$total} pessoa(s).", 'sucesso');

        // Responde já e manda os emails depois: a página não fica presa esperando o servidor de email
        $this->redirecionarEContinuar('/turmas/' . $turmaId . '/alertas');
        foreach ($destinatarios as $uid) {
            $this->mail->notificar(
                (int) $uid,
                'Novo alerta: ' . $titulo,
                Mail::modelo([
                    'previa' => $mensagem, 'selo' => $urgente ? 'Aviso urgente' : 'Aviso da turma', 'cor' => $urgente ? '#d63548' : '#2563eb',
                    'titulo' => $titulo, 'corpo' => '<p>' . nl2br(Mail::e($mensagem)) . '</p>',
                    'destaque' => $contagem ? 'Contagem: ' . date('d/m/Y \à\s H:i', strtotime($contagem)) : ($doDia ? 'Aviso do dia: ' . date('d/m/Y', strtotime($doDia)) : null),
                    'botao' => ['Ver o aviso', '/turmas/' . $turmaId . '/alertas'],
                    'rodape' => $urgente ? 'Avisos urgentes chegam para todos, independente da preferência de email.' : null,
                ]),
                'alertas',
                $urgente // urgente ignora preferências
            );
        }

        exit;
    }

    /** Manda o redirecionamento ao navegador e continua rodando (para tarefas lentas, como emails). */
    private function redirecionarEContinuar(string $path): void
    {
        ignore_user_abort(true);
        @set_time_limit(300);
        session_write_close();
        header('Location: ' . App::getBasePath() . $path);
        header('Connection: close');
        header('Content-Length: 0');
        while (ob_get_level() > 0) ob_end_flush();
        flush();
    }

    // ============ HELPERS ============

    private function destinatariosPorEscopo(int $turmaId, string $escopo, ?int $projetoId, ?int $grupoId): array
    {
        $pdo = Database::getConnection();

        if ($escopo === 'turma') {
            $stmt = $pdo->prepare("
                SELECT usuario_id FROM turma_usuarios
                WHERE turma_id = ? AND ativo = 1
            ");
            $stmt->execute([$turmaId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        if ($escopo === 'projeto') {
            // Alunos dos grupos do projeto + representantes da turma
            $stmt = $pdo->prepare("
                SELECT DISTINCT ga.usuario_id
                FROM grupo_alunos ga
                INNER JOIN grupos g ON g.id = ga.grupo_id
                WHERE g.projeto_id = ? AND ga.saiu_em IS NULL
                UNION
                SELECT usuario_id FROM turma_usuarios
                WHERE turma_id = ? AND papel = 'representante' AND ativo = 1
            ");
            $stmt->execute([$projetoId, $turmaId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // grupo
        $stmt = $pdo->prepare("
            SELECT usuario_id FROM grupo_alunos
            WHERE grupo_id = ? AND saiu_em IS NULL
        ");
        $stmt->execute([$grupoId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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