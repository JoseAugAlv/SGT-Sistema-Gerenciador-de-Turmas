<?php
// app/Controllers/ProjetoController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/ConfiguracaoConceito.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class ProjetoController
{
    const MODOS = [
        'etapa'      => 'Por etapa (critérios podem ser vinculados a etapas)',
        'cronograma' => 'Por cronograma (etapas apenas para controle)',
    ];

    private Projeto              $projeto;
    private Turma                $turma;
    private TurmaUsuario         $tu;
    private ConfiguracaoConceito $cfg;
    private Auditoria            $audit;

    public function __construct()
    {
        $this->projeto = new Projeto();
        $this->turma   = new Turma();
        $this->tu      = new TurmaUsuario();
        $this->cfg     = new ConfiguracaoConceito();
        $this->audit   = new Auditoria();
    }

    // ============ LISTAGEM (por turma) ============

    public function index()
    {
        $turmaId = (int) ($_GET['turma_id'] ?? 0);
        $u = $_SESSION['usuario'];

        if (!$turmaId) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Informe a turma.'];
            $this->redirect('/turmas');
        }

        $turma = $this->turma->porId($turmaId);
        if (!$turma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Turma não encontrada.'];
            $this->redirect('/turmas');
        }

        $souMaster    = $u['tipo'] === 'master';
        $estouNaTurma = $this->tu->estaAtivo($turmaId, (int) $u['id']);
        $souRep       = $this->tu->ehRepresentante($turmaId, (int) $u['id']);

        if (!$souMaster && !$estouNaTurma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Sem acesso a esta turma.'];
            $this->redirect('/turmas');
        }

        if ($turma['bloqueada'] && !$souMaster) {
            $this->render('turmas/bloqueada', ['turma' => $turma]);
            return;
        }

        $this->render('projetos/index', [
            'turma'    => $turma,
            'projetos' => $this->projeto->listarPorTurma($turmaId),
            'isMaster' => $souMaster,
            'isRep'    => $souRep,
        ]);
    }

    // ============ CRIAR (MASTER) ============

    public function criarForm()
    {
        $this->exigirMaster();
        $turmaId = (int) ($_GET['turma_id'] ?? 0);

        $turma = $this->turma->porId($turmaId);
        if (!$turma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Turma inválida.'];
            $this->redirect('/turmas');
        }

        $this->render('projetos/criar', [
            'turma' => $turma,
            'modos' => self::MODOS,
        ]);
    }

    public function salvar()
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $turmaId = (int) ($_POST['turma_id'] ?? 0);
        $nome    = trim($_POST['nome'] ?? '');
        $desc    = trim($_POST['descricao'] ?? '') ?: null;
        $prazo   = trim($_POST['prazo'] ?? '') ?: null;
        $modo    = $_POST['modo_avaliacao'] ?? 'cronograma';

        $turma = $this->turma->porId($turmaId);
        $erros = [];
        if (!$turma)                                    $erros[] = 'Turma inválida.';
        if (strlen($nome) < 3)                          $erros[] = 'Nome muito curto.';
        if (!array_key_exists($modo, self::MODOS))      $erros[] = 'Modo de avaliação inválido.';
        if ($prazo) {
            $dt = DateTime::createFromFormat('Y-m-d', $prazo);
            if (!$dt) $erros[] = 'Prazo inválido.';
        }

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/projetos/criar?turma_id=' . $turmaId);
        }

        $id = $this->projeto->criar([
            'turma_id'       => $turmaId,
            'nome'           => $nome,
            'descricao'      => $desc,
            'prazo'          => $prazo,
            'modo_avaliacao' => $modo,
        ]);

        // Garante configuração de conceito com defaults
        $this->cfg->garantir($id);

        $this->audit->registrar('projeto_criado', 'projetos', $id, null, [
            'nome' => $nome, 'turma_id' => $turmaId, 'modo' => $modo,
        ]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Projeto criado.'];
        $this->redirect('/projetos/' . $id);
    }

    // ============ DETALHE ============

    public function detalhe(int $id)
    {
        $projeto = $this->projeto->porId($id);
        if (!$projeto) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto não encontrado.'];
            $this->redirect('/turmas');
        }

        $u            = $_SESSION['usuario'];
        $souMaster    = $u['tipo'] === 'master';
        $estouNaTurma = $this->tu->estaAtivo((int) $projeto['turma_id'], (int) $u['id']);
        $souRep       = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

        if (!$souMaster && !$estouNaTurma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Sem acesso a este projeto.'];
            $this->redirect('/turmas');
        }

        $turma = $this->turma->porId((int) $projeto['turma_id']);
        if ($turma['bloqueada'] && !$souMaster) {
            $this->render('turmas/bloqueada', ['turma' => $turma]);
            return;
        }

        require_once __DIR__ . '/../Models/Etapa.php';
        $etapa = new Etapa();

        $this->render('projetos/detalhe', [
            'projeto'  => $projeto,
            'turma'    => $turma,
            'etapas'   => $etapa->listarPorProjeto($id),
            'isMaster' => $souMaster,
            'isRep'    => $souRep,
            'podeEditar' => ($souMaster || $souRep),
        ]);
    }

    // ============ EDITAR (MASTER) ============

    public function editarForm(int $id)
    {
        $this->exigirMaster();
        $projeto = $this->projeto->porId($id);
        if (!$projeto) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto não encontrado.'];
            $this->redirect('/turmas');
        }

        $this->render('projetos/editar', [
            'projeto' => $projeto,
            'modos'   => self::MODOS,
        ]);
    }

    public function atualizar(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $antes = $this->projeto->porId($id);
        if (!$antes) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto não encontrado.'];
            $this->redirect('/turmas');
        }

        if ($antes['encerrado']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto encerrado não pode ser editado.'];
            $this->redirect('/projetos/' . $id);
        }

        $nome  = trim($_POST['nome'] ?? '');
        $desc  = trim($_POST['descricao'] ?? '') ?: null;
        $prazo = trim($_POST['prazo'] ?? '') ?: null;
        $modo  = $_POST['modo_avaliacao'] ?? 'cronograma';

        $erros = [];
        if (strlen($nome) < 3)                      $erros[] = 'Nome muito curto.';
        if (!array_key_exists($modo, self::MODOS))  $erros[] = 'Modo inválido.';
        if ($prazo) {
            $dt = DateTime::createFromFormat('Y-m-d', $prazo);
            if (!$dt) $erros[] = 'Prazo inválido.';
        }

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/projetos/' . $id . '/editar');
        }

        $this->projeto->atualizar($id, [
            'nome' => $nome, 'descricao' => $desc, 'prazo' => $prazo, 'modo_avaliacao' => $modo,
        ]);

        $this->audit->registrar('projeto_atualizado', 'projetos', $id, $antes,
            ['nome' => $nome, 'modo' => $modo]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Projeto atualizado.'];
        $this->redirect('/projetos/' . $id);
    }

    // ============ ENCERRAR (MASTER OU REP) ============

    public function encerrarForm(int $id)
    {
        $projeto = $this->projeto->porId($id);
        if (!$projeto) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto não encontrado.'];
            $this->redirect('/turmas');
        }

        $u         = $_SESSION['usuario'];
        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

        if (!$souMaster && !$souRep) {
            http_response_code(403);
            exit('Apenas master ou representante pode encerrar.');
        }

        if ($projeto['encerrado']) {
            $_SESSION['flash'] = ['tipo' => 'aviso', 'mensagem' => 'Este projeto já está encerrado.'];
            $this->redirect('/projetos/' . $id);
        }

        $this->render('projetos/encerrar', ['projeto' => $projeto]);
    }

    public function encerrar(int $id)
    {
        CsrfMiddleware::validate();

        $projeto = $this->projeto->porId($id);
        if (!$projeto) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto não encontrado.'];
            $this->redirect('/turmas');
        }

        $u         = $_SESSION['usuario'];
        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

        if (!$souMaster && !$souRep) {
            http_response_code(403); exit('Sem permissão.');
        }

        if ($projeto['encerrado']) {
            $this->redirect('/projetos/' . $id);
        }

        if (empty($_POST['confirmo'])) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Confirmação obrigatória.'];
            $this->redirect('/projetos/' . $id . '/encerrar');
        }

        $this->projeto->encerrar($id, (int) $u['id']);

        // TODO FASE 7: SnapshotService::congelarBoletim($id)

        $this->audit->registrar('projeto_encerrado', 'projetos', $id,
            ['encerrado' => 0], ['encerrado' => 1, 'por' => $u['id']]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Projeto encerrado. Boletim congelado (F7).'];
        $this->redirect('/projetos/' . $id);
    }

    public function grupos(int $id)      { $this->placeholder($id, 'Grupos'); }
    public function criterios(int $id)   { $this->placeholder($id, 'Critérios'); }
    public function avaliacoes(int $id)  { $this->placeholder($id, 'Avaliações'); }
    public function relatorios(int $id)  { $this->placeholder($id, 'Relatórios'); }

    private function placeholder(int $id, string $tituloSecao): void
    {
        $projeto = $this->projeto->porId($id);
        if (!$projeto) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Projeto não encontrado.'];
            $this->redirect('/turmas');
        }

        $u            = $_SESSION['usuario'];
        $souMaster    = $u['tipo'] === 'master';
        $estouNaTurma = $this->tu->estaAtivo((int) $projeto['turma_id'], (int) $u['id']);

        if (!$souMaster && !$estouNaTurma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Sem acesso a este projeto.'];
            $this->redirect('/turmas');
        }

        $this->render('projetos/placeholder', [
            'projeto'      => $projeto,
            'tituloSecao'  => $tituloSecao,
        ]);
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