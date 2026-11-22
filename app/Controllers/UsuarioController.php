<?php
// app/Controllers/UsuarioController.php
// Versão completa: lista, criar, editar, excluir, atribuir tipo e tornar representante.

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/PreferenciasUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Helpers/PasswordHelper.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Core/Mail.php';

class UsuarioController
{
    private Usuario             $usuario;
    private PreferenciasUsuario $prefs;
    private Auditoria           $audit;
    private Mail                $mail;

    public function __construct()
    {
        $this->usuario = new Usuario();
        $this->prefs   = new PreferenciasUsuario();
        $this->audit   = new Auditoria();
        $this->mail    = new Mail();
    }

    // ============ HELPERS ============

    private function exigirMaster(): void
    {
        if (empty($_SESSION['usuario']) || ($_SESSION['usuario']['tipo'] ?? '') !== 'master') {
            http_response_code(403);
            exit('Acesso restrito ao master.');
        }
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

    // ============ LISTAGEM ============

    public function index()
    {
        $this->exigirMaster();

        $pdo    = Database::getConnection();
        $busca  = trim($_GET['busca'] ?? '');
        $like   = '%' . $busca . '%';
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $limite = 20;
        $offset = ($pagina - 1) * $limite;

        // Contagem
        if ($busca !== '') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nome LIKE ? OR email LIKE ?");
            $stmt->execute([$like, $like]);
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        }
        $total        = (int) $stmt->fetchColumn();
        $totalPaginas = max(1, (int) ceil($total / $limite));

        // Lista
        if ($busca !== '') {
            $stmt = $pdo->prepare("
                SELECT id, nome, email, tipo, ativo, email_confirmado, ultimo_acesso, created_at
                FROM usuarios
                WHERE nome LIKE ? OR email LIKE ?
                ORDER BY nome ASC
                LIMIT ? OFFSET ?
            ");
            $stmt->bindValue(1, $like, PDO::PARAM_STR);
            $stmt->bindValue(2, $like, PDO::PARAM_STR);
            $stmt->bindValue(3, $limite, PDO::PARAM_INT);
            $stmt->bindValue(4, $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("
                SELECT id, nome, email, tipo, ativo, email_confirmado, ultimo_acesso, created_at
                FROM usuarios
                ORDER BY nome ASC
                LIMIT ? OFFSET ?
            ");
            $stmt->bindValue(1, $limite, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ============ TURMAS PARA O MODAL ============
        $turmas = $pdo->query("
            SELECT id, nome, codigo_acesso
            FROM turmas
            ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Quantos representantes cada turma já tem
        $repsPorTurma = [];
        $stmt = $pdo->query("
            SELECT turma_id, COUNT(*) AS total
            FROM turma_usuarios
            WHERE papel = 'representante' AND ativo = 1
            GROUP BY turma_id
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $repsPorTurma[(int) $r['turma_id']] = (int) $r['total'];
        }

        // Em quais turmas cada usuário é representante
        $repsPorUsuario = [];
        $stmt = $pdo->query("
            SELECT tu.usuario_id, t.id AS turma_id, t.nome AS turma_nome
            FROM turma_usuarios tu
            INNER JOIN turmas t ON t.id = tu.turma_id
            WHERE tu.papel = 'representante' AND tu.ativo = 1
            ORDER BY t.nome ASC
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $repsPorUsuario[(int) $r['usuario_id']][] = [
                'turma_id'   => (int) $r['turma_id'],
                'turma_nome' => $r['turma_nome'],
            ];
        }

        // Em quais turmas cada usuário está (ativo), para saber se precisa adicionar antes
        $turmasPorUsuario = [];
        $stmt = $pdo->query("
            SELECT usuario_id, turma_id
            FROM turma_usuarios
            WHERE ativo = 1
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $turmasPorUsuario[(int) $r['usuario_id']][] = (int) $r['turma_id'];
        }

        // Paginação (preserva ?busca=)
        $paginationHtml = '';
        if ($totalPaginas > 1) {
            $base = App::getBasePath() . '/usuarios';
            $qs   = $busca !== '' ? '&busca=' . urlencode($busca) : '';

            $paginationHtml .= '<div class="paginacao">';
            if ($pagina > 1) {
                $paginationHtml .= '<a class="pag-btn" href="' . $base . '?pagina=' . ($pagina - 1) . $qs . '">Anterior</a>';
            }
            for ($i = max(1, $pagina - 2); $i <= min($totalPaginas, $pagina + 2); $i++) {
                $active = $i === $pagina ? ' active' : '';
                $paginationHtml .= '<a class="pag-btn' . $active . '" href="' . $base . '?pagina=' . $i . $qs . '">' . $i . '</a>';
            }
            if ($pagina < $totalPaginas) {
                $paginationHtml .= '<a class="pag-btn" href="' . $base . '?pagina=' . ($pagina + 1) . $qs . '">Próximo</a>';
            }
            $paginationHtml .= '</div>';
        }

        $this->render('usuarios/index', [
            'usuarios'         => $usuarios,
            'total'            => $total,
            'busca'            => $busca,
            'pagina'           => $pagina,
            'totalPaginas'     => $totalPaginas,
            'paginationHtml'   => $paginationHtml,
            'turmas'           => $turmas,
            'repsPorTurma'     => $repsPorTurma,
            'repsPorUsuario'   => $repsPorUsuario,
            'turmasPorUsuario' => $turmasPorUsuario,
        ]);
    }

    // ============ CRIAR ============

    public function criar()
    {
        $this->exigirMaster();
        $this->render('usuarios/criar');
    }

    public function salvar()
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $nome  = trim($_POST['nome'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $tipo  = $_POST['tipo'] ?? 'aluno';
        $senha = $_POST['senha'] ?? '';

        $erros = [];
        if (strlen($nome) < 3)                           $erros[] = 'Nome muito curto.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $erros[] = 'Email inválido.';
        if (!in_array($tipo, ['aluno', 'master'], true)) $erros[] = 'Tipo inválido.';
        if ($this->usuario->emailExiste($email))         $erros[] = 'Este email já está cadastrado.';

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/usuarios/criar');
        }

        $senhaTemp = $senha !== '' ? $senha : PasswordHelper::generateTemp(10);

        $r = SecurityHelper::validarForcaSenha($senhaTemp);
        if (!$r['valida']) {
            $this->flash('Senha fraca: ' . implode(' | ', $r['erros']));
            $this->redirect('/usuarios/criar');
        }

        $token  = SecurityHelper::gerarToken();
        $expira = date('Y-m-d H:i:s', time() + 24 * 3600);

        $id = $this->usuario->criar([
            'nome'               => $nome,
            'email'              => $email,
            'senha'              => PasswordHelper::hash($senhaTemp),
            'tipo'               => $tipo,
            'email_confirmado'   => 0,
            'email_token'        => $token,
            'email_token_expira' => $expira,
            'primeiro_login'     => 1,
            'ativo'              => 1,
            'lgpd_aceito'        => 0,
        ]);

        $this->prefs->criarPadrao($id);

        try { $this->mail->emailPrimeiroAcesso($email, $nome, $token, $senhaTemp); } catch (Throwable $e) {}

        $this->audit->registrar('usuario_criado', 'usuarios', $id, null, [
            'nome' => $nome, 'email' => $email, 'tipo' => $tipo,
        ]);

        $this->flash("Usuário criado. Senha temporária: {$senhaTemp}", 'sucesso');
        $this->redirect('/usuarios');
    }

    // ============ EDITAR ============

    public function editar()
    {
        $this->exigirMaster();

        // Aceita ?id= e também /usuarios/{id}/editar
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) { $this->flash('ID não informado.'); $this->redirect('/usuarios'); }

        $usuario = $this->usuario->findById($id);
        if (!$usuario) { $this->flash('Usuário não encontrado.'); $this->redirect('/usuarios'); }

        // Já é representante em quais turmas? (para exibir na view)
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT t.id, t.nome
            FROM turma_usuarios tu
            INNER JOIN turmas t ON t.id = tu.turma_id
            WHERE tu.usuario_id = ? AND tu.papel = 'representante' AND tu.ativo = 1
            ORDER BY t.nome
        ");
        $stmt->execute([$id]);
        $turmasComoRep = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('usuarios/editar', [
            'usuario'         => $usuario,
            'turmasComoRep'   => $turmasComoRep,
        ]);
    }

    public function atualizar()
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $id    = (int) ($_POST['id'] ?? 0);
        $nome  = trim($_POST['nome'] ?? '');
        $tipo  = $_POST['tipo'] ?? 'aluno';
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $senha = $_POST['senha'] ?? '';

        if (!$id) { $this->flash('ID não informado.'); $this->redirect('/usuarios'); }

        $usuario = $this->usuario->findById($id);
        if (!$usuario) { $this->flash('Usuário não encontrado.'); $this->redirect('/usuarios'); }

        $erros = [];
        if (strlen($nome) < 3)                           $erros[] = 'Nome muito curto.';
        if (!in_array($tipo, ['aluno', 'master'], true)) $erros[] = 'Tipo inválido.';

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/usuarios/editar?id=' . $id);
        }

        $pdo = Database::getConnection();
        $pdo->prepare("UPDATE usuarios SET nome = ?, tipo = ?, ativo = ? WHERE id = ?")
            ->execute([$nome, $tipo, $ativo, $id]);

        if ($senha !== '') {
            $r = SecurityHelper::validarForcaSenha($senha);
            if (!$r['valida']) {
                $this->flash('Senha fraca: ' . implode(' | ', $r['erros']));
                $this->redirect('/usuarios/editar?id=' . $id);
            }
            $this->usuario->atualizarSenha($id, PasswordHelper::hash($senha));
        }

        $this->audit->registrar('usuario_atualizado', 'usuarios', $id,
            ['nome' => $usuario['nome'], 'tipo' => $usuario['tipo'], 'ativo' => $usuario['ativo']],
            ['nome' => $nome, 'tipo' => $tipo, 'ativo' => $ativo]);

        $this->flash('Usuário atualizado.', 'sucesso');
        $this->redirect('/usuarios');
    }

    // ============ EXCLUIR (SOFT DELETE) ============

    public function excluir()
    {
        $this->exigirMaster();

        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) { $this->flash('ID não informado.'); $this->redirect('/usuarios'); }

        if ($id === (int) $_SESSION['usuario']['id']) {
            $this->flash('Você não pode desativar sua própria conta.');
            $this->redirect('/usuarios');
        }

        $usuario = $this->usuario->findById($id);
        if (!$usuario) { $this->flash('Usuário não encontrado.'); $this->redirect('/usuarios'); }

        $pdo = Database::getConnection();
        $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?")->execute([$id]);

        $this->audit->registrar('usuario_desativado', 'usuarios', $id, null, null);
        $this->flash('Usuário desativado.', 'sucesso');
        $this->redirect('/usuarios');
    }

    // ============ ATRIBUIR TIPO (aluno ↔ master) ============

    public function atribuirPerfilMaster()
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $id   = (int) ($_POST['id_usuario'] ?? 0);
        $tipo = $_POST['tipo'] ?? '';

        if (!$id || !in_array($tipo, ['aluno', 'master'], true)) {
            $this->flash('Dados inválidos.');
            $this->redirect('/usuarios');
        }

        if ($id === (int) $_SESSION['usuario']['id'] && $tipo !== 'master') {
            $this->flash('Você não pode rebaixar sua própria conta.');
            $this->redirect('/usuarios');
        }

        $usuario = $this->usuario->findById($id);
        if (!$usuario) { $this->flash('Usuário não encontrado.'); $this->redirect('/usuarios'); }

        $pdo = Database::getConnection();
        $pdo->prepare("UPDATE usuarios SET tipo = ? WHERE id = ?")->execute([$tipo, $id]);

        $this->audit->registrar('usuario_tipo_alterado', 'usuarios', $id,
            ['tipo' => $usuario['tipo']], ['tipo' => $tipo]);

        $this->flash("Tipo alterado para '{$tipo}'.", 'sucesso');
        $this->redirect('/usuarios');
    }

    public function atribuirPerfilAdmin()
    {
        $this->atribuirPerfilMaster();
    }

    // ============ TORNAR REPRESENTANTE ============

    public function tornarRepresentante()
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $id      = (int) ($_POST['id_usuario'] ?? 0);
        $turmaId = (int) ($_POST['turma_id'] ?? 0);

        if (!$id || !$turmaId) {
            $this->flash('Dados inválidos.');
            $this->redirect('/usuarios');
        }

        $usuario = $this->usuario->findById($id);
        if (!$usuario) { $this->flash('Usuário não encontrado.'); $this->redirect('/usuarios'); }

        $pdo = Database::getConnection();

        // Turma existe?
        $stmt = $pdo->prepare("SELECT id, nome FROM turmas WHERE id = ? LIMIT 1");
        $stmt->execute([$turmaId]);
        $turma = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$turma) { $this->flash('Turma não encontrada.'); $this->redirect('/usuarios'); }

