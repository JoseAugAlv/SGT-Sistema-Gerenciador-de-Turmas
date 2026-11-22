<?php
// app/Controllers/TutorialController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';

class TutorialController
{
    public function index()
    {
        $tituloPagina = 'Tutorial — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();

        $usuario   = $_SESSION['usuario'] ?? null;
        $isMaster  = $usuario && $usuario['tipo'] === 'master';
        $isRep     = false;
        $isDiretor = false;

        if ($usuario && !$isMaster) {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare("
                SELECT 1 FROM turma_usuarios
                WHERE usuario_id = ? AND papel = 'representante' AND ativo = 1
                LIMIT 1
            ");
            $stmt->execute([(int) $usuario['id']]);
            $isRep = (bool) $stmt->fetchColumn();

            $stmt = $pdo->prepare("
                SELECT 1 FROM grupo_diretores
                WHERE usuario_id = ? AND ativo = 1
                LIMIT 1
            ");
            $stmt->execute([(int) $usuario['id']]);
            $isDiretor = (bool) $stmt->fetchColumn();
        }

        require __DIR__ . '/../Views/tutorial/index.php';
    }

    public function aluno()
    {
        $this->exigirLogin();
        $tituloPagina = 'Tutorial do Aluno — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();
        require __DIR__ . '/../Views/tutorial/aluno.php';
    }

    public function diretor()
    {
        $this->exigirLogin();
        $tituloPagina = 'Tutorial do Diretor — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();
        require __DIR__ . '/../Views/tutorial/diretor.php';
    }

    public function representante()
    {
        $this->exigirLogin();
        $tituloPagina = 'Tutorial do Representante — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();
        require __DIR__ . '/../Views/tutorial/representante.php';
    }

    // ============ GALERIA DE EXEMPLOS ============

    public function exemplos()
    {
        $tituloPagina = 'Exemplos de telas — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();
        require __DIR__ . '/../Views/tutorial/exemplos.php';
    }

    /**
     * Página de demo renderizada DENTRO do iframe.
     * Sem header/nav/footer do sistema — layout próprio.
     */
    public function demo(string $slug)
    {
        $permitidos = [
            'dashboard-aluno',
            'dashboard-diretor',
            'dashboard-representante',
            'turma-detalhe',
            'projeto-detalhe',
            'grupo-detalhe',
            'criterios',
            'avaliacoes-rep',
            'avaliacoes-diretor',
            'avaliacoes-pares',
            'avaliacoes-auto',
            'minhas-notas',
            'atas-rep',
            'atas-diretor',
            'materiais',
            'relatorio-geral',
            'notificacoes',
            'conceitos',
        ];

        if (!in_array($slug, $permitidos, true)) {
            http_response_code(404);
            exit('Exemplo não disponível.');
        }

        $arquivo = __DIR__ . '/../Views/tutorial/demos/' . $slug . '.php';
        if (!file_exists($arquivo)) {
            http_response_code(404);
            exit('Demo não encontrada.');
        }

        // Layout base da demo
        $basePath = App::getBasePath();
        $appName  = App::getName();
        $ativo    = $slug;
        $conteudo = $arquivo;

        require __DIR__ . '/../Views/tutorial/demos/_layout.php';
    }

    public function master()
    {
        $this->exigirLogin();
        if (empty($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'master') {
            http_response_code(403);
            exit('Acesso restrito ao master.');
        }
        $tituloPagina = 'Tutorial do Master — ' . App::getName();
        $basePath     = App::getBasePath();
        $appName      = App::getName();
        require __DIR__ . '/../Views/tutorial/master.php';
    }

    private function exigirLogin(): void
    {
        if (empty($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }
    }
}