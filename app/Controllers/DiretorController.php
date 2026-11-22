<?php
// app/Controllers/DiretorController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Grupo.php';
require_once __DIR__ . '/../Models/GrupoAluno.php';
require_once __DIR__ . '/../Models/GrupoDiretor.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Models/Notificacao.php';

class DiretorController
{
    private Projeto      $projeto;
    private Grupo        $grupo;
    private GrupoAluno   $membro;
    private GrupoDiretor $diretor;
    private TurmaUsuario $tu;
    private Auditoria    $audit;

    public function __construct()
    {
        $this->projeto = new Projeto();
        $this->grupo   = new Grupo();
        $this->membro  = new GrupoAluno();
        $this->diretor = new GrupoDiretor();
        $this->tu      = new TurmaUsuario();
        $this->audit   = new Auditoria();
    }

        // ============ LISTA DE GRUPOS QUE DIRIJO ============

    public function index()
    {
        $u = $_SESSION['usuario'];
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT g.id, g.nome AS grupo_nome,
                   p.id AS projeto_id, p.nome AS projeto_nome, p.encerrado,
                   t.id AS turma_id, t.nome AS turma_nome
            FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            INNER JOIN projetos p ON p.id = g.projeto_id
            INNER JOIN turmas t ON t.id = p.turma_id
            WHERE gd.usuario_id = ? AND gd.ativo = 1
            ORDER BY p.nome ASC, g.nome ASC
        ");
        $stmt->execute([(int) $u['id']]);
        $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('diretores/index', ['grupos' => $grupos]);
    }

    // ============ PROMOVER (view alternativa de nomeação) ============

    public function promover(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);

        $diretores = $this->diretor->listarAtivos($grupoId);
        $membros   = $this->membro->listarAtivos($grupoId);

