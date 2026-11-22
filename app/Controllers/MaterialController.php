<?php
// app/Controllers/MaterialController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Material.php';
require_once __DIR__ . '/../Models/MovimentacaoMaterial.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../Models/SolicitacaoMaterial.php';
require_once __DIR__ . '/../Models/Notificacao.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class MaterialController
{
    const TIPOS_MOV = [
        'cadastro' => 'Cadastro',
        'compra'   => 'Compra',
        'uso'      => 'Uso (retirada)',
        'ajuste'   => 'Ajuste',
    ];
    const UNIDADES = [
        'un'      => 'Unidade (un)',
        'cx'      => 'Caixa (cx)',
        'pct'     => 'Pacote (pct)',
        'resma'   => 'Resma (resma)',
        'rolo'    => 'Rolo (rolo)',
        'kg'      => 'Quilograma (kg)',
        'g'       => 'Grama (g)',
        'L'       => 'Litro (L)',
        'mL'      => 'Mililitro (mL)',
        'm'       => 'Metro (m)',
        'cm'      => 'Centímetro (cm)',
        'm2'      => 'Metro quadrado (m²)',
        'm3'      => 'Metro cúbico (m³)',
        'h'       => 'Hora (h)',
        'outro'   => 'Outro',
    ];

    private Material            $material;
    private MovimentacaoMaterial $mov;
    private Projeto             $projeto;
    private TurmaUsuario        $tu;
    private Auditoria           $audit;
    private SolicitacaoMaterial $sol;

    public function __construct()
    {
        $this->material = new Material();
        $this->mov      = new MovimentacaoMaterial();
        $this->projeto  = new Projeto();
        $this->tu       = new TurmaUsuario();
        $this->audit    = new Auditoria();
        $this->sol = new SolicitacaoMaterial();
    }

    // ============ LISTAGEM ============

    public function index(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirAcesso($projeto);
        $podeEditar = $this->podeEditar($projeto);

        $this->render('materiais/index', [
            'projeto'      => $projeto,
            'materiais'    => $this->material->listarPorProjeto($projetoId),
            'podeEditar'   => $podeEditar,
            'solicitacoes' => $this->sol->listarAbertas($projetoId),
            'totalAbertas' => $this->sol->contarAbertas($projetoId),
        ]);
    }

    // ============ CADASTRAR ============

    public function cadastrarForm(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirPodeEditar($projeto);

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/projetos/' . $projetoId . '/materiais');
        }

        $this->render('materiais/cadastrar', [
            'projeto'  => $projeto,
            'unidades' => self::UNIDADES,
        ]);
    }
    // ============ SOLICITAÇÕES DE MATERIAL ============

public function solicitarForm(int $projetoId)
{
    $projeto = $this->projeto->porId($projetoId);
    if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

    $this->exigirAcesso($projeto);

    if ($projeto['encerrado']) {
        $this->flash('Projeto encerrado.');
        $this->redirect('/projetos/' . $projetoId . '/materiais');
    }

    $this->render('materiais/solicitar', [
        'projeto'  => $projeto,
        'unidades' => self::UNIDADES,
    ]);
}

public function solicitar(int $projetoId)
{
    $projeto = $this->projeto->porId($projetoId);
    if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

    $this->exigirAcesso($projeto);
    CsrfMiddleware::validate();

    if ($projeto['encerrado']) {
        $this->flash('Projeto encerrado.');
        $this->redirect('/projetos/' . $projetoId . '/materiais');
    }

    $nome      = trim($_POST['nome_material'] ?? '');
    $qtd       = (float) str_replace(',', '.', $_POST['quantidade'] ?? '0');
    $unidade   = trim($_POST['unidade'] ?? '') ?: null;
    $just      = trim($_POST['justificativa'] ?? '') ?: null;
    $preco     = trim($_POST['preco_estimado'] ?? '');
    $preco     = $preco !== '' ? (float) str_replace(',', '.', $preco) : null;

    $erros = [];
    if (strlen($nome) < 2)                                             $erros[] = 'Nome do material muito curto.';
    if ($qtd <= 0)                                                     $erros[] = 'Quantidade deve ser maior que zero.';
    if ($unidade && !array_key_exists($unidade, self::UNIDADES))       $erros[] = 'Unidade inválida.';
    if ($preco !== null && $preco < 0)                                 $erros[] = 'Preço estimado não pode ser negativo.';

    if ($erros) {
        $this->flash(implode(' | ', $erros));
        $this->redirect('/projetos/' . $projetoId . '/materiais/solicitar');
    }

    $u = $_SESSION['usuario'];
    $id = $this->sol->criar([
        'projeto_id'      => $projetoId,
        'turma_id'        => (int) $projeto['turma_id'],
        'solicitante_id'  => (int) $u['id'],
        'nome_material'   => $nome,
        'quantidade'      => $qtd,
        'unidade'         => $unidade,
        'justificativa'   => $just,
        'preco_estimado'  => $preco,
    ]);

    $this->audit->registrar('material_solicitado', 'solicitacoes_materiais', $id, null, [
        'nome' => $nome, 'quantidade' => $qtd,
    ]);

    // Notifica rep + diretores ativos do projeto
    $this->notificarResponsaveis($projeto, $id, $nome, $u['nome']);

    $this->flash('Solicitação enviada. Aguarde a análise do representante ou diretor.', 'sucesso');
    $this->redirect('/projetos/' . $projetoId . '/materiais');
}

