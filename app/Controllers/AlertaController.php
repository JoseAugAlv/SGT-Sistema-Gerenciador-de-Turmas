<?php
// app/Controllers/AlertaController.php
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
        $stmt = $pdo->prepare("SELECT id, nome, codigo_acesso FROM turmas WHERE id = ? LIMIT 1");
        $stmt->execute([$turmaId]);
        $turma = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$turma) { $this->flash('Turma não encontrada.'); $this->redirect('/turmas'); }

        $projetos = $this->projeto->listarPorTurma($turmaId);
        $grupos   = [];
        foreach ($projetos as $p) {
            $grupos[$p['id']] = $this->grupo->listarPorProjeto((int) $p['id']);
        }

        $this->render('alertas/criar', [
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

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/turmas/' . $turmaId . '/alertas/criar');
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
            'expira_em'  => $expiraEm,
        ]);

        // Destinatários
        $destinatarios = $this->destinatariosPorEscopo($turmaId, $escopo, $projetoId, $grupoId);

        // Notifica cada um
        $link = App::getBasePath() . '/turmas/' . $turmaId . '/alertas';
        $total = $this->notif->criarParaVarios(
            $destinatarios, 'alerta', "[Alerta] {$titulo}", $mensagem, $link
        );

        // Emails
        foreach ($destinatarios as $uid) {
            $this->mail->notificar(
                (int) $uid,
                'Novo alerta: ' . $titulo,
                '<p>' . nl2br(htmlspecialchars($mensagem)) . '</p><p><a href="' . App::getUrl() . $link . '">Ver no sistema</a></p>',
                'alertas',
                $urgente // urgente ignora preferências
            );
        }

        $this->audit->registrar('alerta_criado', 'alertas', $id, null, [
            'titulo' => $titulo, 'escopo' => $escopo,
            'urgente' => $urgente, 'destinatarios' => $total,
        ]);

        $this->flash("Alerta enviado para {$total} usuário(s).", 'sucesso');
        $this->redirect('/turmas/' . $turmaId . '/alertas');
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