        $this->render('diretores/promover', [
            'grupo'     => $grupo,
            'projeto'   => $projeto,
            'diretores' => $diretores,
            'membros'   => $membros,
        ]);
    }

    // ============ TROCAR (remover + nomear no mesmo fluxo) ============

    public function trocarForm(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);

        $diretores = $this->diretor->listarAtivos($grupoId);
        $membros   = $this->membro->listarAtivos($grupoId);

        $this->render('diretores/trocar', [
            'grupo'     => $grupo,
            'projeto'   => $projeto,
            'diretores' => $diretores,
            'membros'   => $membros,
        ]);
    }

    public function trocar(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        $saiId   = (int) ($_POST['sai_id'] ?? 0);
        $entraId = (int) ($_POST['entra_id'] ?? 0);

        $erros = [];
        if (!$saiId)   $erros[] = 'Selecione o diretor que sai.';
        if (!$entraId) $erros[] = 'Selecione o novo diretor.';
        if ($saiId === $entraId) $erros[] = 'O diretor que sai e o que entra devem ser diferentes.';

        if (!$this->diretor->ehDiretorAtivo($grupoId, $saiId)) {
            $erros[] = 'O diretor que sai não está ativo neste grupo.';
        }
        if (!$this->membro->estaAtivo($grupoId, $entraId)) {
            $erros[] = 'O novo diretor precisa ser membro ativo do grupo.';
        }
        if ($this->diretor->ehDiretorAtivo($grupoId, $entraId)) {
            $erros[] = 'O novo diretor já é diretor ativo deste grupo.';
        }

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/grupos/' . $grupoId . '/diretores/trocar');
        }

        $meuId = (int) $_SESSION['usuario']['id'];

        // 1. Remove o antigo
        $this->diretor->remover($grupoId, $saiId, $meuId);

        // 2. Nomeia o novo
        $this->diretor->nomear($grupoId, $entraId, $meuId);

        // 3. Reatribui atas pendentes
        $totalReatribuidas = $this->diretor->reatribuirAtasPendentes($grupoId, $saiId);

        $this->audit->registrar('diretor_trocado', 'grupo_diretores', $grupoId,
            ['sai_id' => $saiId, 'entra_id' => $entraId, 'por' => $meuId],
            ['atas_reatribuidas' => $totalReatribuidas]);

        $msg = 'Diretor trocado.';
        if ($totalReatribuidas > 0) {
            $msg .= " {$totalReatribuidas} ata(s) pendente(s) foram reatribuídas.";
        }
        $this->flash($msg, 'sucesso');
        $this->redirect('/grupos/' . $grupoId . '/diretores');
    }

    // ============ ALOCAR VÁRIOS MEMBROS DE UMA VEZ ============

    public function alocarForm(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);

        $candidatos = $this->membro->candidatosNaTurma($grupoId, (int) $projeto['turma_id']);
        $membros    = $this->membro->listarAtivos($grupoId);

        $this->render('diretores/alocar', [
            'grupo'      => $grupo,
            'projeto'    => $projeto,
            'candidatos' => $candidatos,
            'membros'    => $membros,
        ]);
    }

    public function alocar(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/grupos/' . $grupoId);
        }

        $usuarioIds = array_map('intval', $_POST['usuarios'] ?? []);

        if (empty($usuarioIds)) {
            $this->flash('Selecione ao menos um aluno.');
            $this->redirect('/grupos/' . $grupoId . '/diretores/alocar');
        }

        $adicionados = 0;
        foreach ($usuarioIds as $uid) {
            if (!$this->tu->estaAtivo((int) $projeto['turma_id'], $uid)) continue;
            if ($this->membro->estaAtivo($grupoId, $uid)) continue;

            $this->membro->adicionar($grupoId, $uid);
            $adicionados++;
        }

        $this->audit->registrar('grupo_alocacao_massa', 'grupo_alunos', $grupoId, null,
            ['total' => $adicionados, 'usuarios' => $usuarioIds]);

        $this->flash("{$adicionados} aluno(s) adicionado(s).", 'sucesso');
        $this->redirect('/grupos/' . $grupoId);
    }

    public function gerenciar(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);

        $this->render('diretores/gerenciar', [
            'grupo'      => $grupo,
            'projeto'    => $projeto,
            'diretores'  => $this->diretor->listarAtivos($grupoId),
            'membros'    => $this->membro->listarAtivos($grupoId),
            'podeEditar' => !$projeto['encerrado'],
        ]);
    }

    public function historico(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirAcessoProjeto($projeto);

        $this->render('diretores/historico', [
            'grupo'     => $grupo,
            'projeto'   => $projeto,
            'historico' => $this->diretor->listarHistorico($grupoId),
        ]);
    }

    public function nomear(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        if (!$usuarioId) {
            $this->flash('Selecione um membro do grupo.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        if (!$this->membro->estaAtivo($grupoId, $usuarioId)) {
            $this->flash('O usuário precisa ser membro ativo do grupo.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        if ($this->diretor->ehDiretorAtivo($grupoId, $usuarioId)) {
            $this->flash('Usuário já é diretor ativo deste grupo.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        $meuId = (int) $_SESSION['usuario']['id'];
        $this->diretor->nomear($grupoId, $usuarioId, $meuId);
        $this->audit->registrar('diretor_nomeado', 'grupo_diretores', $grupoId, null,
            ['usuario_id' => $usuarioId, 'por' => $meuId]);

        // Notificação para o novo diretor
        (new Notificacao())->criar(
            $usuarioId,
            'papel',
            'Você agora é diretor',
            "Você foi nomeado diretor do grupo '{$grupo['nome']}'.",
            '/grupos/' . $grupoId . '/diretores'
        );

        $this->flash('Diretor nomeado.', 'sucesso');
        $this->redirect('/grupos/' . $grupoId . '/diretores');
    }

        public function remover(int $grupoId)
    {
        $grupo = $this->grupo->porId($grupoId);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        if (!$usuarioId) {
            $this->flash('Usuário não informado.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        if (!$this->diretor->ehDiretorAtivo($grupoId, $usuarioId)) {
            $this->flash('Usuário não é diretor ativo deste grupo.');
            $this->redirect('/grupos/' . $grupoId . '/diretores');
        }

        $meuId = (int) $_SESSION['usuario']['id'];
        $this->diretor->remover($grupoId, $usuarioId, $meuId);

        $totalReatribuidas = $this->diretor->reatribuirAtasPendentes($grupoId, $usuarioId);

        $this->audit->registrar('diretor_removido', 'grupo_diretores', $grupoId,
            ['usuario_id' => $usuarioId, 'por' => $meuId],
            ['atas_reatribuidas' => $totalReatribuidas]);

        // Notificação para o diretor removido
        (new Notificacao())->criar(
            $usuarioId,
            'papel',
            'Você foi removido como diretor',
            "Você não é mais diretor do grupo '{$grupo['nome']}'.",
            '/grupos/' . $grupoId
        );

        // Se houver atas reatribuídas, notifica o novo diretor
        if ($totalReatribuidas > 0) {
            $novoDiretor = (new GrupoDiretor())->listarAtivos($grupoId);
            foreach ($novoDiretor as $d) {
                (new Notificacao())->criar(
                    (int) $d['usuario_id'],
                    'ata',
                    "Você herdou {$totalReatribuidas} ata(s) pendente(s)",
                    "Atas do grupo '{$grupo['nome']}' foram reatribuídas para você.",
                    '/grupos/' . $grupoId . '/diretores'
                );
            }
        }

        $msg = 'Diretor removido.';
        if ($totalReatribuidas > 0) {
            $msg .= " {$totalReatribuidas} ata(s) pendente(s) foram reatribuídas.";
        }
        $this->flash($msg, 'sucesso');
        $this->redirect('/grupos/' . $grupoId . '/diretores');
    }

    // ============ HELPERS ============

    private function exigirAcessoProjeto(array $projeto): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;
        if ($this->tu->estaAtivo((int) $projeto['turma_id'], (int) $u['id'])) return;
        http_response_code(403); exit('Sem acesso.');
    }

    private function exigirPodeEditarProjeto(array $projeto): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;
        if ($this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id'])) return;
        http_response_code(403); exit('Apenas representante da turma ou master.');
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

    private function flash(string $msg, string $tipo = 'erro'): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $msg];
    }
}