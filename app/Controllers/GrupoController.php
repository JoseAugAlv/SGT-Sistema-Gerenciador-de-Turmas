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

    // Alunos da turma com seus grupos neste projeto
    $pdo  = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.email,
               (
                   SELECT GROUP_CONCAT(g2.nome ORDER BY g2.nome SEPARATOR ', ')
                   FROM grupo_alunos ga2
                   INNER JOIN grupos g2 ON g2.id = ga2.grupo_id
                   WHERE ga2.usuario_id = u.id
                     AND ga2.saiu_em IS NULL
                     AND g2.projeto_id = ?
               ) AS grupos_nomes
        FROM turma_usuarios tu
        INNER JOIN usuarios u ON u.id = tu.usuario_id
        WHERE tu.turma_id = ? AND tu.ativo = 1
        ORDER BY u.nome ASC
    ");
    $stmt->execute([$projetoId, (int) $projeto['turma_id']]);
    $alunosComGrupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $this->render('grupos/index', [
        'projeto'         => $projeto,
        'grupos'          => $this->grupo->listarPorProjeto($projetoId),
        'alunosComGrupos' => $alunosComGrupos,
        'podeEditar'      => $this->podeEditarProjeto($projeto),
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

    // Aceita tanto usuario_id (single) quanto usuario_ids[] (múltiplo)
    $ids = [];
    if (!empty($_POST['usuario_ids'])) {
        $ids = array_map('intval', (array) $_POST['usuario_ids']);
    } elseif (!empty($_POST['usuario_id'])) {
        $ids = [(int) $_POST['usuario_id']];
    }
    $ids = array_values(array_unique(array_filter($ids, fn($x) => $x > 0)));

    if (empty($ids)) {
        $this->flash('Selecione pelo menos um aluno.');
        $this->redirect('/grupos/' . $id);
    }

    $adicionados = 0;
    foreach ($ids as $uid) {
        if (!$this->tu->estaAtivo((int) $projeto['turma_id'], $uid)) continue;
        if ($this->membro->estaAtivo($id, $uid)) continue;

        $this->membro->adicionar($id, $uid);
        $adicionados++;
    }

    $this->audit->registrar('grupo_membros_adicionados', 'grupo_alunos', $id, null,
        ['usuario_ids' => $ids, 'adicionados' => $adicionados]);

    $this->flash("{$adicionados} aluno(s) adicionado(s) ao grupo.", 'sucesso');
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

        // ============ CONFIGURAÇÃO DE AVALIAÇÃO DO GRUPO ============

    public function configAvaliacaoForm(int $id)
    {
        $grupo = $this->grupo->porId($id);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/grupos/' . $id);
        }

        $this->render('grupos/config_avaliacao', [
            'grupo'      => $grupo,
            'projeto'    => $projeto,
            'modosGrupo' => self::MODOS_GRUPO,
            'modosPor'   => self::MODOS_POR,
        ]);
    }

    public function configAvaliacaoSalvar(int $id)
    {
        $grupo = $this->grupo->porId($id);
        if (!$grupo) { $this->flash('Grupo não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $grupo['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/grupos/' . $id);
        }

        $modoG = $_POST['modo_avaliacao_grupo'] ?? 'individual';
        $modoP = $_POST['modo_avaliacao_por']   ?? 'diretor';

        $erros = [];
        if (!array_key_exists($modoG, self::MODOS_GRUPO)) $erros[] = 'Modo de grupo inválido.';
        if (!array_key_exists($modoP, self::MODOS_POR))   $erros[] = 'Modo por inválido.';

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/grupos/' . $id . '/config-avaliacao');
        }

        $this->grupo->atualizar($id, [
            'nome'                 => $grupo['nome'],
            'modo_avaliacao_grupo' => $modoG,
            'modo_avaliacao_por'   => $modoP,
        ]);

        $this->audit->registrar('grupo_config_avaliacao_atualizada', 'grupos', $id,
            ['modo_grupo' => $grupo['modo_avaliacao_grupo'], 'modo_por' => $grupo['modo_avaliacao_por']],
            ['modo_grupo' => $modoG, 'modo_por' => $modoP]);

        $this->flash('Configuração de avaliação atualizada.', 'sucesso');
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