        // Usuário já é rep nesta turma?
        $stmt = $pdo->prepare("
            SELECT id, papel, ativo FROM turma_usuarios
            WHERE turma_id = ? AND usuario_id = ?
            LIMIT 1
        ");
        $stmt->execute([$turmaId, $id]);
        $vinculo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($vinculo && $vinculo['papel'] === 'representante' && $vinculo['ativo']) {
            $this->flash('Este usuário já é representante desta turma.');
            $this->redirect('/usuarios');
        }

        // Conta quantos reps ativos a turma tem
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM turma_usuarios
            WHERE turma_id = ? AND papel = 'representante' AND ativo = 1
        ");
        $stmt->execute([$turmaId]);
        $qtdReps = (int) $stmt->fetchColumn();

        if ($qtdReps >= 2) {
            $this->flash('Esta turma já tem 2 representantes ativos. Remova um antes.');
            $this->redirect('/usuarios');
        }

        // Aplica
        if ($vinculo) {
            $pdo->prepare("
                UPDATE turma_usuarios
                SET papel = 'representante', ativo = 1, saiu_em = NULL
                WHERE id = ?
            ")->execute([(int) $vinculo['id']]);
        } else {
            $pdo->prepare("
                INSERT INTO turma_usuarios (turma_id, usuario_id, papel, ativo)
                VALUES (?, ?, 'representante', 1)
            ")->execute([$turmaId, $id]);
        }

        // Notifica o usuário
        try {
            (new Notificacao())->criar(
                $id,
                'papel',
                'Você agora é representante',
                "Você foi nomeado representante da turma '{$turma['nome']}'.",
                '/turmas/' . $turmaId
            );
        } catch (Throwable $e) {}

        $this->audit->registrar('usuario_tornado_representante', 'turma_usuarios', null,
            null, ['usuario_id' => $id, 'turma_id' => $turmaId, 'turma_nome' => $turma['nome']]);

        $this->flash("Usuário agora é representante da turma '{$turma['nome']}'.", 'sucesso');
        $this->redirect('/usuarios');
    }

    // ============ REMOVER REPRESENTANTE ============

    public function removerRepresentante()
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $id      = (int) ($_POST['id_usuario'] ?? 0);
        $turmaId = (int) ($_POST['turma_id'] ?? 0);

        if (!$id || !$turmaId) {
            $this->flash('Dados inválidos.');
            $this->redirect('/usuarios');
        }

        $pdo = Database::getConnection();

        // Verifica se é o último rep da turma
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM turma_usuarios
            WHERE turma_id = ? AND papel = 'representante' AND ativo = 1
        ");
        $stmt->execute([$turmaId]);
        $qtdReps = (int) $stmt->fetchColumn();

        if ($qtdReps <= 1) {
            $this->flash('Não é possível remover o último representante da turma. Nomeie outro antes.');
            $this->redirect('/usuarios');
        }

        // Troca para aluno
        $pdo->prepare("
            UPDATE turma_usuarios
            SET papel = 'aluno'
            WHERE turma_id = ? AND usuario_id = ? AND papel = 'representante' AND ativo = 1
        ")->execute([$turmaId, $id]);

        $this->audit->registrar('usuario_removido_representante', 'turma_usuarios', null,
            ['usuario_id' => $id, 'turma_id' => $turmaId], null);

        $this->flash('Representante removido.', 'sucesso');
        $this->redirect('/usuarios');
    }
}