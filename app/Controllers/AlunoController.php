<?php
// app/Controllers/AlunoController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/PreferenciasUsuario.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/PasswordHelper.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Core/Mail.php';

class AlunoController
{
    private Usuario              $usuario;
    private PreferenciasUsuario  $prefs;
    private Turma                $turma;
    private TurmaUsuario         $tu;
    private Notificacao          $notif;
    private Auditoria            $audit;
    private Mail                 $mail;

    public function __construct()
    {
        $this->usuario = new Usuario();
        $this->prefs   = new PreferenciasUsuario();
        $this->turma   = new Turma();
        $this->tu      = new TurmaUsuario();
        $this->notif   = new Notificacao();
        $this->audit   = new Auditoria();
        $this->mail    = new Mail();
    }

    // ============ LISTAGEM ============

    public function index()
    {
        $u = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';

        $pdo = Database::getConnection();

        if ($isMaster) {
            // Master vê todos os alunos
            $stmt = $pdo->query("
                SELECT u.*,
                       (SELECT GROUP_CONCAT(t.nome SEPARATOR ', ')
                        FROM turma_usuarios tu
                        INNER JOIN turmas t ON t.id = tu.turma_id
                        WHERE tu.usuario_id = u.id AND tu.ativo = 1) AS turmas
                FROM usuarios u
                WHERE u.tipo = 'aluno'
                ORDER BY u.nome ASC
                LIMIT 500
            ");
            $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $minhasTurmas = $this->turma->listarTodas();
        } else {
            // Representante vê apenas alunos das suas turmas
            $minhasTurmas = $this->turmasQueSouRep((int) $u['id']);

            if (empty($minhasTurmas)) {
                http_response_code(403);
                exit('Você não é representante de nenhuma turma.');
            }

            $idsTurmas = array_map(fn($t) => (int) $t['id'], $minhasTurmas);
            $ph = implode(',', array_fill(0, count($idsTurmas), '?'));

            $stmt = $pdo->prepare("
                SELECT DISTINCT u.*,
                       (SELECT GROUP_CONCAT(t2.nome SEPARATOR ', ')
                        FROM turma_usuarios tu2
                        INNER JOIN turmas t2 ON t2.id = tu2.turma_id
                        WHERE tu2.usuario_id = u.id AND tu2.ativo = 1) AS turmas
                FROM usuarios u
                INNER JOIN turma_usuarios tu ON tu.usuario_id = u.id
                WHERE u.tipo = 'aluno' AND tu.turma_id IN ($ph)
                ORDER BY u.nome ASC
                LIMIT 500
            ");
            $stmt->execute($idsTurmas);
            $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->render('alunos/index', [
            'alunos'       => $alunos,
            'minhasTurmas' => $minhasTurmas,
            'isMaster'     => $isMaster,
        ]);
    }

    // ============ CRIAR ALUNO INDIVIDUAL ============

    public function criar()
    {
        $u = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';

        if ($isMaster) {
            $minhasTurmas = $this->turma->listarTodas();
        } else {
            $minhasTurmas = $this->turmasQueSouRep((int) $u['id']);
            if (empty($minhasTurmas)) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Você não é representante de nenhuma turma.'];
                $this->redirect('/turmas');
            }
        }

        $this->render('alunos/criar', [
            'minhasTurmas' => $minhasTurmas,
            'isMaster'     => $isMaster,
        ]);
    }

    public function salvar()
    {
        CsrfMiddleware::validate();

        $u        = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';

        $nome   = trim($_POST['nome'] ?? '');
        $email  = strtolower(trim($_POST['email'] ?? ''));
        $turmasSelecionadas = array_map('intval', $_POST['turmas'] ?? []);

        // Representante só pode criar na(s) turma(s) que representa
        if (!$isMaster) {
            $minhasTurmas = $this->turmasQueSouRep((int) $u['id']);
            $idsPermitidos = array_map(fn($t) => (int) $t['id'], $minhasTurmas);

            if (empty($turmasSelecionadas)) {
                $turmasSelecionadas = $idsPermitidos; // default: todas as dele
            }

            foreach ($turmasSelecionadas as $tid) {
                if (!in_array($tid, $idsPermitidos, true)) {
                    $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Você só pode adicionar alunos nas suas turmas.'];
                    $this->redirect('/alunos/criar');
                }
            }
        }

        $erros = [];
        if (strlen($nome) < 3)                              $erros[] = 'Nome muito curto.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $erros[] = 'Email inválido.';
        if ($this->usuario->emailExiste($email))            $erros[] = 'Este email já está cadastrado.';
        if (empty($turmasSelecionadas))                     $erros[] = 'Selecione pelo menos uma turma.';

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/alunos/criar');
        }

        // 1. Gera senha temporária
        $senhaTemp = PasswordHelper::generateTemp(10);

        // 2. Gera token de primeiro acesso (24h)
        $token  = SecurityHelper::gerarToken();
        $expira = date('Y-m-d H:i:s', time() + 24 * 3600);

        // 3. Cria usuário
        $id = $this->usuario->criar([
            'nome'               => $nome,
            'email'              => $email,
            'senha'              => PasswordHelper::hash($senhaTemp),
            'tipo'               => 'aluno',
            'email_confirmado'   => 0,
            'email_token'        => $token,
            'email_token_expira' => $expira,
            'primeiro_login'     => 1,
            'ativo'              => 1,
            'lgpd_aceito'        => 0,
        ]);

        $this->prefs->criarPadrao($id);

        // 4. Insere nas turmas
        foreach ($turmasSelecionadas as $tid) {
            $this->tu->adicionar($tid, $id, 'aluno');
        }

        // 5. Envia email de primeiro acesso
        $this->mail->emailPrimeiroAcesso($email, $nome, $token, $senhaTemp);

        // 6. Notificação no sistema
        $this->notif->criar(
            $id,
            'papel',
            'Bem-vindo ao ' . App::getName(),
            'Sua conta foi criada. Complete o primeiro acesso para começar.',
            '/primeiro-acesso'
        );

        $this->audit->registrar('aluno_criado', 'usuarios', $id, null, [
            'nome'    => $nome,
            'email'   => $email,
            'turmas'  => $turmasSelecionadas,
            'por'     => $u['id'],
        ]);

        $_SESSION['flash'] = [
            'tipo' => 'sucesso',
            'mensagem' => "Aluno criado. Senha temporária enviada por email: {$senhaTemp}",
        ];
        $this->redirect('/alunos');
    }

    // ============ CRIAÇÃO EM MASSA ============

    public function massa()
    {
        $u = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';

        if ($isMaster) {
            $minhasTurmas = $this->turma->listarTodas();
        } else {
            $minhasTurmas = $this->turmasQueSouRep((int) $u['id']);
            if (empty($minhasTurmas)) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Você não é representante de nenhuma turma.'];
                $this->redirect('/turmas');
            }
        }

        $this->render('alunos/massa', [
            'minhasTurmas' => $minhasTurmas,
            'isMaster'     => $isMaster,
        ]);
    }

