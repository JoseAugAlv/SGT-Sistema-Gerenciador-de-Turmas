<?php
// app/Controllers/TurmaController.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Models/Curso.php';
require_once __DIR__ . '/../Models/Periodo.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class TurmaController
{
    const ANOS_MODULO = [
        '1MOD' => '1º Módulo',
        '2MOD' => '2º Módulo',
        '3MOD' => '3º Módulo',
    ];

    private Turma        $turma;
    private TurmaUsuario $tu;
    private Auditoria    $audit;
    private Curso        $curso;
    private Periodo      $periodo;

    public function __construct()
    {
        $this->turma   = new Turma();
        $this->tu      = new TurmaUsuario();
        $this->audit   = new Auditoria();
        $this->curso   = new Curso();
        $this->periodo = new Periodo();
    }

    // ============ LISTAGEM ============

    public function index()
    {
        $u = $_SESSION['usuario'];
        $turmas = $u['tipo'] === 'master'
            ? $this->turma->listarTodas()
            : $this->turma->listarDoUsuario((int) $u['id']);

        $this->render('turmas/index', [
            'turmas'   => $turmas,
            'isMaster' => $u['tipo'] === 'master',
        ]);
    }

    // ============ CRIAR ============

    public function criarForm()
    {
        $this->exigirMaster();

        $this->render('turmas/criar', [
            'cursos'     => $this->curso->listarAtivos(),
            'periodos'   => $this->periodo->listarAtivos(),
            'anosModulo' => self::ANOS_MODULO,
        ]);
    }

    public function salvar()
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $codigoEscola = trim($_POST['codigo_escola'] ?? '');
        $anoModulo    = trim($_POST['ano_modulo']    ?? '');
        $cursoId      = (int) ($_POST['curso_id']    ?? 0);
        $periodoId    = (int) ($_POST['periodo_id']  ?? 0);
        $codigoAcesso = strtoupper(trim($_POST['codigo_acesso'] ?? ''));
        $corP         = trim($_POST['cor_primaria']   ?? '#7165e8');
        $corS         = trim($_POST['cor_secundaria'] ?? '#39b889');

        $erros = [];
        if (!ctype_digit($codigoEscola))                       $erros[] = 'Código da escola deve ser numérico.';
        if (!array_key_exists($anoModulo, self::ANOS_MODULO))  $erros[] = 'Ano/Módulo inválido.';

        $curso = $cursoId ? $this->curso->porId($cursoId) : null;
        if (!$curso || !$curso['ativo'])                       $erros[] = 'Curso inválido.';

        $periodo = $periodoId ? $this->periodo->porId($periodoId) : null;
        if (!$periodo || !$periodo['ativo'])                   $erros[] = 'Período inválido.';

        if (strlen($codigoAcesso) < 4)                         $erros[] = 'Código de acesso muito curto.';
        if ($this->turma->codigoJaExiste($codigoAcesso))       $erros[] = 'Código já em uso.';

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/turmas/criar');
        }

        $nome = Turma::montarNome($codigoEscola, $anoModulo, $curso['sigla'], $periodo['sigla']);

        $id = $this->turma->criar([
            'nome'           => $nome,
            'codigo_acesso'  => $codigoAcesso,
            'codigo_interno' => $nome,
            'cor_primaria'   => $corP,
            'cor_secundaria' => $corS,
        ]);

        $this->audit->registrar('turma_criada', 'turmas', $id, null, ['nome' => $nome, 'codigo' => $codigoAcesso]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => "Turma '{$nome}' criada. Código: {$codigoAcesso}"];
        $this->redirect('/turmas/' . $id);
    }

    // ============ DETALHE ============

    public function detalhe(int $id)
    {
        $turma = $this->turma->porId($id);
        if (!$turma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Turma não encontrada.'];
            $this->redirect('/turmas');
        }

        $u            = $_SESSION['usuario'];
        $souMaster    = $u['tipo'] === 'master';
        $souRep       = $this->tu->ehRepresentante($id, (int) $u['id']);
        $estouNaTurma = $this->tu->estaAtivo($id, (int) $u['id']);

        if (!$souMaster && !$estouNaTurma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Você não tem acesso a esta turma.'];
            $this->redirect('/turmas');
        }

        if ($turma['bloqueada'] && !$souMaster) {
            $this->render('turmas/bloqueada', ['turma' => $turma]);
            return;
        }

        $this->render('turmas/detalhe', [
            'turma'          => $turma,
            'representantes' => $this->tu->listarRepresentantes($id),
            'alunos'         => $this->tu->listarAlunos($id),
            'isMaster'       => $souMaster,
            'isRep'          => $souRep,
            'projetos'       => (new Projeto())->listarPorTurma($id),
        ]);
    }

    // ============ EDITAR ============

    public function editarForm(int $id)
    {
        $this->exigirMaster();

        $turma = $this->turma->porId($id);
        if (!$turma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Turma não encontrada.'];
            $this->redirect('/turmas');
        }

        $partes = explode('-', $turma['nome']);
        $codigoEscolaAtual = $partes[0] ?? '';
        $anoModuloAtual    = $partes[1] ?? '';
        $siglaCursoAtual   = $partes[2] ?? '';
        $siglaPeriodoAtual = $partes[3] ?? '';

        $cursoAtualId = null;
        foreach ($this->curso->listarTodos() as $c) {
            if ($c['sigla'] === $siglaCursoAtual) { $cursoAtualId = (int) $c['id']; break; }
        }
        $periodoAtualId = null;
        foreach ($this->periodo->listarTodos() as $p) {
            if ($p['sigla'] === $siglaPeriodoAtual) { $periodoAtualId = (int) $p['id']; break; }
        }

        $this->render('turmas/editar', [
            'turma'             => $turma,
            'cursos'            => $this->curso->listarAtivos(),
            'periodos'          => $this->periodo->listarAtivos(),
            'anosModulo'        => self::ANOS_MODULO,
            'codigoEscolaAtual' => $codigoEscolaAtual,
            'anoModuloAtual'    => $anoModuloAtual,
            'cursoAtualId'      => $cursoAtualId,
            'periodoAtualId'    => $periodoAtualId,
        ]);
    }

    public function atualizar(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $antes = $this->turma->porId($id);
        if (!$antes) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Turma não encontrada.'];
            $this->redirect('/turmas');
        }

        $codigoEscola = trim($_POST['codigo_escola'] ?? '');
        $anoModulo    = trim($_POST['ano_modulo']    ?? '');
        $cursoId      = (int) ($_POST['curso_id']    ?? 0);
        $periodoId    = (int) ($_POST['periodo_id']  ?? 0);
        $codigoAcesso = strtoupper(trim($_POST['codigo_acesso'] ?? ''));
        $corP         = trim($_POST['cor_primaria']   ?? '#7165e8');
        $corS         = trim($_POST['cor_secundaria'] ?? '#39b889');

        $erros = [];
        if (!ctype_digit($codigoEscola))                       $erros[] = 'Código da escola deve ser numérico.';
        if (!array_key_exists($anoModulo, self::ANOS_MODULO))  $erros[] = 'Ano/Módulo inválido.';

        $curso = $cursoId ? $this->curso->porId($cursoId) : null;
        if (!$curso || !$curso['ativo'])                       $erros[] = 'Curso inválido.';

        $periodo = $periodoId ? $this->periodo->porId($periodoId) : null;
        if (!$periodo || !$periodo['ativo'])                   $erros[] = 'Período inválido.';

        if (strlen($codigoAcesso) < 4)                         $erros[] = 'Código de acesso muito curto.';
        if ($this->turma->codigoJaExiste($codigoAcesso, $id))  $erros[] = 'Código já em uso.';

        if ($erros) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => implode(' | ', $erros)];
            $this->redirect('/turmas/' . $id . '/editar');
        }

        $nome = Turma::montarNome($codigoEscola, $anoModulo, $curso['sigla'], $periodo['sigla']);

        Database::getConnection()->prepare("
            UPDATE turmas SET nome = ?, codigo_acesso = ?, codigo_interno = ?, cor_primaria = ?, cor_secundaria = ?
            WHERE id = ?
        ")->execute([$nome, $codigoAcesso, $nome, $corP, $corS, $id]);

        $this->audit->registrar('turma_atualizada', 'turmas', $id, $antes,
            ['nome' => $nome, 'codigo_acesso' => $codigoAcesso]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Turma atualizada.'];
        $this->redirect('/turmas/' . $id);
    }

    // ============ EXCLUIR ============

    public function excluirForm(int $id)
    {
        $this->exigirMaster();

        $turma = $this->turma->porId($id);
        if (!$turma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Turma não encontrada.'];
            $this->redirect('/turmas');
        }

        $totalAlunos   = $this->tu->contarAtivos($id);
        $totalProjetos = $this->contarProjetos($id);

        $this->render('turmas/excluir', [
            'turma'         => $turma,
            'totalAlunos'   => $totalAlunos,
            'totalProjetos' => $totalProjetos,
            'podeExcluir'   => ($totalAlunos === 0 && $totalProjetos === 0),
        ]);
    }

    public function excluir(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $turma = $this->turma->porId($id);
        if (!$turma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Turma não encontrada.'];
            $this->redirect('/turmas');
        }

        $totalAlunos   = $this->tu->contarAtivos($id);
        $totalProjetos = $this->contarProjetos($id);

        if ($totalAlunos > 0 || $totalProjetos > 0) {
            $_SESSION['flash'] = [
                'tipo' => 'erro',
                'mensagem' => "Não é possível excluir: turma tem {$totalAlunos} aluno(s) e {$totalProjetos} projeto(s)."
            ];
            $this->redirect('/turmas/' . $id);
        }

        $this->audit->registrar('turma_excluida', 'turmas', $id, $turma, null);
        $this->turma->delete($id, 'id');

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => "Turma '{$turma['nome']}' excluída."];
        $this->redirect('/turmas');
    }

    // ============ BLOQUEIO ============

    public function bloquear(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();
        $motivo = trim($_POST['motivo'] ?? '') ?: 'Bloqueio administrativo';

        $antes = $this->turma->porId($id);
        $this->turma->bloquear($id, (int) $_SESSION['usuario']['id'], $motivo);
        $this->audit->registrar('turma_bloqueada', 'turmas', $id, $antes, ['motivo' => $motivo]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Turma bloqueada.'];
        $this->redirect('/turmas/' . $id);
    }

    public function desbloquear(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();
        $antes = $this->turma->porId($id);
        $this->turma->desbloquear($id);
        $this->audit->registrar('turma_desbloqueada', 'turmas', $id, $antes, null);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Turma desbloqueada.'];
        $this->redirect('/turmas/' . $id);
    }

    // ============ CÓDIGO DE ACESSO ============

    public function regenerarCodigo(int $id)
    {
        CsrfMiddleware::validate();
        $u = $_SESSION['usuario'];
        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante($id, (int) $u['id']);

        if (!$souMaster && !$souRep) { http_response_code(403); exit('Sem permissão.'); }

        $antes = $this->turma->porId($id);
        $novo  = $this->turma->regenerarCodigo($id);
        $this->audit->registrar('turma_codigo_regenerado', 'turmas', $id,
            ['codigo' => $antes['codigo_acesso']], ['codigo' => $novo]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => "Novo código: {$novo}"];
        $this->redirect('/turmas/' . $id);
    }

    // ============ IDENTIDADE (cor e logo da turma) ============

    private function exigirRepOuMaster(int $id): array
    {
        $turma = $this->turma->porId($id);
        if (!$turma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Turma não encontrada.'];
            $this->redirect('/turmas');
        }
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !$this->tu->ehRepresentante($id, (int) $u['id'])) {
            http_response_code(403);
            exit('Sem permissão.');
        }
        return $turma;
    }

    public function identidadeForm(int $id)
    {
        $turma = $this->exigirRepOuMaster($id);
        $this->render('turmas/identidade', ['turma' => $turma]);
    }

    public function salvarIdentidade(int $id)
    {
        CsrfMiddleware::validate();
        $turma = $this->exigirRepOuMaster($id);

        $personalizada = !empty($_POST['cor_personalizada']) ? 1 : 0;
        $cor = strtolower(trim($_POST['cor_primaria'] ?? ''));
        if (!preg_match('/^#[0-9a-f]{6}$/', $cor)) {
            $cor = $turma['cor_primaria'];
        }

        $logo = $turma['logo'];
        $logoAntigo = null;

        if (!empty($_POST['remover_logo'])) {
            $logoAntigo = $logo;
            $logo = null;
        }

        if (!empty($_FILES['logo']['name'])) {
            require_once __DIR__ . '/../Helpers/UploadHelper.php';
            $r = UploadHelper::upload($_FILES['logo'], 'uploads/turmas', ['png', 'jpg', 'jpeg', 'webp'], 1048576);
            if (!$r['success']) {
                $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Logo: ' . $r['message'] . ' Use PNG, JPG ou WEBP de até 1 MB.'];
                $this->redirect('/turmas/' . $id . '/identidade');
            }
            $logoAntigo = $turma['logo'];
            $logo = $r['arquivo'];
        }

        $this->turma->atualizarIdentidade($id, $cor, $personalizada, $logo);

        // Apaga o arquivo antigo só depois de salvar
        if ($logoAntigo && $logoAntigo !== $logo && str_starts_with($logoAntigo, 'uploads/turmas/')) {
            $caminho = __DIR__ . '/../../public/' . $logoAntigo;
            if (is_file($caminho)) @unlink($caminho);
        }

        $this->audit->registrar('turma_identidade_alterada', 'turmas', $id,
            ['cor' => $turma['cor_primaria'], 'personalizada' => (int) $turma['cor_personalizada'], 'logo' => $turma['logo']],
            ['cor' => $cor, 'personalizada' => $personalizada, 'logo' => $logo]);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Identidade da turma salva.'];
        $this->redirect('/turmas/' . $id . '/identidade');
    }

    // ============ ENTRAR ============

    public function entrarForm()
    {
        $this->render('turmas/entrar');
    }

    public function entrar()
    {
        CsrfMiddleware::validate();
        $codigo = strtoupper(trim($_POST['codigo_acesso'] ?? ''));
        $u = $_SESSION['usuario'];

        if ($codigo === '') {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Informe o código.'];
            $this->redirect('/turmas/entrar');
        }

        $turma = $this->turma->porCodigo($codigo);
        if (!$turma) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Código de turma inválido.'];
            $this->redirect('/turmas/entrar');
        }
        if ($turma['bloqueada']) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Esta turma está bloqueada.'];
            $this->redirect('/turmas/entrar');
        }
        if ($this->tu->estaAtivo((int) $turma['id'], (int) $u['id'])) {
            $_SESSION['flash'] = ['tipo' => 'aviso', 'mensagem' => 'Você já está nesta turma.'];
            $this->redirect('/turmas/' . $turma['id']);
        }

        $this->tu->adicionar((int) $turma['id'], (int) $u['id'], 'aluno');
        $this->audit->registrar('aluno_entrou_turma', 'turma_usuarios', (int) $turma['id'],
            null, ['usuario_id' => $u['id'], 'papel' => 'aluno']);

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Você entrou na turma!'];
        $this->redirect('/turmas/' . $turma['id']);
    }

    // ============ REPRESENTANTES ============

    public function representantesForm(int $id)
    {
        $this->exigirMaster();
        $turma = $this->turma->porId($id);
        if (!$turma) $this->redirect('/turmas');

        $this->render('turmas/representantes', [
            'turma'          => $turma,
            'representantes' => $this->tu->listarRepresentantes($id),
            'alunos'         => $this->tu->listarAlunos($id),
        ]);
    }

    public function nomearRepresentante(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        if (!$usuarioId) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Selecione um aluno.'];
            $this->redirect('/turmas/' . $id . '/representantes');
        }

        if ($this->tu->contarRepresentantes($id) >= 2) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Turma já possui 2 representantes.'];
            $this->redirect('/turmas/' . $id . '/representantes');
        }

        if (!$this->tu->estaAtivo($id, $usuarioId)) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Usuário não está ativo nesta turma.'];
            $this->redirect('/turmas/' . $id . '/representantes');
        }

        $this->tu->definirPapel($id, $usuarioId, 'representante');
        $this->audit->registrar('representante_nomeado', 'turma_usuarios', $id, null,
            ['usuario_id' => $usuarioId, 'papel' => 'representante']);

        $turma = $this->turma->porId($id);
        (new Notificacao())->criar(
            $usuarioId,
            'papel',
            'Você agora é representante',
            "Você foi nomeado representante da turma '{$turma['nome']}'.",
            '/turmas/' . $id
        );

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Representante nomeado.'];
        $this->redirect('/turmas/' . $id . '/representantes');
    }

    public function removerRepresentante(int $id)
    {
        $this->exigirMaster();
        CsrfMiddleware::validate();

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        if (!$usuarioId) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Usuário não informado.'];
            $this->redirect('/turmas/' . $id . '/representantes');
        }

        if ($this->tu->contarRepresentantes($id) <= 1) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Não é possível remover o último representante.'];
            $this->redirect('/turmas/' . $id . '/representantes');
        }

        $this->tu->definirPapel($id, $usuarioId, 'aluno');
        $this->audit->registrar('representante_removido', 'turma_usuarios', $id,
            ['usuario_id' => $usuarioId, 'papel' => 'representante'],
            ['usuario_id' => $usuarioId, 'papel' => 'aluno']);

        $turma = $this->turma->porId($id);
        (new Notificacao())->criar(
            $usuarioId,
            'papel',
            'Você não é mais representante',
            "Seu papel de representante na turma '{$turma['nome']}' foi removido.",
            '/turmas/' . $id
        );

        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => 'Representante removido.'];
        $this->redirect('/turmas/' . $id . '/representantes');
    }

    // ============ HELPERS ============

    private function contarProjetos(int $turmaId): int
    {
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM projetos WHERE turma_id = ?");
        $stmt->execute([$turmaId]);
        return (int) $stmt->fetchColumn();
    }

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