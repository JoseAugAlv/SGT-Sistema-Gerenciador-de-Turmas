<?php
// app/Controllers/UsuarioController.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Helpers/PaginationHelper.php';

class UsuarioController
{
    private $usuario;

    public function __construct()
    {
        $this->usuario = new Usuario();
    }

    /**
     * Lista de usuários (Admin e Master)
     */
    public function index()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $role = (int) $_SESSION['usuario']['role'];
        
        if (!in_array($role, [1, 2])) {
            http_response_code(403);
            echo "<h1>403 - Acesso Negado</h1>";
            echo "<p>Apenas Administradores e Master podem acessar esta página.</p>";
            echo "<a href='" . App::getBasePath() . "/'>Voltar</a>";
            exit;
        }

        $pagina = (int) ($_GET['pagina'] ?? 1);
        $limite = 20;
        
        $total = $this->usuario->getTotal();
        $paginacao = PaginationHelper::paginate($total, $pagina, $limite);
        $usuarios = $this->usuario->getWithPagination($limite, $paginacao['offset']);
        $perfis = $this->usuario->getPerfis();
        
        $paginationHtml = PaginationHelper::render(
            App::getBasePath() . '/usuarios', 
            $pagina, 
            $paginacao['totalPaginas']
        );

        $tituloPagina = 'Gerenciar Usuários - ' . App::getName();
        $cssPagina = 'usuarios.css';
        require '../app/Views/usuarios/index.php';
    }

    /**
     * Formulário de criação (Admin e Master)
     */
    public function criar()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $role = (int) $_SESSION['usuario']['role'];
        
        if (!in_array($role, [1, 2])) {
            http_response_code(403);
            echo "<h1>403 - Acesso Negado</h1>";
            exit;
        }

        $perfis = $this->usuario->getPerfis();
        $tituloPagina = 'Criar Usuário - ' . App::getName();
        $cssPagina = 'usuarios.css';
        require '../app/Views/usuarios/criar.php';
    }

    /**
     * Salvar novo usuário (Admin e Master)
     */
    public function salvar()
    {
        require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
        CsrfMiddleware::validate();

        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $role = (int) $_SESSION['usuario']['role'];
        
        if (!in_array($role, [1, 2])) {
            http_response_code(403);
            echo "<h1>403 - Acesso Negado</h1>";
            exit;
        }

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $dataNascimento = $_POST['data_nascimento'] ?? '';
        $idPerfil = (int) ($_POST['id_perfil'] ?? 4);
        $senha = $_POST['senha'] ?? '';

        if (empty($nome) || empty($email)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Nome e e-mail são obrigatórios.'];
            header('Location: ' . App::getBasePath() . '/usuarios/criar');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'E-mail inválido.'];
            header('Location: ' . App::getBasePath() . '/usuarios/criar');
            exit;
        }

        if ($this->usuario->emailExists($email)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Este e-mail já está cadastrado.'];
            header('Location: ' . App::getBasePath() . '/usuarios/criar');
            exit;
        }

        require_once __DIR__ . '/../Helpers/PasswordHelper.php';

        if (empty($senha)) {
            $senha = PasswordHelper::generateTemp();
        } else {
            $validacao = PasswordHelper::validate($senha);
            if (!$validacao['valid']) {
                $_SESSION['flash'] = [
                    'tipo' => 'erro', 
                    'mensagem' => 'Senha fraca. Requisitos: ' . implode(', ', $validacao['errors'])
                ];
                header('Location: ' . App::getBasePath() . '/usuarios/criar');
                exit;
            }
        }

        try {
            require_once __DIR__ . '/../Helpers/PasswordHelper.php';
            
            $senhaTemp = $senha; // Se veio do formulário ou gerada
            $senhaHash = PasswordHelper::hash($senhaTemp);
            $token = bin2hex(random_bytes(32));

            $idUsuario = $this->usuario->create([
                'nome' => $nome,
                'email' => $email,
                'senha' => $senhaHash,
                'telefone' => $telefone,
                'data_nascimento' => $dataNascimento,
                'id_perfil' => $idPerfil,
                'token' => $token,
                'email_verificado' => true,
                'primeiro_acesso' => false
            ]);

            // Envia e-mail com a senha
            require_once __DIR__ . '/../Core/Mail.php';
            $mail = new Mail();
            $assunto = "Sua conta foi criada - " . App::getName();
            $mensagem = "
            <h2>Olá, {$nome}!</h2>
            <p>Sua conta foi criada no sistema " . App::getName() . ".</p>
            <p><strong>E-mail:</strong> {$email}</p>
            <p><strong>Senha temporária:</strong> {$senhaTemp}</p>
            <p>Recomendamos alterar sua senha no primeiro acesso.</p>
            <p><a href='" . App::getUrl() . "/login'>Acessar o sistema</a></p>
            ";
            $mail->send($email, $assunto, $mensagem, $nome);

            require_once __DIR__ . '/../Helpers/SecurityHelper.php';
            SecurityHelper::logAuditoria(
                'criar_usuario',
                $_SESSION['usuario']['id'],
                "Usuário criado: {$nome} ({$email}) com perfil ID: {$idPerfil}",
                'info'
            );

            $_SESSION['flash'] = [
                'tipo' => 'sucesso',
                'mensagem' => "Usuário criado com sucesso! As credenciais foram enviadas para o e-mail."
            ];

        } catch (Exception $e) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao criar usuário: ' . $e->getMessage()];
                header('Location: ' . App::getBasePath() . '/usuarios/criar');
                exit;
            }
        }

    /**
     * Formulário de edição (Admin e Master)
     */
    public function editar()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $role = (int) $_SESSION['usuario']['role'];
        
        if (!in_array($role, [1, 2])) {
            http_response_code(403);
            echo "<h1>403 - Acesso Negado</h1>";
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        
        if (!$id) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Usuário não identificado.'];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;
        }

        $usuario = $this->usuario->findById($id);
        
        if (!$usuario) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Usuário não encontrado.'];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;
        }

        $perfis = $this->usuario->getPerfis();
        $tituloPagina = 'Editar Usuário - ' . App::getName();
        $cssPagina = 'usuarios.css';
        require '../app/Views/usuarios/editar.php';
    }

    /**
     * Atualizar usuário (Admin e Master)
     */
    public function atualizar()
    {
        require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
        CsrfMiddleware::validate();

        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $role = (int) $_SESSION['usuario']['role'];
        
        if (!in_array($role, [1, 2])) {
            http_response_code(403);
            echo "<h1>403 - Acesso Negado</h1>";
            exit;
        }

        $id = (int) ($_POST['id_usuario'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $dataNascimento = $_POST['data_nascimento'] ?? '';
        $idPerfil = (int) ($_POST['id_perfil'] ?? 4);
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $senha = $_POST['senha'] ?? '';

        if (!$id) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Usuário não identificado.'];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;
        }

        if (empty($nome) || empty($email)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Nome e e-mail são obrigatórios.'];
            header('Location: ' . App::getBasePath() . '/usuarios/editar?id=' . $id);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'E-mail inválido.'];
            header('Location: ' . App::getBasePath() . '/usuarios/editar?id=' . $id);
            exit;
        }

        if ($this->usuario->emailExists($email, $id)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Este e-mail já está em uso.'];
            header('Location: ' . App::getBasePath() . '/usuarios/editar?id=' . $id);
            exit;
        }

        try {
            $dados = [
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefone,
                'data_nascimento' => $dataNascimento,
                'id_perfil' => $idPerfil,
                'ativo' => $ativo
            ];

            if (!empty($senha)) {
            require_once __DIR__ . '/../Helpers/PasswordHelper.php';
            $validacao = PasswordHelper::validate($senha);
            if (!$validacao['valid']) {
                $_SESSION['flash'] = [
                    'tipo' => 'erro', 
                    'mensagem' => 'Senha fraca. Requisitos: ' . implode(', ', $validacao['errors'])
                ];
                header('Location: ' . App::getBasePath() . '/usuarios/editar?id=' . $id);
                exit;
            }
            $dados['senha'] = PasswordHelper::hash($senha);
        }

            $this->usuario->update($id, $dados);

            require_once __DIR__ . '/../Helpers/SecurityHelper.php';
            SecurityHelper::logAuditoria(
                'atualizar_usuario',
                $_SESSION['usuario']['id'],
                "Usuário atualizado: ID {$id} - {$nome}",
                'info'
            );

            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Usuário atualizado com sucesso!'];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;

        } catch (Exception $e) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao atualizar: ' . $e->getMessage()];
            header('Location: ' . App::getBasePath() . '/usuarios/editar?id=' . $id);
            exit;
        }
    }

    /**
     * Excluir usuário (Apenas Master)
     */
    public function excluir()
    {
        require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
        CsrfMiddleware::validate();

        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $role = (int) $_SESSION['usuario']['role'];
        
        if ($role !== 1) {
            http_response_code(403);
            echo "<h1>403 - Acesso Negado</h1>";
            echo "<p>Apenas o Master pode excluir usuários.</p>";
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Usuário não identificado.'];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;
        }

        if ($id == $_SESSION['usuario']['id']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Você não pode excluir sua própria conta.'];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;
        }

        try {
            $usuario = $this->usuario->findById($id);
            $this->usuario->update($id, ['ativo' => 0]);

            require_once __DIR__ . '/../Helpers/SecurityHelper.php';
            SecurityHelper::logAuditoria(
                'excluir_usuario',
                $_SESSION['usuario']['id'],
                "Usuário desativado: ID {$id} - " . ($usuario['nome'] ?? ''),
                'warning'
            );

            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Usuário desativado com sucesso!'];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;

        } catch (Exception $e) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao desativar usuário: ' . $e->getMessage()];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;
        }
    }

    /**
     * Atribuir perfil - MASTER
     */
    public function atribuirPerfilMaster()
    {
        require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
        CsrfMiddleware::validate();

        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] != 1) {
            http_response_code(403);
            echo "<h1>403 - Acesso Negado</h1>";
            exit;
        }

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $idPerfil = (int) ($_POST['id_perfil'] ?? 0);

        if (!$idUsuario || !$idPerfil) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Dados inválidos.'];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;
        }

        if ($this->usuario->atualizarPerfil($idUsuario, $idPerfil)) {
            require_once __DIR__ . '/../Helpers/SecurityHelper.php';
            SecurityHelper::logAuditoria(
                'atribuir_perfil_master',
                $_SESSION['usuario']['id'],
                "Perfil atribuído: Usuário ID {$idUsuario} -> Perfil ID {$idPerfil}",
                'info'
            );

            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Perfil atribuído com sucesso!'];
        } else {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao atribuir perfil.'];
        }

        header('Location: ' . App::getBasePath() . '/usuarios');
        exit;
    }

    /**
     * Atribuir perfil - ADMIN (apenas Operador e Usuario)
     */
    public function atribuirPerfilAdmin()
    {
        require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
        CsrfMiddleware::validate();

        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $role = (int) $_SESSION['usuario']['role'];
        
        if ($role !== 2) {
            http_response_code(403);
            echo "<h1>403 - Acesso Negado</h1>";
            exit;
        }

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $idPerfil = (int) ($_POST['id_perfil'] ?? 0);

        if (!$idUsuario || !$idPerfil) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Dados inválidos.'];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;
        }

        if (!in_array($idPerfil, [3, 4])) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Você só pode atribuir perfis Operador ou Usuario.'];
            header('Location: ' . App::getBasePath() . '/usuarios');
            exit;
        }

        if ($this->usuario->atualizarPerfil($idUsuario, $idPerfil)) {
            require_once __DIR__ . '/../Helpers/SecurityHelper.php';
            SecurityHelper::logAuditoria(
                'atribuir_perfil_admin',
                $_SESSION['usuario']['id'],
                "Perfil atribuído por Admin: Usuário ID {$idUsuario} -> Perfil ID {$idPerfil}",
                'info'
            );

            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Perfil atribuído com sucesso!'];
        } else {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Erro ao atribuir perfil.'];
        }

        header('Location: ' . App::getBasePath() . '/usuarios');
        exit;
    }

    // ============================================================
    // REMOVIDO: Métodos relacionados a primeiro acesso pendente
    // - pendentes()
    // ============================================================
}