<?php
// app/Core/CrudController.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Core/Model.php';
require_once __DIR__ . '/../Helpers/PaginationHelper.php';

abstract class CrudController
{
    protected $model;
    protected $table;
    protected $primaryKey = 'id';
    protected $perPage = 20;
    protected $allowedRoles = [1, 2];
    protected $fields = [];
    protected $requiredFields = [];
    protected $searchFields = [];
    protected $sortableFields = [];
    protected $defaultSort = '';
    protected $defaultOrder = 'ASC';
    protected $viewPrefix = '';
    protected $routePrefix = '';
    protected $labelSingular = '';
    protected $labelPlural = '';
    protected $excludeFromList = ['senha', 'password', 'token'];
    protected $excludeFromForm = ['senha', 'password', 'token'];

    public function __construct()
    {
        $this->model = new Model();
        $this->model->table = $this->table;
        $this->viewPrefix = $this->viewPrefix ?: $this->table;
        $this->routePrefix = $this->routePrefix ?: $this->table;
        $this->labelSingular = $this->labelSingular ?: ucfirst(substr($this->table, 0, -1));
        $this->labelPlural = $this->labelPlural ?: ucfirst($this->table);
    }

    /**
     * Lista todos os registros
     */
    public function index()
    {
        $this->checkAuth();

        $pagina = (int) ($_GET['pagina'] ?? 1);
        $busca = $_GET['busca'] ?? '';

        $total = $this->getTotal($busca);
        $paginacao = PaginationHelper::paginate($total, $pagina, $this->perPage);
        $registros = $this->getList($this->perPage, $paginacao['offset'], $busca);

        $paginationHtml = PaginationHelper::render(
            App::getBasePath() . '/' . $this->routePrefix,
            $pagina,
            $paginacao['totalPaginas']
        );

        $this->renderView('index', [
            'registros' => $registros,
            'total' => $total,
            'paginationHtml' => $paginationHtml,
            'labelPlural' => $this->labelPlural,
            'fields' => $this->getListFields(),
            'routePrefix' => $this->routePrefix,
        ]);
    }

    /**
     * Formulário de criação
     */
    public function criar()
    {
        $this->checkAuth();

        $this->renderView('criar', [
            'fields' => $this->getFormFields(),
            'labelSingular' => $this->labelSingular,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    /**
     * Salva um novo registro
     */
    public function salvar()
    {
        $this->checkAuth();
        $this->validateCsrf();

        $dados = $this->sanitizeInput($_POST);
        $this->validateData($dados);

        $insertData = $this->prepareInsertData($dados);

        try {
            $id = $this->model->insertReturnId($insertData);

            $this->logAction('criar', "{$this->labelSingular} criado: ID {$id}");

            $_SESSION['flash'] = [
                'tipo' => 'sucesso',
                'mensagem' => "{$this->labelSingular} criado com sucesso!"
            ];

            header('Location: ' . App::getBasePath() . '/' . $this->routePrefix);
            exit;

        } catch (Exception $e) {
            $_SESSION['flash'] = [
                'tipo' => 'erro',
                'mensagem' => "Erro ao criar {$this->labelSingular}: " . $e->getMessage()
            ];
            header('Location: ' . App::getBasePath() . '/' . $this->routePrefix . '/criar');
            exit;
        }
    }

    /**
     * Formulário de edição
     */
    public function editar()
    {
        $this->checkAuth();

        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'ID não informado.'];
            header('Location: ' . App::getBasePath() . '/' . $this->routePrefix);
            exit;
        }

        $registro = $this->model->find($id, $this->primaryKey);

        if (!$registro) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => "{$this->labelSingular} não encontrado."];
            header('Location: ' . App::getBasePath() . '/' . $this->routePrefix);
            exit;
        }

