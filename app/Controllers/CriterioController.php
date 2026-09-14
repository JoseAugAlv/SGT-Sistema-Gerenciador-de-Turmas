<?php
// app/Controllers/CriterioController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Etapa.php';
require_once __DIR__ . '/../Models/Criterio.php';
require_once __DIR__ . '/../Models/AvaliacaoArquivada.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class CriterioController
{
    private Projeto            $projeto;
    private Etapa              $etapa;
    private Criterio           $criterio;
    private AvaliacaoArquivada $arquivo;
    private TurmaUsuario       $tu;
    private Auditoria          $audit;

    public function __construct()
    {
        $this->projeto  = new Projeto();
        $this->etapa    = new Etapa();
        $this->criterio = new Criterio();
        $this->arquivo  = new AvaliacaoArquivada();
        $this->tu       = new TurmaUsuario();
        $this->audit    = new Auditoria();
    }

    public function index(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirAcessoProjeto($projeto);

        // Atualiza bloqueios por prazo antes de listar
        $this->criterio->atualizarBloqueios($projetoId);

        $criterios = $this->criterio->listarPorProjeto($projetoId);
        $soma      = $this->criterio->somaPesos($projetoId);

        $statusPeso = 'incompleto';
        if (abs($soma - 10.0) < 0.01) $statusPeso = 'completo';
        elseif ($soma > 10.0)         $statusPeso = 'excedido';

        $this->render('criterios/index', [
            'projeto'    => $projeto,
            'criterios'  => $criterios,
            'somaPesos'  => $soma,
            'statusPeso' => $statusPeso,
            'podeEditar' => $this->podeEditarProjeto($projeto) && !$projeto['encerrado'],
        ]);
    }

    public function criarForm(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirPodeEditarProjeto($projeto);
        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projetoId . '/criterios');
        }

        $this->render('criterios/criar', [
            'projeto'    => $projeto,
            'etapas'     => $this->etapa->listarPorProjeto($projetoId),
            'tipos'      => Criterio::TIPOS,
            'aplicaveis' => Criterio::APLICAVEIS,
        ]);
    }

    public function salvar(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projetoId . '/criterios');
        }

        $dados = $this->validarDados($_POST, $projetoId);
        if (!empty($dados['_erros'])) {
            $this->flash(implode(' | ', $dados['_erros']));
            $this->redirect('/projetos/' . $projetoId . '/criterios/criar');
        }
        unset($dados['_erros']);

        $id = $this->criterio->criar($dados);
        $this->audit->registrar('criterio_criado', 'criterios', $id, null, $dados);

        $this->flash('Critério criado.', 'sucesso');
        $this->redirect('/projetos/' . $projetoId . '/criterios');
    }

    public function editarForm(int $id)
    {
        $criterio = $this->criterio->porId($id);
        if (!$criterio) { $this->flash('Critério não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $criterio['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projeto['id'] . '/criterios');
        }

        $this->render('criterios/editar', [
            'criterio'   => $criterio,
            'projeto'    => $projeto,
            'etapas'     => $this->etapa->listarPorProjeto((int) $projeto['id']),
            'tipos'      => Criterio::TIPOS,
            'aplicaveis' => Criterio::APLICAVEIS,
        ]);
    }

    public function atualizar(int $id)
    {
        $criterio = $this->criterio->porId($id);
        if (!$criterio) { $this->flash('Critério não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $criterio['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projeto['id'] . '/criterios');
        }

        $dados = $this->validarDados($_POST, (int) $projeto['id']);
        if (!empty($dados['_erros'])) {
            $this->flash(implode(' | ', $dados['_erros']));
            $this->redirect('/criterios/' . $id . '/editar');
        }
        unset($dados['_erros']);

        $this->criterio->atualizar($id, $dados);
        $this->audit->registrar('criterio_atualizado', 'criterios', $id, $criterio, $dados);

        $this->flash('Critério atualizado.', 'sucesso');
        $this->redirect('/projetos/' . $projeto['id'] . '/criterios');
    }

    /**
     * Exclui ou arquiva o critério.
     * Se houver avaliações: arquiva em JSON com expira_em = hoje + 30d antes de excluir.
     */
    public function excluir(int $id)
    {
        $criterio = $this->criterio->porId($id);
        if (!$criterio) { $this->flash('Critério não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $criterio['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.'); $this->redirect('/projetos/' . $projeto['id'] . '/criterios');
        }

        $pid = (int) $projeto['id'];

        $avaliacoes = $this->criterio->coletarAvaliacoes($id);
        $temAvaliacoes = !empty($avaliacoes['individuais']) || !empty($avaliacoes['coletivas']);

        $arquivado = false;
        if ($temAvaliacoes) {
            $this->arquivo->arquivar(
                $id,
                $pid,
                $avaliacoes,
                (int) $_SESSION['usuario']['id']
            );
            $arquivado = true;
        }

        $this->criterio->excluir($id);

        $this->audit->registrar('criterio_excluido', 'criterios', $id, $criterio,
            ['arquivado' => $arquivado]);

        if ($arquivado) {
            $this->flash('Critério arquivado por 30 dias e removido. Master pode restaurar.', 'sucesso');
        } else {
            $this->flash('Critério excluído.', 'sucesso');
        }
        $this->redirect('/projetos/' . $pid . '/criterios');
    }

    public function reabrirForm(int $id)
    {
        $criterio = $this->criterio->porId($id);
        if (!$criterio) { $this->flash('Critério não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $criterio['projeto_id']);
        $this->exigirAcessoProjeto($projeto);

        // Quem pode reabrir: master ou ocupante atual do cargo
        if (!$this->podeReabrir($criterio, $projeto)) {
            http_response_code(403);
            exit('Apenas master ou ocupante atual do cargo (representante/diretor) pode reabrir.');
        }

        $this->render('criterios/reabrir', [
            'criterio' => $criterio,
            'projeto'  => $projeto,
        ]);
    }

    public function reabrir(int $id)
    {
        $criterio = $this->criterio->porId($id);
        if (!$criterio) { $this->flash('Critério não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $criterio['projeto_id']);
        $this->exigirAcessoProjeto($projeto);
        CsrfMiddleware::validate();

        if (!$this->podeReabrir($criterio, $projeto)) {
            http_response_code(403);
            exit('Sem permissão para reabrir.');
        }

        $novoPrazo = trim($_POST['novo_prazo'] ?? '');
        if ($novoPrazo === '' || !DateTime::createFromFormat('Y-m-d\TH:i', $novoPrazo)) {
            $this->flash('Informe um novo prazo válido (obrigatório).');
            $this->redirect('/criterios/' . $id . '/reabrir');
        }

        $novoPrazoSql = str_replace('T', ' ', $novoPrazo) . ':00';

        if (strtotime($novoPrazoSql) <= time()) {
            $this->flash('O novo prazo deve ser no futuro.');
            $this->redirect('/criterios/' . $id . '/reabrir');
        }

        $this->criterio->reabrir($id, (int) $_SESSION['usuario']['id'], $novoPrazoSql);

        $this->audit->registrar('criterio_reaberto', 'criterios', $id,
            ['bloqueado' => $criterio['bloqueado'], 'prazo' => $criterio['prazo_avaliacao']],
            ['bloqueado' => 0, 'prazo' => $novoPrazoSql]);

        $this->flash('Critério reaberto com novo prazo.', 'sucesso');
        $this->redirect('/projetos/' . $projeto['id'] . '/criterios');
    }

    // ============ HELPERS ============

    private function validarDados(array $post, int $projetoId): array
    {
        $etapaId    = (int) ($post['etapa_id'] ?? 0);
        $nome       = trim($post['nome'] ?? '');
        $descricao  = trim($post['descricao'] ?? '') ?: null;
        $peso       = (float) str_replace(',', '.', $post['peso'] ?? '0');
        $tipo       = $post['tipo_avaliacao'] ?? '';
        $aplicavel  = $post['aplicavel_a'] ?? 'todos';
        $prazo      = trim($post['prazo_avaliacao'] ?? '');

        $erros = [];

        if (strlen($nome) < 2)                 $erros[] = 'Nome muito curto.';
        if ($peso < 0.01 || $peso > 99.99)     $erros[] = 'Peso deve estar entre 0.01 e 99.99.';
        if (!array_key_exists($tipo, Criterio::TIPOS))         $erros[] = 'Tipo de avaliação inválido.';
        if (!array_key_exists($aplicavel, Criterio::APLICAVEIS)) $erros[] = 'Aplicabilidade inválida.';

        if ($etapaId) {
            $etapa = $this->etapa->porId($etapaId);
            if (!$etapa || (int) $etapa['projeto_id'] !== $projetoId) {
                $erros[] = 'Etapa inválida para este projeto.';
            }
        }

        $prazoSql = null;
        if ($prazo !== '') {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $prazo);
            if (!$dt) {
                $erros[] = 'Prazo inválido.';
            } else {
                $prazoSql = str_replace('T', ' ', $prazo) . ':00';
            }
        }

        return [
            'projeto_id'      => $projetoId,
            'etapa_id'        => $etapaId ?: null,
            'nome'            => $nome,
            'descricao'       => $descricao,
            'peso'            => $peso,
            'tipo_avaliacao'  => $tipo,
            'aplicavel_a'     => $aplicavel,
            'prazo_avaliacao' => $prazoSql,
            '_erros'          => $erros,
        ];
    }

    private function podeReabrir(array $criterio, array $projeto): bool
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) return false;
        if ($u['tipo'] === 'master') return true;

        // Representante atual da turma OU diretor atual do grupo
        if ($this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id'])) {
            return true;
        }

        // Diretor ativo de algum grupo do projeto
        $stmt = $this->tu->conn->prepare("
            SELECT 1
            FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            WHERE g.projeto_id = ? AND gd.usuario_id = ? AND gd.ativo = 1
            LIMIT 1
        ");
        $stmt->execute([(int) $projeto['id'], (int) $u['id']]);
        return (bool) $stmt->fetchColumn();
    }

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