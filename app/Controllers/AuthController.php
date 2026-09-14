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

        $_SESSION['usuario'] = [
            'id'               => (int) $user['id'],
            'nome'             => $user['nome'],
            'email'            => $user['email'],
            'tipo'             => $user['tipo'],
            'role'             => $user['tipo'],   // alias usado pelo Router
            'primeiro_login'   => (int) $user['primeiro_login'],
            'email_confirmado' => (int) $user['email_confirmado'],
            'lgpd_aceito'      => (int) $user['lgpd_aceito'],
        ];

        $this->usuario->atualizarUltimoAcesso((int) $user['id']);
        SecurityHelper::logAuditoria('login', $user['id'], 'Login OK', 'info');

        $this->redirect('/');
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
            'primeiro_login'      => 1,
            'ativo'               => 1,
            'lgpd_aceito'         => 1,
            'lgpd_data'           => date('Y-m-d H:i:s'),
        ]);

        $this->prefs->criarPadrao($id);
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

        $_SESSION['usuario']['primeiro_login']   = 0;
        $_SESSION['usuario']['lgpd_aceito']      = 1;
        $_SESSION['usuario']['email_confirmado'] = 1;

        SecurityHelper::logAuditoria('primeiro_acesso_concluido', $user['id'], 'Fluxo concluído', 'info');

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Tudo pronto! Bem-vindo ao sistema.'];
        $this->redirect('/');
    }

    // ============ HELPERS ============

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