    public function massaSalvar()
    {
        CsrfMiddleware::validate();

        $u        = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';

        $linhas = trim($_POST['nomes'] ?? '');
        $turmasSelecionadas = array_map('intval', $_POST['turmas'] ?? []);

        if ($linhas === '') {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Cole ao menos um nome.'];
            $this->redirect('/alunos/massa');
        }

        // Representante só pode usar suas turmas
        if (!$isMaster) {
            $minhasTurmas = $this->turmasQueSouRep((int) $u['id']);
            $idsPermitidos = array_map(fn($t) => (int) $t['id'], $minhasTurmas);
            if (empty($turmasSelecionadas)) $turmasSelecionadas = $idsPermitidos;
            foreach ($turmasSelecionadas as $tid) {
                if (!in_array($tid, $idsPermitidos, true)) {
                    $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Turma inválida.'];
                    $this->redirect('/alunos/massa');
                }
            }
        }

        if (empty($turmasSelecionadas)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Selecione pelo menos uma turma.'];
            $this->redirect('/alunos/massa');
        }

        $nomes = preg_split('/\r\n|\r|\n/', $linhas);
        $nomes = array_filter(array_map('trim', $nomes), fn($n) => $n !== '');

        $criados   = 0;
        $erros     = [];
        $criadosEmails = []; // para envio de emails em lote

        foreach ($nomes as $nome) {
            if (strlen($nome) < 3) {
                $erros[] = "{$nome}: nome muito curto";
                continue;
            }

            // Gera email único
            $email = $this->gerarEmailUnico($nome);

            $senhaTemp = PasswordHelper::generateTemp(10);
            $token     = SecurityHelper::gerarToken();
            $expira    = date('Y-m-d H:i:s', time() + 24 * 3600);

            try {
                $id = $this->usuario->criar([
                    'nome'               => $nome,
                    'email'              => $email,
                    'senha'              => PasswordHelper::hash($senhaTemp),
                    'tipo'               => 'aluno',
                    'email_confirmado'   => 0,
                    'email_token'        => $token,
                    'email_token_expira' => $expira,
                    'primeiro_login'     => 1,
                    'ativo'              => 1,
                    'lgpd_aceito'        => 0,
                ]);

                $this->prefs->criarPadrao($id);

                foreach ($turmasSelecionadas as $tid) {
                    $this->tu->adicionar($tid, $id, 'aluno');
                }

                $criadosEmails[] = ['id' => $id, 'email' => $email, 'nome' => $nome, 'senha' => $senhaTemp, 'token' => $token];
                $criados++;

            } catch (Throwable $e) {
                $erros[] = "{$nome}: " . $e->getMessage();
            }
        }

        // Envia emails após o lote
        foreach ($criadosEmails as $aluno) {
            try {
                $this->mail->emailPrimeiroAcesso($aluno['email'], $aluno['nome'], $aluno['token'], $aluno['senha']);
            } catch (Throwable $e) {
                // ignora falha de email individual
            }
        }

        $this->audit->registrar('alunos_criados_massa', 'usuarios', null, null, [
            'total_criados' => $criados,
            'total_erros'   => count($erros),
            'turmas'        => $turmasSelecionadas,
            'por'           => $u['id'],
        ]);

        // Salva resumo na sessão para exibir
        $_SESSION['massa_resultado'] = [
            'criados' => $criados,
            'erros'   => $erros,
        ];

        $this->redirect('/alunos/massa/resultado');
    }