public function solicitacoes(int $projetoId)
{
    $projeto = $this->projeto->porId($projetoId);
    if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

    $this->exigirAcesso($projeto);
    $podeGerenciar = $this->podeEditar($projeto);
    $u             = $_SESSION['usuario'];

    $status = $_GET['status'] ?? null;
    if ($status && !array_key_exists($status, SolicitacaoMaterial::STATUS)) {
        $status = null;
    }

    $lista = $this->sol->listarPorProjeto($projetoId, $status);

    $this->render('materiais/solicitacoes', [
        'projeto'       => $projeto,
        'solicitacoes'  => $lista,
        'statusFiltro'  => $status,
        'statusLabels'  => SolicitacaoMaterial::STATUS,
        'podeGerenciar' => $podeGerenciar,
        'meuId'         => (int) $u['id'],
    ]);
}

public function aprovarSolicitacao(int $id)
{
    $sol = $this->sol->porId($id);
    if (!$sol) { $this->flash('Solicitação não encontrada.'); $this->redirect('/turmas'); }

    $projeto = $this->projeto->porId((int) $sol['projeto_id']);
    $this->exigirPodeEditar($projeto);
    CsrfMiddleware::validate();

    if ($projeto['encerrado']) {
        $this->flash('Projeto encerrado: materiais não podem mais ser alterados.');
        $this->redirect('/projetos/' . $projeto['id'] . '/materiais');
    }

    if ($sol['status'] !== 'pendente') {
        $this->flash('Esta solicitação não está mais pendente.');
        $this->redirect('/projetos/' . $projeto['id'] . '/materiais/solicitacoes');
    }

    $this->sol->aprovar($id, (int) $_SESSION['usuario']['id']);
    $this->audit->registrar('material_solicitacao_aprovada', 'solicitacoes_materiais', $id, null, null);

    // Notifica solicitante
    (new Notificacao())->criar(
        (int) $sol['solicitante_id'],
        'material',
        'Solicitação aprovada',
        "Sua solicitação de '{$sol['nome_material']}' foi aprovada e aguarda compra.",
        '/projetos/' . $projeto['id'] . '/materiais/solicitacoes'
    );

    $this->flash('Solicitação aprovada.', 'sucesso');
    $this->redirect('/projetos/' . $projeto['id'] . '/materiais/solicitacoes');
}

public function recusarSolicitacao(int $id)
{
    $sol = $this->sol->porId($id);
    if (!$sol) { $this->flash('Solicitação não encontrada.'); $this->redirect('/turmas'); }

    $projeto = $this->projeto->porId((int) $sol['projeto_id']);
    $this->exigirPodeEditar($projeto);
    CsrfMiddleware::validate();

    if ($projeto['encerrado']) {
        $this->flash('Projeto encerrado: materiais não podem mais ser alterados.');
        $this->redirect('/projetos/' . $projeto['id'] . '/materiais');
    }

    if (!in_array($sol['status'], ['pendente', 'aprovada'], true)) {
        $this->flash('Esta solicitação não pode mais ser recusada.');
        $this->redirect('/projetos/' . $projeto['id'] . '/materiais/solicitacoes');
    }

    $motivo = trim($_POST['motivo'] ?? '') ?: 'Sem motivo informado.';
    $this->sol->recusar($id, (int) $_SESSION['usuario']['id'], $motivo);
    $this->audit->registrar('material_solicitacao_recusada', 'solicitacoes_materiais', $id, null,
        ['motivo' => $motivo]);

    (new Notificacao())->criar(
        (int) $sol['solicitante_id'],
        'material',
        'Solicitação recusada',
        "Sua solicitação de '{$sol['nome_material']}' foi recusada: {$motivo}",
        '/projetos/' . $projeto['id'] . '/materiais/solicitacoes'
    );

    $this->flash('Solicitação recusada.', 'sucesso');
    $this->redirect('/projetos/' . $projeto['id'] . '/materiais/solicitacoes');
}

