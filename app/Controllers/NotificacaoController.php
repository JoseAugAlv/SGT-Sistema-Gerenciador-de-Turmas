<?php
// app/Controllers/NotificacaoController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/PreferenciasUsuario.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class NotificacaoController
{
    private Notificacao         $notif;
    private PreferenciasUsuario $prefs;

    public function __construct()
    {
        $this->notif = new Notificacao();
        $this->prefs = new PreferenciasUsuario();
    }

        public function index()
    {
        $u = $_SESSION['usuario'];

        // Verifica prazos próximos (roda a cada visita)
        $this->verificarPrazosProximos((int) $u['id']);

        $filtro = $_GET['filtro'] ?? null;

        $notificacoes = $this->notif->listarPorUsuario((int) $u['id'], $filtro, 100);
        $naoLidas     = $this->notif->contarNaoLidas((int) $u['id']);

        $this->render('notificacoes/index', [
            'notificacoes' => $notificacoes,
            'naoLidas'     => $naoLidas,
            'filtro'       => $filtro,
        ]);
    }

        public function marcarLida()
    {
        CsrfMiddleware::validate();
        $u  = $_SESSION['usuario'];
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->notif->marcarLida($id, (int) $u['id']);
        }

        // Redireciona de volta para onde o usuário veio (padrão: lista)
        $voltar = $_POST['voltar'] ?? ($_SERVER['HTTP_REFERER'] ?? null);
        if ($voltar && strpos($voltar, 'http') === 0) {
            header('Location: ' . $voltar);
        } else {
            header('Location: ' . App::getBasePath() . '/notificacoes');
        }
        exit;
    }

    public function marcarTodasLidas()
    {
        CsrfMiddleware::validate();
        $u = $_SESSION['usuario'];
        $total = $this->notif->marcarTodasLidas((int) $u['id']);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => "{$total} notificação(ões) marcada(s) como lida(s)."];
        header('Location: ' . App::getBasePath() . '/notificacoes');
        exit;
    }

    public function contador()
    {
        $u = $_SESSION['usuario'] ?? null;
        header('Content-Type: application/json');
        if (!$u) { echo json_encode(['total' => 0]); exit; }

        echo json_encode(['total' => $this->notif->contarNaoLidas((int) $u['id'])]);
        exit;
    }

    public function dropdown()
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { echo ''; exit; }

        $ultimas = $this->notif->ultimas((int) $u['id'], 10);
        header('Content-Type: text/html; charset=utf-8');
        extract(['ultimas' => $ultimas, 'basePath' => App::getBasePath()]);
        require __DIR__ . '/../Views/notificacoes/dropdown.php';
        exit;
    }

    public function preferencias()
    {
        $u = $_SESSION['usuario'];
        $this->render('notificacoes/preferencias', [
            'prefs' => $this->prefs->porUsuario((int) $u['id']) ?? [
                'receber_email' => 1, 'receber_email_prazos' => 1,
                'receber_email_alertas' => 1, 'receber_email_avaliacoes' => 1,
            ],
        ]);
    }

    public function salvarPreferencias()
    {
        CsrfMiddleware::validate();
        $u = $_SESSION['usuario'];

        $prefs = $this->prefs->porUsuario((int) $u['id']);
        if (!$prefs) {
            $this->prefs->criarPadrao((int) $u['id']);
        }

        $this->prefs->atualizar((int) $u['id'], $_POST);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Preferências salvas.'];
        header('Location: ' . App::getBasePath() . '/notificacoes/preferencias');
        exit;
    }

    private function render(string $view, array $dados = []): void
    {
        extract($dados);
        require __DIR__ . '/../Views/' . $view . '.php';
    }

        /**
     * Cria notificações para critérios que vencem em até 3 dias.
     * Evita duplicatas checando se já existe notificação do tipo 'prazo'
     * com mesmo referencia_id para o mesmo usuário.
     */
    private function verificarPrazosProximos(int $usuarioId): void
    {
        $pdo = Database::getConnection();

        // Projetos do usuário (via turma)
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.id
            FROM projetos p
            INNER JOIN turma_usuarios tu ON tu.turma_id = p.turma_id
            WHERE tu.usuario_id = ? AND tu.ativo = 1 AND p.encerrado = 0
        ");
        $stmt->execute([$usuarioId]);
        $projetos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($projetos)) return;

        $ph = implode(',', array_fill(0, count($projetos), '?'));

        $stmt = $pdo->prepare("
            SELECT c.id, c.nome, c.prazo_avaliacao, c.projeto_id, p.nome AS projeto_nome
            FROM criterios c
            INNER JOIN projetos p ON p.id = c.projeto_id
            WHERE c.projeto_id IN ({$ph})
              AND c.bloqueado = 0
              AND c.prazo_avaliacao IS NOT NULL
              AND c.prazo_avaliacao >= NOW()
              AND c.prazo_avaliacao <= DATE_ADD(NOW(), INTERVAL 3 DAY)
        ");
        $stmt->execute($projetos);
        $crits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($crits)) return;

        $notif = new Notificacao();

        foreach ($crits as $c) {
            // Já existe notificação desse critério para o usuário?
            $stmt = $pdo->prepare("
                SELECT 1 FROM notificacoes
                WHERE usuario_id = ? AND tipo = 'prazo' AND referencia_id = ?
                LIMIT 1
            ");
            $stmt->execute([$usuarioId, (int) $c['id']]);
            if ($stmt->fetchColumn()) continue;

            $notif->criar(
                $usuarioId,
                'prazo',
                'Prazo próximo',
                "O critério '{$c['nome']}' do projeto '{$c['projeto_nome']}' vence em {$c['prazo_avaliacao']}.",
                '/projetos/' . (int) $c['projeto_id'] . '/avaliacoes',
                (int) $c['id']
            );
        }
    }
}