    public function massaResultado()
    {
        if (empty($_SESSION['massa_resultado'])) {
            $this->redirect('/alunos');
        }

        $resultado = $_SESSION['massa_resultado'];
        unset($_SESSION['massa_resultado']);

        $this->render('alunos/massa_resultado', ['resultado' => $resultado]);
    }

    // ============ AÇÕES ============

    public function alternarAtivo(int $id)
    {
        CsrfMiddleware::validate();

        $u = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';

        $aluno = $this->usuario->findById($id);
        if (!$aluno || $aluno['tipo'] !== 'aluno') {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Aluno não encontrado.'];
            $this->redirect('/alunos');
        }

        // Representante só pode alternar alunos das suas turmas
        if (!$isMaster) {
            $minhasTurmas = $this->turmasQueSouRep((int) $u['id']);
            $idsPermitidos = array_map(fn($t) => (int) $t['id'], $minhasTurmas);

            $pdo = Database::getConnection();
            $ph = implode(',', array_fill(0, count($idsPermitidos), '?'));
            $stmt = $pdo->prepare("
                SELECT 1 FROM turma_usuarios
                WHERE usuario_id = ? AND turma_id IN ($ph) AND ativo = 1
                LIMIT 1
            ");
            $stmt->execute(array_merge([$id], $idsPermitidos));
            if (!$stmt->fetchColumn()) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Sem permissão.'];
                $this->redirect('/alunos');
            }
        }

        $novoAtivo = $aluno['ativo'] ? 0 : 1;
        $pdo = Database::getConnection();
        $pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?")->execute([$novoAtivo, $id]);

        $this->audit->registrar('aluno_ativo_alterado', 'usuarios', $id,
            ['ativo' => $aluno['ativo']], ['ativo' => $novoAtivo]);

        $_SESSION['flash'] = [
            'tipo' => 'sucesso',
            'mensagem' => $novoAtivo ? 'Aluno ativado.' : 'Aluno desativado.',
        ];
        $this->redirect('/alunos');
    }

        // ============ EDITAR ============

