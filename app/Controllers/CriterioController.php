<?php
// app/Controllers/CriterioController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Etapa.php';
require_once __DIR__ . '/../Models/Criterio.php';
require_once __DIR__ . '/../Models/AvaliacaoArquivada.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Models/DescontoNota.php';
require_once __DIR__ . '/../Models/Notificacao.php';
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

        // Critérios obrigatórios: quem ainda não preencheu e descontos já aplicados (só representante/master vê)
        $faltamPreencher = [];
        $descontos       = [];
        if ($this->podeEditarProjeto($projeto)) {
            $descModel = new DescontoNota();
            foreach ($criterios as $c) {
                if (!empty($c['obrigatorio'])) {
                    $faltamPreencher[(int) $c['id']] = $this->criterio->quemFaltaPreencher($c, (int) $projeto['turma_id']);
                    $descontos[(int) $c['id']]       = $descModel->doCriterio((int) $c['id']);
                }
            }
        }

        $statusPeso = 'incompleto';
        if (abs($soma - 10.0) < 0.01) $statusPeso = 'completo';
        elseif ($soma > 10.0)         $statusPeso = 'excedido';

        $this->render('criterios/index', [
            'projeto'    => $projeto,
            'criterios'  => $criterios,
            'somaPesos'  => $soma,
            'statusPeso' => $statusPeso,
            'podeEditar' => $this->podeEditarProjeto($projeto) && !$projeto['encerrado'],
            'podeReabrir'=> !$projeto['encerrado'] && $this->podeReabrir([], $projeto),
            'faltamPreencher' => $faltamPreencher,
            'descontos'       => $descontos,
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
            'somaPesos'  => $this->criterio->somaPesos($projetoId),
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
        if ($dados['prazo_avaliacao'] && strtotime($dados['prazo_avaliacao']) <= time()) {
            $dados['_erros'][] = 'O prazo de avaliação precisa ser no futuro.';
        }
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
            'somaPesos'  => $this->criterio->somaPesos((int) $projeto['id']) - (float) $criterio['peso'],
            'temAvaliacoes' => $this->criterio->totalAvaliacoes($id) > 0,
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
        // Com notas lançadas, trocar o tipo deixaria essas notas órfãs (outro avaliador, outro cálculo).
        if ($dados['tipo_avaliacao'] !== $criterio['tipo_avaliacao'] && $this->criterio->totalAvaliacoes($id) > 0) {
            $dados['_erros'][] = 'Este critério já tem notas lançadas, então o tipo de avaliação não pode mudar. Exclua e crie outro se precisar.';
        }
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

    // ============ DESCONTO POR NÃO PREENCHER CRITÉRIO OBRIGATÓRIO ============

    public function descontar(int $id)
    {
        [$criterio, $projeto] = $this->criterioParaDesconto($id);

        $pontos = (float) str_replace(',', '.', $_POST['pontos'] ?? '0');
        $alunos = array_map('intval', (array) ($_POST['alunos'] ?? []));
        $voltar = '/projetos/' . $projeto['id'] . '/criterios';

        if ($pontos <= 0 || $pontos > 100) { $this->flash('Informe um desconto entre 0,1 e 100 pontos.'); $this->redirect($voltar); }
        if (!$alunos)                      { $this->flash('Marque pelo menos um aluno.');               $this->redirect($voltar); }

        // Só quem de fato não preencheu pode receber o desconto
        $faltam = $this->criterio->quemFaltaPreencher($criterio, (int) $projeto['turma_id']);
        $alunos = array_values(array_filter($alunos, fn($a) => isset($faltam[$a])));
        if (!$alunos) { $this->flash('Nenhum dos alunos marcados está na lista de quem não preencheu.'); $this->redirect($voltar); }

        $motivo = "Não preencheu o critério obrigatório '{$criterio['nome']}'";
        $desc   = new DescontoNota();
        $notif  = new Notificacao();
        $pts    = number_format($pontos, 1, ',', '.');
        foreach ($alunos as $aid) {
            $desc->aplicar($id, $aid, $pontos, $motivo, (int) $_SESSION['usuario']['id']);
            $notif->criar($aid, 'avaliacao', 'Desconto na sua nota',
                "Você perdeu {$pts} ponto(s) na nota do projeto '{$projeto['nome']}' porque não preencheu o critério obrigatório '{$criterio['nome']}' até o prazo.",
                '/projetos/' . $projeto['id'] . '/avaliacoes/minhas', $id);
        }
        $this->audit->registrar('desconto_aplicado', 'criterios', $id, null, ['alunos' => $alunos, 'pontos' => $pontos]);

        $this->flash('Desconto de ' . $pts . ' ponto(s) aplicado a ' . count($alunos) . ' aluno(s). Eles foram avisados.', 'sucesso');
        $this->redirect($voltar);
    }

    public function removerDesconto(int $id)
    {
        [$criterio, $projeto] = $this->criterioParaDesconto($id);
        $alunoId = (int) ($_POST['aluno_id'] ?? 0);

        if ((new DescontoNota())->remover($id, $alunoId)) {
            $this->audit->registrar('desconto_removido', 'criterios', $id, ['aluno_id' => $alunoId], null);
            $this->flash('Desconto removido.', 'sucesso');
        }
        $this->redirect('/projetos/' . $projeto['id'] . '/criterios');
    }

    /** Critério obrigatório, já encerrado, de um projeto aberto que o usuário gerencia. */
    private function criterioParaDesconto(int $id): array
    {
        $criterio = $this->criterio->porId($id);
        if (!$criterio) { $this->flash('Critério não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $criterio['projeto_id']);
        $this->exigirPodeEditarProjeto($projeto);
        CsrfMiddleware::validate();

        $voltar = '/projetos/' . $projeto['id'] . '/criterios';
        if ($projeto['encerrado'])         { $this->flash('Projeto encerrado: as notas estão congeladas.'); $this->redirect($voltar); }
        if (empty($criterio['obrigatorio'])) { $this->flash('Desconto só vale para critério obrigatório.');  $this->redirect($voltar); }
        if (empty($criterio['bloqueado']))   { $this->flash('Espere o prazo do critério fechar para aplicar desconto.'); $this->redirect($voltar); }

        return [$criterio, $projeto];
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

        $obrigatorio = !empty($post['obrigatorio']) && in_array($tipo, Criterio::TIPOS_DO_ALUNO, true);
        if ($obrigatorio && $prazo === '') {
            $erros[] = 'Critério obrigatório precisa de prazo: é até ele que os alunos devem preencher.';
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
            'obrigatorio'     => $obrigatorio ? 1 : 0,
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
        $stmt = Database::getConnection()->prepare("
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