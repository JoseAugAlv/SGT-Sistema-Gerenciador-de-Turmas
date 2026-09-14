<?php
// app/Controllers/AvaliacaoController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Criterio.php';
require_once __DIR__ . '/../Models/Etapa.php';
require_once __DIR__ . '/../Models/Avaliacao.php';
require_once __DIR__ . '/../Models/AvaliacaoColetiva.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/GrupoAluno.php';
require_once __DIR__ . '/../Models/GrupoDiretor.php';
require_once __DIR__ . '/../Models/ConfiguracaoConceito.php';
require_once __DIR__ . '/../Services/NotaService.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../Config/database.php';

class AvaliacaoController
{
    private Projeto              $projeto;
    private Criterio             $criterio;
    private Avaliacao            $aval;
    private AvaliacaoColetiva    $coletiva;
    private TurmaUsuario         $tu;
    private GrupoAluno           $membro;
    private GrupoDiretor         $diretor;
    private ConfiguracaoConceito $cfg;
    private NotaService          $nota;
    private Auditoria            $audit;

    public function __construct()
    {
        $this->projeto  = new Projeto();
        $this->criterio = new Criterio();
        $this->aval     = new Avaliacao();
        $this->coletiva = new AvaliacaoColetiva();
        $this->tu       = new TurmaUsuario();
        $this->membro   = new GrupoAluno();
        $this->diretor  = new GrupoDiretor();
        $this->cfg      = new ConfiguracaoConceito();
        $this->nota     = new NotaService();
        $this->audit    = new Auditoria();
    }

    /**
     * Redireciona para a visão correta conforme o papel do usuário.
     */
    public function index(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
        $souDiretor = $this->temDiretorAtivoNoProjeto((int) $projeto['id'], (int) $u['id']);

        if ($souMaster || $souRep) {
            $this->redirect('/projetos/' . $projetoId . '/avaliacoes/representante');
        }
        if ($souDiretor) {
            $this->redirect('/projetos/' . $projetoId . '/avaliacoes/diretor');
        }
        $this->redirect('/projetos/' . $projetoId . '/avaliacoes/minhas');
    }

    // ============ BULK REPRESENTANTE ============

    public function bulkRepresentante(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

        if (!$souMaster && !$souRep) {
            http_response_code(403); exit('Apenas representante ou master.');
        }

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.', 'aviso');
            $this->redirect('/projetos/' . $projetoId . '/criterios');
        }

        $this->criterio->atualizarBloqueios($projetoId);

        $cfg         = $this->cfg->garantir($projetoId);
        $criterios   = $this->criterio->listarPorProjeto($projetoId);
        $alunos      = $this->tu->listarAlunos((int) $projeto['turma_id']);

        // Separa critérios: aplicáveis a todos vs apenas para diretores vs apenas representantes
        $critTodos        = [];
        $critDiretores    = [];
        $critRepresent    = [];
        foreach ($criterios as $c) {
            if ($c['tipo_avaliacao'] === 'coletiva') continue; // coletiva tem tela própria
            switch ($c['aplicavel_a']) {
                case 'apenas_diretores':      $critDiretores[] = $c; break;
                case 'apenas_representantes': $critRepresent[] = $c; break;
                default:                      $critTodos[]     = $c;
            }
        }

        // Busca avaliações já feitas por este representante
        $minhasAvaliacoes = [];
        foreach ($criterios as $c) {
            $minhasAvaliacoes[(int) $c['id']] = $this->aval->listarDoAvaliador((int) $c['id'], (int) $u['id']);
        }

        $this->render('avaliacoes/index_rep', [
            'projeto'          => $projeto,
            'cfg'              => $cfg,
            'critTodos'        => $critTodos,
            'critDiretores'    => $critDiretores,
            'critRepresent'    => $critRepresent,
            'alunos'           => $alunos,
            'minhasAvaliacoes' => $minhasAvaliacoes,
        ]);
    }

    public function bulkRepresentanteSalvar(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
        if (!$souMaster && !$souRep) { http_response_code(403); exit('Sem permissão.'); }

        CsrfMiddleware::validate();

        if ($projeto['encerrado']) { $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projetoId); }

        $limpar = !empty($_POST['limpar_antes']);

        if ($limpar) {
            $this->aval->deletarDoAvaliadorNoProjeto($projetoId, (int) $u['id']);
        }

        $this->criterio->atualizarBloqueios($projetoId);
        $cfg       = $this->cfg->garantir($projetoId);
        $criterios = $this->criterio->listarPorProjeto($projetoId);

        $salvos = 0;
        foreach ($criterios as $c) {
            if ($c['bloqueado']) continue;
            if ($c['tipo_avaliacao'] === 'coletiva') continue;

            $cid = (int) $c['id'];
            $tipoAval = Avaliacao::MAPA_TIPO[$c['tipo_avaliacao']] ?? null;

            // Decide se este representante aplica este critério
            // Critérios 'diretor' são aplicados pelo representante apenas se o aluno for
            // único diretor do grupo — deixamos para a F6 parte 2 (não forçamos aqui)
            $aplica = in_array($c['tipo_avaliacao'], ['representante', 'pares', 'autoavaliacao', 'misto'], true)
                   || ($c['tipo_avaliacao'] === 'diretor' && $souMaster);
            if (!$aplica) continue;
            if (!$tipoAval) continue;

            // Pega lista de valores do POST: aval[cid][aluno_id]
            $linha = $_POST['aval'][$cid] ?? [];
            foreach ($linha as $alunoId => $dados) {
                $alunoId = (int) $alunoId;
                $conceito = $dados['conceito'] ?? '';
                $valor    = (float) str_replace(',', '.', $dados['valor'] ?? '');

                if ($conceito === '' && $valor == 0.0) {
                    // vazio: se estava salvo, remove
                    $this->aval->deletar($cid, $alunoId, (int) $u['id'], $tipoAval);
                    continue;
                }

                if (!in_array($conceito, ['I','R','B','MB'], true)) {
                    $conceito = NotaService::conceitoPorValor($valor, $cfg);
                }
                if ($valor <= 0) {
                    $valor = NotaService::valorPadraoPorConceito($conceito, $cfg);
                }

                $this->aval->upsert(
                    $cid, $alunoId, (int) $u['id'], $tipoAval,
                    $conceito, $valor, null, null
                );
                $salvos++;
            }
        }

        $this->audit->registrar('avaliacao_rep_salva', 'avaliacoes', $projetoId, null,
            ['avaliador_id' => $u['id'], 'limpar' => $limpar, 'total' => $salvos]);

        $this->flash("Avaliações salvas ({$salvos}).", 'sucesso');
        $this->redirect('/projetos/' . $projetoId . '/avaliacoes/representante');
    }

    // ============ MINHAS NOTAS (ALUNO) ============

    public function minhasNotas(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $boletim = $this->nota->boletimDoAluno($projetoId, (int) $u['id']);
        $cfg     = $this->cfg->garantir($projetoId);

        $this->render('avaliacoes/minhas_notas', [
            'projeto' => $projeto,
            'boletim' => $boletim,
            'cfg'     => $cfg,
        ]);
    }

    // ============ HELPERS ============

    private function temDiretorAtivoNoProjeto(int $projetoId, int $usuarioId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 1
            FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            WHERE g.projeto_id = ? AND gd.usuario_id = ? AND gd.ativo = 1
            LIMIT 1
        ");
        $stmt->execute([$projetoId, $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    private function exigirAcesso(array $projeto): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;
        if ($this->tu->estaAtivo((int) $projeto['turma_id'], (int) $u['id'])) return;
        http_response_code(403); exit('Sem acesso.');
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