<?php
// app/Controllers/GrupoController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Grupo.php';
require_once __DIR__ . '/../Models/GrupoAluno.php';
require_once __DIR__ . '/../Models/GrupoDiretor.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class GrupoController
{
    const MODOS_GRUPO = [
        'individual' => 'Individual (cada aluno recebe nota própria)',
        'coletiva'   => 'Coletiva (uma nota para o grupo todo)',
    ];

    const MODOS_POR = [
        'diretor'       => 'Diretor',
        'representante' => 'Representante',
        'pares'         => 'Pares',
        'autoavaliacao' => 'Autoavaliação',
        'misto'         => 'Misto (diretor + representante)',
    ];

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

    public function index(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirAcessoProjeto($projeto);

        $this->render('grupos/index', [
            'projeto'    => $projeto,
            'grupos'     => $this->grupo->listarPorProjeto($projetoId),
            'podeEditar' => $this->podeEditarProjeto($projeto),
        ]);
    }

    public function criarForm(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirPodeEditarProjeto($projeto);
        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projetoId . '/grupos');
        }

        $this->render('grupos/criar', [
            'projeto'    => $projeto,
            'modosGrupo' => self::MODOS_GRUPO,
            'modosPor'   => self::MODOS_POR,
        ]);
    }

    public function salvar(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projetoId . '/grupos');
        }

        $nome  = trim($_POST['nome'] ?? '');
        $modoG = $_POST['modo_avaliacao_grupo'] ?? 'individual';
        $modoP = $_POST['modo_avaliacao_por']   ?? 'diretor';

        $erros = [];
        if (strlen($nome) < 2) $erros[] = 'Nome muito curto.';
        if (!array_key_exists($modoG, self::MODOS_GRUPO)) $erros[] = 'Modo de grupo inválido.';
        if (!array_key_exists($modoP, self::MODOS_POR))   $erros[] = 'Modo de avaliação inválido.';

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/projetos/' . $projetoId . '/grupos/criar');
        }

        $id = $this->grupo->criar([
            'projeto_id'           => $projetoId,
            'nome'                 => $nome,
            'modo_avaliacao_grupo' => $modoG,
            'modo_avaliacao_por'   => $modoP,
        ]);

        $this->audit->registrar('grupo_criado', 'grupos', $id, null,
            ['projeto_id' => $projetoId, 'nome' => $nome]);

        $this->flash('Grupo criado.', 'sucesso');
        $this->redirect('/grupos/' . $id);
    }

    public function detalhe(int $id)
    {
        $grupo = $this->grupo->porId($id);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirAcessoProjeto($projeto);

        $podeEditar = $this->podeEditarProjeto($projeto) && !$projeto['encerrado'];

        $this->render('grupos/detalhe', [
            'grupo'      => $grupo,
            'projeto'    => $projeto,
            'membros'    => $this->membro->listarAtivos($id),
            'diretores'  => $this->diretor->listarAtivos($id),
            'candidatos' => $podeEditar
                ? $this->membro->candidatosNaTurma($id, (int) $projeto['turma_id'])
                : [],
            'podeEditar' => $podeEditar,
        ]);
    }

    public function editarForm(int $id)
    {
        $grupo = $this->grupo->porId($id);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/grupos/' . $id);
        }

        $this->render('grupos/editar', [
            'grupo'      => $grupo,
            'projeto'    => $projeto,
            'modosGrupo' => self::MODOS_GRUPO,
            'modosPor'   => self::MODOS_POR,
        ]);
    }

    public function atualizar(int $id)
    {
        $grupo = $this->grupo->porId($id);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/grupos/' . $id);
        }

        $nome  = trim($_POST['nome'] ?? '');
        $modoG = $_POST['modo_avaliacao_grupo'] ?? 'individual';
        $modoP = $_POST['modo_avaliacao_por']   ?? 'diretor';

        $erros = [];
        if (strlen($nome) < 2) $erros[] = 'Nome muito curto.';
        if (!array_key_exists($modoG, self::MODOS_GRUPO)) $erros[] = 'Modo inválido.';
        if (!array_key_exists($modoP, self::MODOS_POR))   $erros[] = 'Modo por inválido.';

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/grupos/' . $id . '/editar');
        }

        $this->grupo->atualizar($id, [
            'nome'                 => $nome,
            'modo_avaliacao_grupo' => $modoG,
            'modo_avaliacao_por'   => $modoP,
        ]);

        $this->audit->registrar('grupo_atualizado', 'grupos', $id, $grupo, ['nome' => $nome]);
        $this->flash('Grupo atualizado.', 'sucesso');
        $this->redirect('/grupos/' . $id);
    }

    public function excluir(int $id)
    {
        $grupo = $this->grupo->porId($id);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/grupos/' . $id);
        }

        $pid = (int) $projeto['id'];
        $this->grupo->excluir($id);
        $this->audit->registrar('grupo_excluido', 'grupos', $id, $grupo, null);
        $this->flash('Grupo excluído.', 'sucesso');
        $this->redirect('/projetos/' . $pid . '/grupos');
    }

    public function adicionarMembro(int $id)
    {
        $grupo = $this->grupo->porId($id);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/grupos/' . $id);
        }

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        if (!$usuarioId) { $this->flash('Selecione um aluno.'); $this->redirect('/grupos/' . $id); }

        if (!$this->tu->estaAtivo((int) $projeto['turma_id'], $usuarioId)) {
            $this->flash('Aluno não está ativo nesta turma.'); $this->redirect('/grupos/' . $id);
        }
        if ($this->membro->estaAtivo($id, $usuarioId)) {
            $this->flash('Aluno já está no grupo.'); $this->redirect('/grupos/' . $id);
        }

        $this->membro->adicionar($id, $usuarioId);
        $this->audit->registrar('grupo_membro_adicionado', 'grupo_alunos', $id, null,
            ['usuario_id' => $usuarioId]);

        $this->flash('Aluno adicionado ao grupo.', 'sucesso');
        $this->redirect('/grupos/' . $id);
    }

    public function removerMembro(int $id)
    {
        $grupo = $this->grupo->porId($id);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/grupos/' . $id);
        }

        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        if (!$usuarioId) { $this->flash('Aluno não informado.'); $this->redirect('/grupos/' . $id); }

        $this->membro->remover($id, $usuarioId);
        $this->audit->registrar('grupo_membro_removido', 'grupo_alunos', $id,
            ['usuario_id' => $usuarioId], null);

        $this->flash('Aluno removido do grupo.', 'sucesso');
        $this->redirect('/grupos/' . $id);
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

    private function podeEditarProjeto(array $projeto): bool
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) return false;
        if ($u['tipo'] === 'master') return true;
        return $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
    }

    private function exigirPodeEditarProjeto(array $projeto): void
    {
        if (!$this->podeEditarProjeto($projeto)) {
            http_response_code(403);
            exit('Apenas representante da turma ou master.');
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

    private function flash(string $msg, string $tipo = 'erro'): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $msg];
    }
}