public function comprarSolicitacaoForm(int $id)
{
    $sol = $this->sol->porId($id);
    if (!$sol) { $this->flash('Solicitação não encontrada.'); $this->redirect('/turmas'); }

    $projeto = $this->projeto->porId((int) $sol['projeto_id']);
    $this->exigirPodeEditar($projeto);

    if ($sol['status'] !== 'aprovada') {
        $this->flash('Apenas solicitações aprovadas podem ser marcadas como compradas.');
        $this->redirect('/projetos/' . $projeto['id'] . '/materiais/solicitacoes');
    }

    // Verifica se já existe material com o mesmo nome no projeto
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT id, nome, quantidade, preco, unidade FROM materiais
        WHERE projeto_id = ? AND LOWER(nome) = LOWER(?)
        LIMIT 1
    ");
    $stmt->execute([$projeto['id'], $sol['nome_material']]);
    $materialExistente = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $this->render('materiais/solicitacao_comprar', [
        'projeto'           => $projeto,
        'solicitacao'       => $sol,
        'materialExistente' => $materialExistente,
        'unidades'          => self::UNIDADES,
    ]);
}

public function comprarSolicitacao(int $id)
{
    $sol = $this->sol->porId($id);
    if (!$sol) { $this->flash('Solicitação não encontrada.'); $this->redirect('/turmas'); }

    $projeto = $this->projeto->porId((int) $sol['projeto_id']);
    $this->exigirPodeEditar($projeto);
    CsrfMiddleware::validate();

    if ($projeto['encerrado']) {
        $this->flash('Projeto encerrado: materiais não podem mais ser alterados.');
        $this->redirect('/projetos/' . $projeto['id'] . '/materiais');
    }

    if ($sol['status'] !== 'aprovada') {
        $this->flash('Esta solicitação não está aprovada.');
        $this->redirect('/projetos/' . $projeto['id'] . '/materiais/solicitacoes');
    }

    $qtd   = (float) str_replace(',', '.', $_POST['quantidade'] ?? '0');
    $preco = (float) str_replace(',', '.', $_POST['preco_unitario'] ?? '0');
    $obs   = trim($_POST['observacao'] ?? '') ?: null;

    $erros = [];
    if ($qtd <= 0)  $erros[] = 'Quantidade comprada deve ser maior que zero.';
    if ($preco < 0) $erros[] = 'Preço não pode ser negativo.';

    if ($erros) {
        $this->flash(implode(' | ', $erros));
        $this->redirect('/solicitacoes/' . $id . '/comprar');
    }

    $pdo = Database::getConnection();

    $existente = $this->material->porNome((int) $projeto['id'], $sol['nome_material']);

    if ($existente) {
        // Adiciona ao estoque (preço médio ponderado)
        [$novoPreco, $novaQtd] = $this->material->comprar((int) $existente['id'], $qtd, $preco);
        $materialId = (int) $existente['id'];

        $this->mov->registrar(
            $materialId,
            (int) $_SESSION['usuario']['id'],
            'compra',
            $qtd,
            $preco,
            $obs ?: "Atende solicitação #{$id}"
        );
    } else {
        // Cria novo material já com a quantidade comprada
        $materialId = $this->material->criar([
            'nome'       => $sol['nome_material'],
            'preco'      => $preco,
            'quantidade' => $qtd,
            'unidade'    => $sol['unidade'] ?: null,
            'projeto_id' => (int) $projeto['id'],
        ]);

        $this->mov->registrar(
            $materialId,
            (int) $_SESSION['usuario']['id'],
            'compra',   // entrou no estoque por compra: conta na prestação de contas
            $qtd,
            $preco,
            $obs ?: "Comprado para atender o pedido de {$sol['solicitante_nome']}"
        );
    }

    $this->sol->marcarComprada($id, (int) $_SESSION['usuario']['id'], $materialId);

    $this->audit->registrar('material_solicitacao_comprada', 'solicitacoes_materiais', $id,
        null, ['material_id' => $materialId, 'quantidade' => $qtd, 'preco' => $preco]);

    (new Notificacao())->criar(
        (int) $sol['solicitante_id'],
        'material',
        'Solicitação atendida',
        "Seu pedido de '{$sol['nome_material']}' foi comprado!",
        '/projetos/' . $projeto['id'] . '/materiais'
    );

    $this->flash('Compra registrada e solicitação marcada como comprada.', 'sucesso');
    $this->redirect('/projetos/' . $projeto['id'] . '/materiais/solicitacoes');
}