        $this->renderView('editar', [
            'registro' => $registro,
            'fields' => $this->getFormFields($registro),
            'labelSingular' => $this->labelSingular,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    /**
     * Atualiza um registro
     */
    public function atualizar()
    {
        $this->checkAuth();
        $this->validateCsrf();

        $id = (int) ($_POST[$this->primaryKey] ?? 0);

        if (!$id) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'ID não informado.'];
            header('Location: ' . App::getBasePath() . '/' . $this->routePrefix);
            exit;
        }

        $dados = $this->sanitizeInput($_POST);
        $this->validateData($dados, $id);

        $updateData = $this->prepareUpdateData($dados);

        try {
            $this->model->update($id, $updateData, $this->primaryKey);

            $this->logAction('atualizar', "{$this->labelSingular} atualizado: ID {$id}");

            $_SESSION['flash'] = [
                'tipo' => 'sucesso',
                'mensagem' => "{$this->labelSingular} atualizado com sucesso!"
            ];

            header('Location: ' . App::getBasePath() . '/' . $this->routePrefix);
            exit;

        } catch (Exception $e) {
            $_SESSION['flash'] = [
                'tipo' => 'erro',
                'mensagem' => "Erro ao atualizar {$this->labelSingular}: " . $e->getMessage()
            ];
            header('Location: ' . App::getBasePath() . '/' . $this->routePrefix . '/editar?id=' . $id);
            exit;
        }
    }