    public function editarForm(int $id)
    {
        $u        = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';

        $aluno = $this->usuario->findById($id);
        if (!$aluno || $aluno['tipo'] !== 'aluno') {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Aluno não encontrado.'];
            $this->redirect('/alunos');
        }

        // Permissão
        if ($isMaster) {
            $minhasTurmas = $this->turma->listarTodas();
        } else {
            $minhasTurmas = $this->turmasQueSouRep((int) $u['id']);
            $idsPermitidas = array_map(fn($t) => (int) $t['id'], $minhasTurmas);

            // Verifica se o aluno está em alguma das suas turmas
            $pdo = Database::getConnection();
            if (!empty($idsPermitidas)) {
                $ph = implode(',', array_fill(0, count($idsPermitidas), '?'));
                $stmt = $pdo->prepare("
                    SELECT 1 FROM turma_usuarios
                    WHERE usuario_id = ? AND turma_id IN ($ph) AND ativo = 1
                    LIMIT 1
                ");
                $stmt->execute(array_merge([$id], $idsPermitidas));
                if (!$stmt->fetchColumn()) {
                    $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Aluno não pertence às suas turmas.'];
                    $this->redirect('/alunos');
                }
            } else {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Você não é representante de nenhuma turma.'];
                $this->redirect('/alunos');
            }
        }

        // Turmas onde o aluno já está
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT turma_id FROM turma_usuarios
            WHERE usuario_id = ? AND ativo = 1
        ");
        $stmt->execute([$id]);
        $turmasDoAluno = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $this->render('alunos/editar', [
            'aluno'         => $aluno,
            'minhasTurmas'  => $minhasTurmas,
            'turmasDoAluno' => $turmasDoAluno,
            'isMaster'      => $isMaster,
        ]);
    }

    public function atualizar(int $id)
    {
        CsrfMiddleware::validate();

        $u        = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';

        $aluno = $this->usuario->findById($id);
        if (!$aluno || $aluno['tipo'] !== 'aluno') {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Aluno não encontrado.'];
            $this->redirect('/alunos');
        }

        // Permissão
        if (!$isMaster) {
            $minhasTurmas = $this->turmasQueSouRep((int) $u['id']);
            $idsPermitidas = array_map(fn($t) => (int) $t['id'], $minhasTurmas);

            if (empty($idsPermitidas)) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Sem permissão.'];
                $this->redirect('/alunos');
            }

            $pdo = Database::getConnection();
            $ph = implode(',', array_fill(0, count($idsPermitidas), '?'));
            $stmt = $pdo->prepare("
                SELECT 1 FROM turma_usuarios
                WHERE usuario_id = ? AND turma_id IN ($ph) AND ativo = 1
                LIMIT 1
            ");
            $stmt->execute(array_merge([$id], $idsPermitidas));
            if (!$stmt->fetchColumn()) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Aluno não pertence às suas turmas.'];
                $this->redirect('/alunos');
            }
        }

        $nome      = trim($_POST['nome'] ?? '');
        $telefone  = trim($_POST['telefone'] ?? '') ?: null;
        $nasc      = trim($_POST['data_nascimento'] ?? '') ?: null;
        $ativo     = isset($_POST['ativo']) ? 1 : 0;
        $turmas    = array_map('intval', $_POST['turmas'] ?? []);

        $erros = [];
        if (strlen($nome) < 3) $erros[] = 'Nome muito curto.';
        if ($nasc) {
            $dt = DateTime::createFromFormat('Y-m-d', $nasc);
            if (!$dt) $erros[] = 'Data de nascimento inválida.';
            else {
                $idade = (new DateTime())->diff($dt)->y;
                if ($idade < 12) $erros[] = 'Idade mínima: 12 anos.';
            }
        }

        // Representante só pode mexer nas próprias turmas
        if (!$isMaster) {
            $idsPermitidas = array_map(fn($t) => (int) $t['id'], $this->turmasQueSouRep((int) $u['id']));
            foreach ($turmas as $tid) {
                if (!in_array($tid, $idsPermitidas, true)) {
                    $erros[] = 'Turma inválida.';
                    break;
                }
            }
        }

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/alunos/' . $id . '/editar');
        }

        $pdo = Database::getConnection();

