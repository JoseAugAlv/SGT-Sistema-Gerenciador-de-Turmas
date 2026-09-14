<?php
// app/Controllers/MaterialController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Material.php';
require_once __DIR__ . '/../Models/MovimentacaoMaterial.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';
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

    public function __construct()
    {
        $this->material = new Material();
        $this->mov      = new MovimentacaoMaterial();
        $this->projeto  = new Projeto();
        $this->tu       = new TurmaUsuario();
        $this->audit    = new Auditoria();
    }

    // ============ LISTAGEM ============

    public function index(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $this->exigirAcesso($projeto);
        $podeEditar = $this->podeEditar($projeto);

        $this->render('materiais/index', [
            'projeto'    => $projeto,
            'materiais'  => $this->material->listarPorProjeto($projetoId),
            'podeEditar' => $podeEditar,
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

                $nome     = trim($_POST['nome'] ?? '');
        $unidade  = trim($_POST['unidade'] ?? '') ?: null;
        $qtd      = (float) str_replace(',', '.', $_POST['quantidade'] ?? '0');
        $preco    = (float) str_replace(',', '.', $_POST['preco'] ?? '0');

        $erros = [];
        if (strlen($nome) < 2)     $erros[] = 'Nome muito curto.';
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

        $this->flash("Retirada registrada ({$qtd} {$material['unidade']}).", 'sucesso');
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