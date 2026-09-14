<?php
// app/Controllers/ConceitoController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/ConfiguracaoConceito.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class ConceitoController
{
    const VISIBILIDADES = [
        'turma'   => 'Toda a turma',
        'grupo'   => 'Apenas o grupo',
        'proprio' => 'Apenas o próprio',
    ];

    private Projeto              $projeto;
    private ConfiguracaoConceito $cfg;
    private TurmaUsuario         $tu;
    private Auditoria            $audit;

    public function __construct()
    {
        $this->projeto = new Projeto();
        $this->cfg     = new ConfiguracaoConceito();
        $this->tu      = new TurmaUsuario();
        $this->audit   = new Auditoria();
    }

    public function index(int $projetoId)
    {
        $this->exigirPodeEditar($projetoId);
        $projeto = $this->projeto->porId($projetoId);

        $this->render('conceitos/index', [
            'projeto'       => $projeto,
            'cfg'           => $this->cfg->garantir($projetoId),
            'visibilidades' => self::VISIBILIDADES,
        ]);
    }

    public function salvar(int $projetoId)
    {
        $this->exigirPodeEditar($projetoId);
        CsrfMiddleware::validate();

        $projeto = $this->projeto->porId($projetoId);
        if ($projeto['encerrado']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto encerrado.'];
            $this->redirect('/projetos/' . $projetoId);
        }

        $d = [
            'limite_i_max'          => (int) ($_POST['limite_i_max']  ?? 0),
            'limite_r_min'          => (int) ($_POST['limite_r_min']  ?? 0),
            'limite_r_max'          => (int) ($_POST['limite_r_max']  ?? 0),
            'limite_b_min'          => (int) ($_POST['limite_b_min']  ?? 0),
            'limite_b_max'          => (int) ($_POST['limite_b_max']  ?? 0),
            'limite_mb_min'         => (int) ($_POST['limite_mb_min'] ?? 0),
            'visibilidade_diretor'  => $_POST['visibilidade_diretor'] ?? 'grupo',
            'visibilidade_aluno'    => $_POST['visibilidade_aluno']   ?? 'proprio',
        ];

        $erros = [];
        if ($d['limite_i_max'] < 0 || $d['limite_i_max'] > 100)  $erros[] = 'I máximo entre 0 e 100.';
        if ($d['limite_r_min'] < 0 || $d['limite_r_min'] > 100)  $erros[] = 'R mínimo entre 0 e 100.';
        if ($d['limite_r_max'] < $d['limite_r_min'])              $erros[] = 'R máximo < mínimo.';
        if ($d['limite_b_min'] <= $d['limite_r_max'])             $erros[] = 'B mínimo deve ser maior que R máximo.';
        if ($d['limite_b_max'] < $d['limite_b_min'])              $erros[] = 'B máximo < mínimo.';
        if ($d['limite_mb_min'] <= $d['limite_b_max'])            $erros[] = 'MB mínimo deve ser maior que B máximo.';
        if ($d['limite_mb_min'] > 100)                            $erros[] = 'MB mínimo não pode passar de 100.';

        if (!array_key_exists($d['visibilidade_diretor'], self::VISIBILIDADES)) $erros[] = 'Visibilidade de diretor inválida.';
        if (!array_key_exists($d['visibilidade_aluno'],    self::VISIBILIDADES)) $erros[] = 'Visibilidade de aluno inválida.';

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/projetos/' . $projetoId . '/conceitos');
        }

        $antes = $this->cfg->garantir($projetoId);
        $this->cfg->atualizar($projetoId, $d);
        $this->audit->registrar('conceito_atualizado', 'configuracoes_conceito', $projetoId, $antes, $d);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Configuração de conceito salva.'];
        $this->redirect('/projetos/' . $projetoId . '/conceitos');
    }

    private function exigirPodeEditar(int $projetoId): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }

        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { http_response_code(404); exit('Projeto inexistente.'); }

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

        if (!$souMaster && !$souRep) {
            http_response_code(403); exit('Apenas master ou representante.');
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