<?php
// app/Controllers/AuthController.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/ResetSenha.php';
require_once __DIR__ . '/../Models/PreferenciasUsuario.php';
require_once __DIR__ . '/../Helpers/PasswordHelper.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';
require_once __DIR__ . '/../Helpers/RateLimiter.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Core/Mail.php';
require_once __DIR__ . '/../Services/GoogleAuthService.php';

class AuthController
{
    private Usuario              $usuario;
    private ResetSenha           $reset;
    private PreferenciasUsuario  $prefs;
    private Mail                 $mail;

    public function __construct()
    {
        $this->usuario = new Usuario();
        $this->reset   = new ResetSenha();
        $this->prefs   = new PreferenciasUsuario();
        $this->mail    = new Mail();
    }

    // ============ LOGIN ============

    public function loginForm()
    {
        $this->render('auth/login');
    }

    public function login()
    {
        CsrfMiddleware::validate();

        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $ip    = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key   = "login:{$email}:{$ip}";

        if (!RateLimiter::check($key, 5, 300)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Muitas tentativas. Tente novamente em 5 minutos.'];
            $this->redirect('/login');
        }

        $user = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? $this->usuario->findByEmail($email)
            : null;

        if (!$user || !PasswordHelper::verify($senha, $user['senha'])) {
            RateLimiter::increment($key);
            SecurityHelper::logAuditoria('login_falha', null, "Email: {$email}", 'warning');
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Email ou senha incorretos.'];
            $this->redirect('/login');
        }

        if (!$user['ativo']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Conta inativa. Contate o administrador.'];
            $this->redirect('/login');
        }

        RateLimiter::reset($key);
        session_regenerate_id(true);

        $this->montarSessao($user);

        $this->usuario->atualizarUltimoAcesso((int) $user['id']);
        SecurityHelper::logAuditoria('login', $user['id'], 'Login OK', 'info');

        $this->redirect($this->destinoPosLogin());
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['usuario'])) {
            SecurityHelper::logAuditoria('logout', $_SESSION['usuario']['id'], 'Logout', 'info');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        $this->redirect('/');
    }

    // ============ CADASTRO ============

    public function cadastroForm()
    {
        $this->render('auth/cadastro');
    }

    public function salvarCadastro()
    {
        CsrfMiddleware::validate();

        $nome      = trim($_POST['nome'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $senha     = $_POST['senha'] ?? '';
        $telefone  = trim($_POST['telefone'] ?? '') ?: null;
        $nasc      = trim($_POST['data_nascimento'] ?? '') ?: null;
        $lgpd      = !empty($_POST['lgpd']);

        $erros = [];

        if (strlen($nome) < 3)                          $erros[] = 'Nome deve ter pelo menos 3 caracteres.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
        if ($this->usuario->emailExiste($email))        $erros[] = 'Este email já está cadastrado.';
        if (!$lgpd)                                     $erros[] = 'Você precisa aceitar os termos LGPD.';

        $r = SecurityHelper::validarForcaSenha($senha);
        if (!$r['valida']) $erros = array_merge($erros, $r['erros']);

        if ($nasc) {
            $dt = DateTime::createFromFormat('Y-m-d', $nasc);
            if (!$dt) {
                $erros[] = 'Data de nascimento inválida.';
            } else {
                $idade = (new DateTime())->diff($dt)->y;
                if ($idade < 12) $erros[] = 'Idade mínima: 12 anos.';
            }
        }

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/login/cadastrar');
        }

        $token  = SecurityHelper::gerarToken();
        $expira = date('Y-m-d H:i:s', time() + 24 * 3600);

        $id = $this->usuario->criar([
            'nome'                => $nome,
            'email'               => $email,
            'senha'               => PasswordHelper::hash($senha),
            'tipo'                => 'aluno',
            'telefone'            => $telefone,
            'data_nascimento'     => $nasc,
            'email_confirmado'    => 0,
            'email_token'         => $token,
            'email_token_expira'  => $expira,
            'primeiro_login'      => 0, // já escolheu a senha e aceitou a LGPD no cadastro
            'ativo'               => 1,
            'lgpd_aceito'         => 1,
            'lgpd_data'           => date('Y-m-d H:i:s'),
        ]);

        $this->prefs->criarPadrao($id);
        $this->prefs->definirNivel($id, (string) ($_POST['nivel_email'] ?? 'importantes'));
        $this->mail->emailConfirmacao($email, $nome, $token);

        SecurityHelper::logAuditoria('cadastro', $id, "Auto-cadastro: {$email}", 'info');

        $_SESSION['flash'] = [
            'tipo' => 'sucesso',
            'mensagem' => 'Cadastro realizado! Verifique seu email para confirmar a conta.',
        ];
        $this->redirect('/login');
    }

    // ============ CONFIRMAR EMAIL ============

    public function confirmarEmail()
    {
        $token = trim($_GET['token'] ?? '');
        if (empty($token)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Token inválido.'];
            $this->redirect('/login');
        }

        $user = $this->usuario->findByToken($token);
        if (!$user) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Token inválido ou já utilizado.'];
            $this->redirect('/login');
        }

        if (strtotime($user['email_token_expira']) < time()) {
            $_SESSION['flash'] = ['tipo' => 'aviso', 'mensagem' => 'Token expirado. Faça login para solicitar novo email.'];
            $this->redirect('/login');
        }

        $this->usuario->confirmarEmail((int) $user['id']);
        SecurityHelper::logAuditoria('email_confirmado', $user['id'], 'Email confirmado', 'info');

        // Se já está logado, atualiza a sessão
        if (!empty($_SESSION['usuario']) && $_SESSION['usuario']['id'] === (int) $user['id']) {
            $_SESSION['usuario']['email_confirmado'] = 1;
        }

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Email confirmado com sucesso!'];
        $this->redirect('/login');
    }

    public function reenviarConfirmacao()
    {
        $usuarioLogado = $_SESSION['usuario'] ?? null;
        if (!$usuarioLogado) {
            $this->redirect('/login');
        }

        $user = $this->usuario->findById((int) $usuarioLogado['id']);
        if (!$user) $this->redirect('/login');

        if ($user['email_confirmado']) {
            $_SESSION['flash'] = ['tipo' => 'aviso', 'mensagem' => 'Seu email já está confirmado.'];
            $this->redirect('/');
        }

        $token  = SecurityHelper::gerarToken();
        $expira = date('Y-m-d H:i:s', time() + 24 * 3600);
        $this->usuario->atualizarToken((int) $user['id'], $token, $expira);
        $this->mail->emailConfirmacao($user['email'], $user['nome'], $token);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Email de confirmação reenviado.'];
        $this->redirect('/auth/confirmar-email-pendente');
    }

    public function confirmarEmailPendente()
    {
        $this->render('auth/confirmar_email');
    }

    // ============ RECUPERAÇÃO DE SENHA ============

    public function esqueciSenhaForm()
    {
        $this->render('auth/esqueci_senha');
    }

    public function enviarToken()
    {
        CsrfMiddleware::validate();

        $email = trim($_POST['email'] ?? '');

        // Resposta SEMPRE igual
        $_SESSION['flash'] = [
            'tipo' => 'sucesso',
            'mensagem' => 'Se o email existir, você receberá as instruções.',
        ];

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = $this->usuario->findByEmail($email);
            if ($user && $user['ativo']) {
                $token  = SecurityHelper::gerarToken();
                $expira = date('Y-m-d H:i:s', time() + 2 * 3600);
                $this->reset->criar((int) $user['id'], $token, $expira);
                $this->mail->emailResetSenha($email, $user['nome'], $token);
                SecurityHelper::logAuditoria('reset_solicitado', $user['id'], "Email: {$email}", 'info');
            }
        }

        $this->redirect('/login');
    }

    public function redefinirForm()
    {
        $token = trim($_GET['token'] ?? '');
        $reg   = $this->reset->buscarValido($token);

        if (!$reg) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Token inválido, expirado ou já utilizado.'];
            $this->redirect('/login');
        }

        $this->render('auth/redefinir_senha', ['token' => $token]);
    }

    public function redefinirSenha()
    {
        CsrfMiddleware::validate();

        $token = trim($_POST['token'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $conf  = $_POST['senha_confirmacao'] ?? '';

        $reg = $this->reset->buscarValido($token);
        if (!$reg) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Token inválido ou expirado.'];
            $this->redirect('/login');
        }

        if ($senha !== $conf) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'As senhas não conferem.'];
            $this->redirect('/auth/redefinir?token=' . urlencode($token));
        }

        $r = SecurityHelper::validarForcaSenha($senha);
        if (!$r['valida']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Senha fraca: ' . implode(' | ', $r['erros'])];
            $this->redirect('/auth/redefinir?token=' . urlencode($token));
        }

        $user = $this->usuario->findById((int) $reg['usuario_id']);
        $this->usuario->atualizarSenha((int) $user['id'], PasswordHelper::hash($senha));
        $this->reset->marcarUsado((int) $reg['id']);
        $this->mail->emailSenhaAlterada($user['email'], $user['nome']);

        SecurityHelper::logAuditoria('senha_redefinida', $user['id'], 'Senha redefinida via token', 'info');

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Senha alterada com sucesso! Faça login.'];
        $this->redirect('/login');
    }

    // ============ PRIMEIRO ACESSO ============

    public function primeiroAcessoForm()
    {
        if (empty($_SESSION['usuario'])) $this->redirect('/login');

        $user = $this->usuario->findById((int) $_SESSION['usuario']['id']);
        if (!$user) $this->redirect('/logout');

        // Se já concluiu, manda pra home
        if (!$user['primeiro_login']) $this->redirect('/');

        $this->render('auth/primeiro_acesso', ['user' => $user]);
    }

    public function completarPrimeiroAcesso()
    {
        CsrfMiddleware::validate();

        if (empty($_SESSION['usuario'])) $this->redirect('/login');

        $user = $this->usuario->findById((int) $_SESSION['usuario']['id']);
        if (!$user) $this->redirect('/logout');

        $senha = $_POST['senha'] ?? '';
        $conf  = $_POST['senha_confirmacao'] ?? '';
        $lgpd  = !empty($_POST['lgpd']);

        if (!$user['email_confirmado']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Confirme seu email antes de continuar.'];
            $this->redirect('/primeiro-acesso');
        }

        if ($senha !== $conf) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'As senhas não conferem.'];
            $this->redirect('/primeiro-acesso');
        }

        $r = SecurityHelper::validarForcaSenha($senha);
        if (!$r['valida']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Senha fraca: ' . implode(' | ', $r['erros'])];
            $this->redirect('/primeiro-acesso');
        }

        if (!$lgpd) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Você precisa aceitar a política LGPD.'];
            $this->redirect('/primeiro-acesso');
        }

        $this->usuario->concluirPrimeiroAcesso((int) $user['id'], PasswordHelper::hash($senha));
        $this->prefs->definirNivel((int) $user['id'], (string) ($_POST['nivel_email'] ?? 'importantes'));

        $_SESSION['usuario']['primeiro_login']   = 0;
        $_SESSION['usuario']['lgpd_aceito']      = 1;
        $_SESSION['usuario']['email_confirmado'] = 1;

        SecurityHelper::logAuditoria('primeiro_acesso_concluido', $user['id'], 'Fluxo concluído', 'info');

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Tudo pronto! Bem-vindo ao sistema.'];
        $this->redirect('/');
    }

    // ============ LOGIN COM GOOGLE ============

    public function googleIniciar()
    {
        if (!GoogleAuthService::configurado()) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'O login com Google ainda não foi configurado.'];
            $this->redirect('/login');
        }
        $_SESSION['google_state'] = bin2hex(random_bytes(16));
        header('Location: ' . GoogleAuthService::urlLogin($_SESSION['google_state']));
        exit;
    }

    /**
     * Volta do Google. Entra na conta ligada ao Google ou com o mesmo email; se não existir, cria.
     * O Google já verificou o email: não pede confirmação. Conta nova (ou sem LGPD) completa nome e telefone.
     * Vindo de um convite (sessão convite_token), entra na conta convidada.
     */
    public function googleCallback()
    {
        $state = $_SESSION['google_state'] ?? '';
        unset($_SESSION['google_state']);
        if (!empty($_GET['error'])) {
            $_SESSION['flash'] = ['tipo' => 'aviso', 'mensagem' => 'Login com Google cancelado.'];
            $this->redirect('/login');
        }
        if ($state === '' || !hash_equals($state, (string) ($_GET['state'] ?? ''))) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'A sessão do login com Google expirou. Tente de novo.'];
            $this->redirect('/login');
        }

        try {
            $perfil = GoogleAuthService::perfilPeloCodigo((string) ($_GET['code'] ?? ''));
        } catch (Exception $e) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => $e->getMessage()];
            $this->redirect('/login');
        }
        // Veio do perfil ("Conectar com Google"): só liga a conta Google à conta já logada
        $vincular = (int) ($_SESSION['google_vincular'] ?? 0);
        unset($_SESSION['google_vincular']);
        if ($vincular && (int) ($_SESSION['usuario']['id'] ?? 0) === $vincular) {
            $dono = $this->usuario->findByGoogleId($perfil['sub']);
            if ($dono && (int) $dono['id'] !== $vincular) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Esta conta Google já está conectada a outro usuário do ' . App::getName() . '.'];
            } else {
                $this->usuario->ligarGoogle($vincular, $perfil['sub']);
                SecurityHelper::logAuditoria('google_conectado', $vincular, "Conta Google conectada: {$perfil['email']}", 'info');
                $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => "Conta Google ({$perfil['email']}) conectada. Agora você também pode entrar com o Google."];
            }
            $this->redirect('/user');
        }

        $user = $this->usuarioDoGoogle($perfil);

        if (!$user['ativo']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Conta inativa. Contate o administrador.'];
            $this->redirect('/login');
        }
        session_regenerate_id(true);
        $this->montarSessao($user);
        $this->usuario->atualizarUltimoAcesso((int) $user['id']);
        SecurityHelper::logAuditoria('login', $user['id'], 'Login com Google', 'info');
        $this->redirect($user['cadastro_pendente'] ? '/completar-cadastro' : $this->destinoPosLogin());
    }

    /** Acha (ou cria) o usuário do perfil do Google. Separado do callback para dar para testar. */
    public function usuarioDoGoogle(array $perfil): array
    {
        $porGoogle = $this->usuario->findByGoogleId($perfil['sub']);

        // Vindo de um convite: a conta é a convidada
        $tokenConvite = $_SESSION['convite_token'] ?? null;
        unset($_SESSION['convite_token']);
        if ($tokenConvite) {
            require_once __DIR__ . '/../Models/Convite.php';
            $convites = new Convite();
            $convite  = $convites->validoPorToken($tokenConvite);
            if (!$convite) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Convite inválido ou expirado. Peça um novo ao representante.'];
                $this->redirect('/login');
            }
            if ($porGoogle && (int) $porGoogle['id'] !== (int) $convite['usuario_id']) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Esta conta Google já está ligada a outro usuário. Entre com ela e peça ao representante um convite para esse email.'];
                $this->redirect('/convite/' . urlencode($tokenConvite));
            }
            $this->usuario->vincularGoogle((int) $convite['usuario_id'], $perfil['sub']);
            $convites->aceitar((int) $convite['id']);
            return $this->usuario->findById((int) $convite['usuario_id']);
        }

        $user = $porGoogle ?: $this->usuario->findByEmail($perfil['email']);
        if ($user) {
            if (empty($user['google_id'])) $this->usuario->vincularGoogle((int) $user['id'], $perfil['sub']);
            return $this->usuario->findById((int) $user['id']);
        }

        $id = $this->usuario->criar([
            'nome'              => $perfil['nome'] ?: strstr($perfil['email'], '@', true),
            'email'             => $perfil['email'],
            'senha'             => PasswordHelper::hash(bin2hex(random_bytes(24))), // entra pelo Google
            'tipo'              => 'aluno',
            'email_confirmado'  => 1,
            'primeiro_login'    => 0,
            'cadastro_pendente' => 1,
            'google_id'         => $perfil['sub'],
            'ativo'             => 1,
            'lgpd_aceito'       => 0,
        ]);
        $this->prefs->criarPadrao($id);
        SecurityHelper::logAuditoria('cadastro', $id, "Cadastro com Google: {$perfil['email']}", 'info');
        return $this->usuario->findById($id);
    }

    // ============ COMPLETAR CADASTRO (GOOGLE / CONVITE) ============

    public function completarCadastroForm()
    {
        if (empty($_SESSION['usuario'])) $this->redirect('/login');
        $user = $this->usuario->findById((int) $_SESSION['usuario']['id']);
        if (!$user || !$user['cadastro_pendente']) $this->redirect('/');
        $this->render('auth/completar_cadastro', ['user' => $user, 'antigo' => $_SESSION['completar_form'] ?? []]);
        unset($_SESSION['completar_form']);
    }

    public function completarCadastro()
    {
        CsrfMiddleware::validate();
        if (empty($_SESSION['usuario'])) $this->redirect('/login');
        $user = $this->usuario->findById((int) $_SESSION['usuario']['id']);
        if (!$user || !$user['cadastro_pendente']) $this->redirect('/');

        [$nome, $telefone, $erros] = self::lerNomeTelefone($_POST);
        if (empty($_POST['lgpd'])) $erros[] = 'Você precisa aceitar a Política de Privacidade (LGPD).';
        if ($erros) {
            $_SESSION['completar_form'] = $_POST;
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/completar-cadastro');
        }

        $this->usuario->completarCadastro((int) $user['id'], $nome, $telefone);
        $this->prefs->definirNivel((int) $user['id'], (string) ($_POST['nivel_email'] ?? 'importantes'));
        $this->montarSessao($this->usuario->findById((int) $user['id']));
        SecurityHelper::logAuditoria('cadastro_completado', $user['id'], 'Nome e telefone informados', 'info');

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Tudo pronto! Bem-vindo ao ' . App::getName() . '.'];
        $this->redirect($this->destinoPosLogin());
    }

    /** Nome completo e telefone (BR, 10 ou 11 dígitos). Retorna [nome, telefone formatado, erros]. */
    public static function lerNomeTelefone(array $post): array
    {
        $nome = preg_replace('/\s+/', ' ', trim((string) ($post['nome'] ?? '')));
        $tel  = preg_replace('/\D/', '', (string) ($post['telefone'] ?? ''));
        $erros = [];
        if (mb_strlen($nome) < 5 || !str_contains($nome, ' ')) $erros[] = 'Informe o nome completo (nome e sobrenome).';
        if (mb_strlen($nome) > 150) $nome = mb_substr($nome, 0, 150);
        if (strlen($tel) < 10 || strlen($tel) > 11) $erros[] = 'Telefone inválido: use DDD + número.';
        $fmt = strlen($tel) === 11 ? sprintf('(%s) %s-%s', substr($tel, 0, 2), substr($tel, 2, 5), substr($tel, 7))
             : (strlen($tel) === 10 ? sprintf('(%s) %s-%s', substr($tel, 0, 2), substr($tel, 2, 4), substr($tel, 6)) : null);
        return [$nome, $fmt, $erros];
    }

    // ============ HELPERS ============

    /** Dados do usuário na sessão (usados pelo Router e pelas telas). */
    public function montarSessao(array $user): void
    {
        $_SESSION['usuario'] = [
            'id'                => (int) $user['id'],
            'nome'              => $user['nome'],
            'email'             => $user['email'],
            'tipo'              => $user['tipo'],
            'role'              => $user['tipo'],   // alias usado pelo Router
            'primeiro_login'    => (int) $user['primeiro_login'],
            'email_confirmado'  => (int) $user['email_confirmado'],
            'lgpd_aceito'       => (int) $user['lgpd_aceito'],
            'cadastro_pendente' => (int) ($user['cadastro_pendente'] ?? 0),
        ];
    }

    private function render(string $view, array $dados = []): void
    {
        extract($dados);
        require __DIR__ . '/../Views/' . $view . '.php';
    }

    /** Página que a pessoa tentou abrir antes do login (guardada pelo Router), ou o início. Só caminhos internos. */
    private function destinoPosLogin(): string
    {
        $d = $_SESSION['voltar_depois_login'] ?? '/';
        unset($_SESSION['voltar_depois_login']);
        return (is_string($d) && preg_match('#^/(?!/)[A-Za-z0-9/_\-]*$#', $d)) ? $d : '/';
    }

    private function redirect(string $path): void
    {
        header('Location: ' . App::getBasePath() . $path);
        exit;
    }
}