        // 1. Atualiza dados básicos
        $pdo->prepare("
            UPDATE usuarios
            SET nome = ?, telefone = ?, data_nascimento = ?, ativo = ?
            WHERE id = ?
        ")->execute([$nome, $telefone, $nasc, $ativo, $id]);

        // 2. Sincroniza turmas
        // Ativas atuais
        $stmt = $pdo->prepare("SELECT turma_id FROM turma_usuarios WHERE usuario_id = ? AND ativo = 1");
        $stmt->execute([$id]);
        $turmasAtuais = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        // Adiciona nas novas
        foreach ($turmas as $tid) {
            if (!in_array($tid, $turmasAtuais, true)) {
                $this->tu->adicionar($tid, $id, 'aluno');
            }
        }

        // Remove das que saíram (soft delete via saiu_em e ativo=0)
        foreach ($turmasAtuais as $tid) {
            if (!in_array($tid, $turmas, true)) {
                $pdo->prepare("
                    UPDATE turma_usuarios
                    SET ativo = 0, saiu_em = NOW()
                    WHERE usuario_id = ? AND turma_id = ? AND ativo = 1
                ")->execute([$id, $tid]);
            }
        }

        $this->audit->registrar('aluno_atualizado', 'usuarios', $id,
            ['nome' => $aluno['nome'], 'turmas' => $turmasAtuais],
            ['nome' => $nome, 'turmas' => $turmas]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Aluno atualizado.'];
        $this->redirect('/alunos');
    }

    // ============ GRUPOS DO ALUNO ============

    public function grupos(int $id)
    {
        $u        = $_SESSION['usuario'];
        $isMaster = $u['tipo'] === 'master';

        $aluno = $this->usuario->findById($id);
        if (!$aluno || $aluno['tipo'] !== 'aluno') {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Aluno não encontrado.'];
            $this->redirect('/alunos');
        }

        // Permissão
        if (!$isMaster) {
            $idsPermitidas = array_map(fn($t) => (int) $t['id'], $this->turmasQueSouRep((int) $u['id']));

            if (empty($idsPermitidas)) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Sem permissão.'];
                $this->redirect('/alunos');
            }

            $pdo = Database::getConnection();
            $ph = implode(',', array_fill(0, count($idsPermitidas), '?'));
            $stmt = $pdo->prepare("
                SELECT 1 FROM turma_usuarios
                WHERE usuario_id = ? AND turma_id IN ($ph) AND ativo = 1
                LIMIT 1
            ");
            $stmt->execute(array_merge([$id], $idsPermitidas));
            if (!$stmt->fetchColumn()) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Aluno não pertence às suas turmas.'];
                $this->redirect('/alunos');
            }
        }

        // Grupos do aluno (com projeto/turma)
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT ga.id           AS vinculo_id,
                   ga.entrou_em,
                   ga.saiu_em,
                   g.id            AS grupo_id,
                   g.nome          AS grupo_nome,
                   p.id            AS projeto_id,
                   p.nome          AS projeto_nome,
                   p.encerrado     AS projeto_encerrado,
                   t.id            AS turma_id,
                   t.nome          AS turma_nome,
                   (SELECT COUNT(*) FROM grupo_diretores gd
                    WHERE gd.grupo_id = g.id AND gd.ativo = 1 AND gd.usuario_id = ?) AS sou_diretor
            FROM grupo_alunos ga
            INNER JOIN grupos g ON g.id = ga.grupo_id
            INNER JOIN projetos p ON p.id = g.projeto_id
            INNER JOIN turmas t ON t.id = p.turma_id
            WHERE ga.usuario_id = ?
            ORDER BY ga.saiu_em IS NOT NULL, p.nome ASC, g.nome ASC
        ");
        $stmt->execute([$id, $id]);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Diretorias ativas em projetos (opcional, para mostrar em destaque)
        $stmt = $pdo->prepare("
            SELECT gd.id, gd.nomeado_em,
                   g.id AS grupo_id, g.nome AS grupo_nome,
                   p.id AS projeto_id, p.nome AS projeto_nome,
                   t.nome AS turma_nome
            FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            INNER JOIN projetos p ON p.id = g.projeto_id
            INNER JOIN turmas t ON t.id = p.turma_id
            WHERE gd.usuario_id = ? AND gd.ativo = 1
            ORDER BY p.nome ASC, g.nome ASC
        ");
        $stmt->execute([$id]);
        $diretorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('alunos/grupos', [
            'aluno'       => $aluno,
            'grupos'      => $grupos,
            'diretorias'  => $diretorias,
            'isMaster'    => $isMaster,
        ]);
    }

    // ============ HELPERS ============

    private function turmasQueSouRep(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT t.*
            FROM turmas t
            INNER JOIN turma_usuarios tu ON tu.turma_id = t.id
            WHERE tu.usuario_id = ? AND tu.papel = 'representante' AND tu.ativo = 1
            ORDER BY t.nome ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function gerarEmailUnico(string $nome): string
    {
        // Normaliza: remove acentos, troca espaços por ".", minúsculo
        $base = iconv('UTF-8', 'ASCII//TRANSLIT', $nome);
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', $base));
        $base = trim($base, '.');

        if ($base === '') $base = 'aluno';

        $email = $base . '@aluno.edu';
        $i = 2;

        while ($this->usuario->emailExiste($email)) {
            $email = $base . $i . '@aluno.edu';
            $i++;
        }

        return $email;
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
}