    /**
     * Exclui (ou desativa) um registro
     */
    public function excluir()
    {
        $this->checkAuth();
        $this->validateCsrf();

        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'ID não informado.'];
            header('Location: ' . App::getBasePath() . '/' . $this->routePrefix);
            exit;
        }

        try {
            // Verifica se tem campo 'ativo' para soft delete
            $registro = $this->model->find($id, $this->primaryKey);
            $colunas = array_keys($registro);

            if (in_array('ativo', $colunas)) {
                $this->model->update($id, ['ativo' => 0], $this->primaryKey);
                $msg = "{$this->labelSingular} desativado: ID {$id}";
            } else {
                $this->model->delete($id, $this->primaryKey);
                $msg = "{$this->labelSingular} excluído: ID {$id}";
            }

            $this->logAction('excluir', $msg);

            $_SESSION['flash'] = [
                'tipo' => 'sucesso',
                'mensagem' => "{$this->labelSingular} removido com sucesso!"
            ];

        } catch (Exception $e) {
            $_SESSION['flash'] = [
                'tipo' => 'erro',
                'mensagem' => "Erro ao remover {$this->labelSingular}: " . $e->getMessage()
            ];
        }

        header('Location: ' . App::getBasePath() . '/' . $this->routePrefix);
        exit;
    }

    // ============================================================
    // MÉTODOS QUE PODEM SER SOBRESCRITOS
    // ============================================================

    /**
     * Busca a lista de registros com paginação
     */
    protected function getList($limit, $offset, $busca = '')
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($busca) && !empty($this->searchFields)) {
            $sql .= " WHERE ";
            $where = [];
            foreach ($this->searchFields as $field) {
                $where[] = "{$field} LIKE ?";
                $params[] = '%' . $busca . '%';
            }
            $sql .= implode(' OR ', $where);
        }

        if (!empty($this->defaultSort)) {
            $sql .= " ORDER BY {$this->defaultSort} {$this->defaultOrder}";
        }

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->model->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total de registros
     */
    protected function getTotal($busca = '')
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";

        if (!empty($busca) && !empty($this->searchFields)) {
            $sql .= " WHERE ";
            $where = [];
            foreach ($this->searchFields as $field) {
                $where[] = "{$field} LIKE ?";
            }
            $sql .= implode(' OR ', $where);
        }

        $stmt = $this->model->conn->prepare($sql);

        if (!empty($busca) && !empty($this->searchFields)) {
            $params = array_fill(0, count($this->searchFields), '%' . $busca . '%');
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Retorna os campos que serão exibidos na lista
     */
    protected function getListFields()
    {
        $fields = [];

        foreach ($this->fields as $field) {
            if (in_array($field, $this->excludeFromList)) {
                continue;
            }
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Retorna os campos para o formulário
     */
    protected function getFormFields($registro = null)
    {
        $fields = [];

        foreach ($this->fields as $field) {
            if (in_array($field, $this->excludeFromForm)) {
                continue;
            }

            $fields[] = [
                'name' => $field,
                'value' => $registro[$field] ?? '',
                'required' => in_array($field, $this->requiredFields),
                'type' => $this->getFieldType($field),
                'label' => $this->getFieldLabel($field),
            ];
        }

        return $fields;
    }

    /**
     * Determina o tipo do campo para o formulário
     */
    protected function getFieldType($field)
    {
        $types = [
            'email' => 'email',
            'senha' => 'password',
            'password' => 'password',
            'data' => 'date',
            'data_nascimento' => 'date',
            'telefone' => 'tel',
            'ativo' => 'checkbox',
        ];

        if (strpos($field, 'data') === 0) {
            return 'date';
        }

        return $types[$field] ?? 'text';
    }

    /**
     * Retorna o label do campo
     */
    protected function getFieldLabel($field)
    {
        $labels = [
            'nome' => 'Nome',
            'email' => 'E-mail',
            'senha' => 'Senha',
            'telefone' => 'Telefone',
            'data_nascimento' => 'Data de Nascimento',
            'ativo' => 'Ativo',
        ];

        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Prepara dados para inserção
     */
    protected function prepareInsertData($dados)
    {
        $data = [];

        foreach ($this->fields as $field) {
            if (in_array($field, $this->excludeFromForm)) {
                continue;
            }

            if (isset($dados[$field])) {
                $data[$field] = $dados[$field];
            }
        }

        return $data;
    }

    /**
     * Prepara dados para atualização
     */
    protected function prepareUpdateData($dados)
    {
        $data = [];

        foreach ($this->fields as $field) {
            if (in_array($field, $this->excludeFromForm)) {
                continue;
            }

            if (array_key_exists($field, $dados)) {
                $data[$field] = $dados[$field];
            }
        }

        return $data;
    }

    /**
     * Valida os dados
     */
    protected function validateData($dados, $id = null)
    {
        $errors = [];

        foreach ($this->requiredFields as $field) {
            if (empty($dados[$field])) {
                $errors[] = "O campo " . $this->getFieldLabel($field) . " é obrigatório.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['flash'] = [
                'tipo' => 'erro',
                'mensagem' => implode('<br>', $errors)
            ];

            $redirect = App::getBasePath() . '/' . $this->routePrefix;
            if ($id) {
                $redirect .= '/editar?id=' . $id;
            } else {
                $redirect .= '/criar';
            }

            header('Location: ' . $redirect);
            exit;
        }
    }

    /**
     * Sanitiza os dados de entrada
     */
    protected function sanitizeInput($dados)
    {
        $sanitized = [];

        foreach ($dados as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Valida token CSRF
     */
    protected function validateCsrf()
    {
        require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';
        CsrfMiddleware::validate();
    }

    /**
     * Verifica autenticação e permissão
     */
    protected function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $role = (int) $_SESSION['usuario']['role'];

        if (!in_array($role, $this->allowedRoles)) {
            http_response_code(403);
            echo "<h1>403 - Acesso Negado</h1>";
            exit;
        }
    }

    /**
     * Renderiza uma view
     */
    protected function renderView($view, $dados = [])
    {
        $basePath = App::getBasePath();
        $appName = App::getName();

        $dados['basePath'] = $basePath;
        $dados['appName'] = $appName;
        $dados['tituloPagina'] = ($view === 'index' ? $this->labelPlural : $this->labelSingular) . ' - ' . $appName;

        $viewPath = __DIR__ . '/../Views/' . $this->viewPrefix . '/' . $view . '.php';

        if (!file_exists($viewPath)) {
            // Tenta usar um template genérico
            $viewPath = __DIR__ . '/../Views/crud/' . $view . '.php';

            if (!file_exists($viewPath)) {
                http_response_code(500);
                echo "View não encontrada: {$view}";
                exit;
            }
        }

        extract($dados);
        require_once __DIR__ . '/../Views/layouts/header.php';
        require_once __DIR__ . '/../Views/layouts/nav.php';
        require $viewPath;
        require_once __DIR__ . '/../Views/layouts/footer.php';
    }

    /**
     * Registra ação no log
     */
    protected function logAction($acao, $detalhes = '')
    {
        require_once __DIR__ . '/../Helpers/SecurityHelper.php';

        $usuarioId = $_SESSION['usuario']['id'] ?? null;

        SecurityHelper::logAuditoria(
            $acao . '_' . $this->table,
            $usuarioId,
            $detalhes,
            'info'
        );
    }
}