public function cancelarSolicitacao(int $id)
{
    $sol = $this->sol->porId($id);
    if (!$sol) { $this->flash('Solicitação não encontrada.'); $this->redirect('/turmas'); }

    $projeto = $this->projeto->porId((int) $sol['projeto_id']);
    $this->exigirAcesso($projeto);
    CsrfMiddleware::validate();

    $u = $_SESSION['usuario'];
    $souDono = (int) $sol['solicitante_id'] === (int) $u['id'];
    $souMaster = $u['tipo'] === 'master';

    if (!$souDono && !$souMaster) {
        http_response_code(403);
        exit('Apenas o solicitante pode cancelar.');
    }

    if (!in_array($sol['status'], ['pendente', 'aprovada'], true)) {
        $this->flash('Esta solicitação não pode mais ser cancelada.');
        $this->redirect('/projetos/' . $projeto['id'] . '/materiais/solicitacoes');
    }

    $this->sol->cancelar($id, (int) $u['id']);
    $this->audit->registrar('material_solicitacao_cancelada', 'solicitacoes_materiais', $id, null, null);

    $this->flash('Solicitação cancelada.', 'sucesso');
    $this->redirect('/projetos/' . $projeto['id'] . '/materiais/solicitacoes');
}

public function excluirSolicitacao(int $id)
{
    if (empty($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'master') {
        http_response_code(403);
        exit('Apenas o master pode excluir solicitações.');
    }
    CsrfMiddleware::validate();

    $sol = $this->sol->porId($id);
    if (!$sol) { $this->flash('Solicitação não encontrada.'); $this->redirect('/turmas'); }

    $projetoId = (int) $sol['projeto_id'];
    $this->sol->excluir($id);
    $this->audit->registrar('material_solicitacao_excluida', 'solicitacoes_materiais', $id, $sol, null);

    $this->flash('Solicitação excluída.', 'sucesso');
    $this->redirect('/projetos/' . $projetoId . '/materiais/solicitacoes');
}

/**
 * Notifica representantes e diretores ativos do projeto sobre nova solicitação.
 */
private function notificarResponsaveis(array $projeto, int $solId, string $nome, string $solicitante): void
{
    $pdo = Database::getConnection();

    // Representantes ativos da turma
    $stmt = $pdo->prepare("
        SELECT usuario_id FROM turma_usuarios
        WHERE turma_id = ? AND papel = 'representante' AND ativo = 1
    ");
    $stmt->execute([(int) $projeto['turma_id']]);
    $reps = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Diretores ativos dos grupos do projeto
    $stmt = $pdo->prepare("
        SELECT DISTINCT gd.usuario_id
        FROM grupo_diretores gd
        INNER JOIN grupos g ON g.id = gd.grupo_id
        WHERE g.projeto_id = ? AND gd.ativo = 1
    ");
    $stmt->execute([(int) $projeto['id']]);
    $diretores = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $ids = array_unique(array_merge($reps, $diretores));

    if (!empty($ids)) {
        (new Notificacao())->criarParaVarios(
            $ids,
            'material',
            'Nova solicitação de material',
            "{$solicitante} solicitou '{$nome}'. Aguardando análise.",
            '/projetos/' . (int) $projeto['id'] . '/materiais/solicitacoes'
        );
    }
}

    public function salvarCadastro(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirPodeEditar($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/projetos/' . $projetoId . '/materiais');
        }

        $nome     = trim($_POST['nome'] ?? '');
        $unidade  = trim($_POST['unidade'] ?? '') ?: null;
        $qtd      = (float) str_replace(',', '.', $_POST['quantidade'] ?? '0');
        $preco    = (float) str_replace(',', '.', $_POST['preco'] ?? '0');

        $erros = [];
        if (strlen($nome) < 2)     $erros[] = 'Nome muito curto.';
        elseif ($this->material->porNome($projetoId, $nome)) $erros[] = "Já existe '{$nome}' no estoque. Use \"Comprar\" nele para somar a quantidade.";
        if ($qtd < 0)              $erros[] = 'Quantidade não pode ser negativa.';
        if ($preco < 0)            $erros[] = 'Preço não pode ser negativo.';
        if (!$unidade || !array_key_exists($unidade, self::UNIDADES)) {
            $erros[] = 'Selecione uma unidade válida.';
        }

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/projetos/' . $projetoId . '/materiais/cadastrar');
        }

        $id = $this->material->criar([
            'nome'       => $nome,
            'preco'      => $preco,
            'quantidade' => $qtd,
            'unidade'    => $unidade,
            'projeto_id' => $projetoId,
        ]);

        // Gera movimentação de cadastro
        $this->mov->registrar(
            $id,
            (int) $_SESSION['usuario']['id'],
            'cadastro',
            $qtd,
            $preco,
            'Cadastro inicial'
        );

        $this->audit->registrar('material_cadastrado', 'materiais', $id, null, [
            'nome' => $nome, 'qtd' => $qtd, 'preco' => $preco,
        ]);

        $this->flash('Material cadastrado.', 'sucesso');
        $this->redirect('/projetos/' . $projetoId . '/materiais');
    }

    // ============ USAR ============

    public function usarForm(int $materialId)
    {
        $material = $this->material->porId($materialId);
        if (!$material) { $this->flash('Material não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $material['projeto_id']);
        $this->exigirPodeEditar($projeto);

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/projetos/' . $projeto['id'] . '/materiais');
        }

        $this->render('materiais/usar', [
            'material' => $material,
            'projeto'  => $projeto,
        ]);
    }

    public function usar(int $materialId)
    {
        $material = $this->material->porId($materialId);
        if (!$material) { $this->flash('Material não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $material['projeto_id']);
        $this->exigirPodeEditar($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/projetos/' . $projeto['id'] . '/materiais');
        }

        $qtd = (float) str_replace(',', '.', $_POST['quantidade'] ?? '0');
        $obs = trim($_POST['observacao'] ?? '') ?: null;

        $erros = [];
        if ($qtd <= 0)                          $erros[] = 'Quantidade deve ser maior que zero.';
        if ($qtd > (float) $material['quantidade']) $erros[] = 'Quantidade maior que o estoque disponível (' . $material['quantidade'] . ').';

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/materiais/' . $materialId . '/usar');
        }

        $this->material->usar($materialId, $qtd);

        $this->mov->registrar(
            $materialId,
            (int) $_SESSION['usuario']['id'],
            'uso',
            $qtd,
            (float) $material['preco'],
            $obs
        );

        $this->audit->registrar('material_usado', 'materiais', $materialId,
            ['quantidade' => (float) $material['quantidade']],
            ['quantidade' => (float) $material['quantidade'] - $qtd, 'usado' => $qtd]);

        $this->flash('Retirada registrada: ' . rtrim(rtrim(number_format($qtd, 2, ',', '.'), '0'), ',') . " {$material['unidade']} de {$material['nome']}.", 'sucesso');
        $this->redirect('/projetos/' . $projeto['id'] . '/materiais');
    }

    // ============ COMPRAR ============

    public function comprarForm(int $materialId)
    {
        $material = $this->material->porId($materialId);
        if (!$material) { $this->flash('Material não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $material['projeto_id']);
        $this->exigirPodeEditar($projeto);

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/projetos/' . $projeto['id'] . '/materiais');
        }

        $this->render('materiais/comprar', [
            'material' => $material,
            'projeto'  => $projeto,
        ]);
    }

    public function comprar(int $materialId)
    {
        $material = $this->material->porId($materialId);
        if (!$material) { $this->flash('Material não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $material['projeto_id']);
        $this->exigirPodeEditar($projeto);
        CsrfMiddleware::validate();

        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado.');
            $this->redirect('/projetos/' . $projeto['id'] . '/materiais');
        }

        $qtd   = (float) str_replace(',', '.', $_POST['quantidade'] ?? '0');
        $preco = (float) str_replace(',', '.', $_POST['preco_unitario'] ?? '0');
        $obs   = trim($_POST['observacao'] ?? '') ?: null;

        $erros = [];
        if ($qtd <= 0)   $erros[] = 'Quantidade comprada deve ser maior que zero.';
        if ($preco < 0)  $erros[] = 'Preço não pode ser negativo.';

        if ($erros) {
            $this->flash(implode(' | ', $erros));
            $this->redirect('/materiais/' . $materialId . '/comprar');
        }

        [$novoPreco, $novaQtd] = $this->material->comprar($materialId, $qtd, $preco);

        $this->mov->registrar(
            $materialId,
            (int) $_SESSION['usuario']['id'],
            'compra',
            $qtd,
            $preco,
            $obs
        );

        $this->audit->registrar('material_comprado', 'materiais', $materialId,
            ['preco' => (float) $material['preco'], 'quantidade' => (float) $material['quantidade']],
            ['preco' => $novoPreco, 'quantidade' => $novaQtd, 'comprado' => $qtd]);

        $this->flash(
            sprintf('Compra registrada. Novo preço médio: R$ %s. Estoque: %s.',
                number_format($novoPreco, 2, ',', '.'),
                number_format($novaQtd, 2, ',', '.')),
            'sucesso'
        );
        $this->redirect('/projetos/' . $projeto['id'] . '/materiais');
    }

    // ============ MOVIMENTAÇÕES ============

    public function movimentacoes(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirAcesso($projeto);

        $filtros = [
            'material_id' => $_GET['material_id'] ?? '',
            'tipo'        => $_GET['tipo'] ?? '',
            'de'          => $_GET['de'] ?? '',
            'ate'         => $_GET['ate'] ?? '',
        ];

        $this->render('materiais/movimentacoes', [
            'projeto'      => $projeto,
            'movimentacoes'=> $this->mov->listarPorProjeto($projetoId, $filtros),
            'materiais'    => $this->material->listarPorProjeto($projetoId),
            'tipos'        => self::TIPOS_MOV,
            'filtros'      => $filtros,
        ]);
    }

    // ============ EXCLUIR ============

    public function excluir(int $materialId)
    {
        $material = $this->material->porId($materialId);
        if (!$material) { $this->flash('Material não encontrado.'); $this->redirect('/turmas'); }

        $projeto = $this->projeto->porId((int) $material['projeto_id']);
        $this->exigirPodeEditar($projeto);
        CsrfMiddleware::validate();

        $pid = (int) $projeto['id'];
        if ($projeto['encerrado']) {
            $this->flash('Projeto encerrado: materiais não podem mais ser alterados.');
            $this->redirect('/projetos/' . $pid . '/materiais');
        }
        // Com compras ou retiradas, excluir apagaria a prestação de contas do projeto
        $stmt = Database::getConnection()->prepare("
            SELECT COUNT(*) FROM movimentacoes_materiais WHERE material_id = ? AND tipo_movimentacao <> 'cadastro'
        ");
        $stmt->execute([$materialId]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->flash("'{$material['nome']}' já tem compras ou retiradas no histórico, então não pode ser excluído. O histórico é a prestação de contas do projeto.");
            $this->redirect('/projetos/' . $pid . '/materiais');
        }
        $this->material->excluir($materialId);

        $this->audit->registrar('material_excluido', 'materiais', $materialId, $material, null);
        $this->flash('Material excluído.', 'sucesso');
        $this->redirect('/projetos/' . $pid . '/materiais');
    }

    // ============ HELPERS ============

    private function exigirAcesso(array $projeto): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;
        if ($this->tu->estaAtivo((int) $projeto['turma_id'], (int) $u['id'])) return;
        http_response_code(403); exit('Sem acesso.');
    }

    private function podeEditar(array $projeto): bool
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) return false;
        if ($u['tipo'] === 'master') return true;
        if ($this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id'])) return true;

        $stmt = Database::getConnection()->prepare("
            SELECT 1 FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            WHERE g.projeto_id = ? AND gd.usuario_id = ? AND gd.ativo = 1
            LIMIT 1
        ");
        $stmt->execute([(int) $projeto['id'], (int) $u['id']]);
        return (bool) $stmt->fetchColumn();
    }

    private function exigirPodeEditar(array $projeto): void
    {
        if (!$this->podeEditar($projeto)) {
            http_response_code(403);
            exit('Apenas representante, diretor ou master.');
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