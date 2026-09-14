<?php
// app/Controllers/UserController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/PreferenciasUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/PasswordHelper.php';
require_once __DIR__ . '/../Helpers/SecurityHelper.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class UserController
{
    private Usuario              $usuario;
    private PreferenciasUsuario  $prefs;
    private Auditoria            $audit;

    public function __construct()
    {
        $this->usuario = new Usuario();
        $this->prefs   = new PreferenciasUsuario();
        $this->audit   = new Auditoria();
    }

    // ============ PERFIL ============

    public function index()
    {
        if (empty($_SESSION['usuario'])) { $this->redirect('/login'); }

        $user = $this->usuario->findById((int) $_SESSION['usuario']['id']);
        unset($user['senha']);

        $this->render('user/index', [
            'user'   => $user,
            'prefs'  => $this->prefs->porUsuario((int) $user['id']),
        ]);
    }

    public function editar()
    {
        if (empty($_SESSION['usuario'])) { $this->redirect('/login'); }

        $user = $this->usuario->findById((int) $_SESSION['usuario']['id']);
        unset($user['senha']);

        $this->render('user/editar', ['user' => $user]);
    }

    public function atualizar()
    {
        CsrfMiddleware::validate();
        if (empty($_SESSION['usuario'])) { $this->redirect('/login'); }

        $id        = (int) $_SESSION['usuario']['id'];
        $nome      = trim($_POST['nome'] ?? '');
        $telefone  = trim($_POST['telefone'] ?? '') ?: null;
        $nasc      = trim($_POST['data_nascimento'] ?? '') ?: null;

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

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/user/editar');
        }

        $pdo = Database::getConnection();
        $pdo->prepare("
            UPDATE usuarios SET nome = ?, telefone = ?, data_nascimento = ? WHERE id = ?
        ")->execute([$nome, $telefone, $nasc, $id]);

        // Atualiza sessão
        $_SESSION['usuario']['nome'] = $nome;

        $this->audit->registrar('usuario_atualizou_perfil', 'usuarios', $id, null,
            ['nome' => $nome, 'telefone' => $telefone]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Perfil atualizado.'];
        $this->redirect('/user');
    }

    // ============ TROCAR SENHA ============

    public function senha()
    {
        if (empty($_SESSION['usuario'])) { $this->redirect('/login'); }
        $this->render('user/senha');
    }

    public function salvarSenha()
    {
        CsrfMiddleware::validate();
        if (empty($_SESSION['usuario'])) { $this->redirect('/login'); }

        $id    = (int) $_SESSION['usuario']['id'];
        $atual = $_POST['senha_atual'] ?? '';
        $nova  = $_POST['senha_nova'] ?? '';
        $conf  = $_POST['senha_confirmacao'] ?? '';

        $user = $this->usuario->findById($id);
        if (!$user || !PasswordHelper::verify($atual, $user['senha'])) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Senha atual incorreta.'];
            $this->redirect('/user/senha');
        }

        if ($nova !== $conf) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'As senhas não conferem.'];
            $this->redirect('/user/senha');
        }

        if (PasswordHelper::verify($nova, $user['senha'])) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'A nova senha deve ser diferente da atual.'];
            $this->redirect('/user/senha');
        }

        $r = SecurityHelper::validarForcaSenha($nova);
        if (!$r['valida']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Senha fraca: ' . implode(' | ', $r['erros'])];
            $this->redirect('/user/senha');
        }

        $this->usuario->atualizarSenha($id, PasswordHelper::hash($nova));

        $this->audit->registrar('usuario_alterou_senha', 'usuarios', $id, null, null);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Senha alterada com sucesso.'];
        $this->redirect('/user');
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