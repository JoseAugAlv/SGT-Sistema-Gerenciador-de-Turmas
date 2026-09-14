<?php
// app/Controllers/ConfiguracaoTurmaController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Curso.php';
require_once __DIR__ . '/../Models/Periodo.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class ConfiguracaoTurmaController
{
    private Curso     $curso;
    private Periodo   $periodo;
    private Auditoria $audit;

    public function __construct()
    {
        $this->curso   = new Curso();
        $this->periodo = new Periodo();
        $this->audit   = new Auditoria();
    }

    // ============ PÁGINA ÚNICA ============

    public function index()
    {
        $this->exigirMaster();
        $this->render('configuracoes/turmas', [
            'cursos'   => $this->curso->listarTodos(),
            'periodos' => $this->periodo->listarTodos(),
        ]);
    }

    // ============ CURSOS ============

    public function salvarCurso()
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $id    = (int) ($_POST['id'] ?? 0);
        $nome  = trim($_POST['nome']  ?? '');
        $sigla = strtoupper(trim($_POST['sigla'] ?? ''));

        $erros = [];
        if (strlen($nome) < 3)                          $erros[] = 'Nome muito curto.';
        if (!preg_match('/^[A-Z0-9]{2,10}$/', $sigla))  $erros[] = 'Sigla inválida (2-10 letras/números).';
        if ($this->curso->siglaJaExiste($sigla, $id ?: null)) $erros[] = 'Sigla já cadastrada.';

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/configuracoes/turmas');
        }

        if ($id) {
            $this->curso->atualizar($id, $nome, $sigla);
            $this->audit->registrar('curso_atualizado', 'cursos', $id, null, ['nome' => $nome, 'sigla' => $sigla]);
            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Curso atualizado.'];
        } else {
            $novoId = $this->curso->criar($nome, $sigla);
            $this->audit->registrar('curso_criado', 'cursos', $novoId, null, ['nome' => $nome, 'sigla' => $sigla]);
            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Curso criado.'];
        }

        $this->redirect('/configuracoes/turmas');
    }

    public function toggleCurso(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();
        $this->curso->alternarAtivo($id);
        $this->audit->registrar('curso_toggle_ativo', 'cursos', $id);
        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Curso atualizado.'];
        $this->redirect('/configuracoes/turmas');
    }

    // ============ PERÍODOS ============

    public function salvarPeriodo()
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $id    = (int) ($_POST['id'] ?? 0);
        $nome  = trim($_POST['nome']  ?? '');
        $sigla = strtoupper(trim($_POST['sigla'] ?? ''));

        $erros = [];
        if (strlen($nome) < 2)                          $erros[] = 'Nome muito curto.';
        if (!preg_match('/^[A-Z0-9]{2,10}$/', $sigla))  $erros[] = 'Sigla inválida (2-10 letras/números).';
        if ($this->periodo->siglaJaExiste($sigla, $id ?: null)) $erros[] = 'Sigla já cadastrada.';

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/configuracoes/turmas');
        }

        if ($id) {
            $this->periodo->atualizar($id, $nome, $sigla);
            $this->audit->registrar('periodo_atualizado', 'periodos', $id, null, ['nome' => $nome, 'sigla' => $sigla]);
            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Período atualizado.'];
        } else {
            $novoId = $this->periodo->criar($nome, $sigla);
            $this->audit->registrar('periodo_criado', 'periodos', $novoId, null, ['nome' => $nome, 'sigla' => $sigla]);
            $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Período criado.'];
        }

        $this->redirect('/configuracoes/turmas');
    }

    public function togglePeriodo(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();
        $this->periodo->alternarAtivo($id);
        $this->audit->registrar('periodo_toggle_ativo', 'periodos', $id);
        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Período atualizado.'];
        $this->redirect('/configuracoes/turmas');
    }

    // ============ HELPERS ============

    private function exigirMaster(): void
    {
        if (empty($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'master') {
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
}