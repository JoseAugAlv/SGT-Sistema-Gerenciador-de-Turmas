<?php
// app/Controllers/ExemploController.php

require_once __DIR__ . '/../Core/CrudController.php';

class ExemploController extends CrudController
{
    public function __construct()
    {
        // ============================================================
        // CONFIGURAÇÃO DA TABELA
        // ============================================================
        $this->table = 'exemplo';
        $this->primaryKey = 'id_exemplo';

        // ============================================================
        // CAMPOS DA TABELA (ORDEM PARA LISTA E FORMULÁRIO)
        // ============================================================
        $this->fields = [
            'nome',
            'descricao',
            'status',
            'data_criacao'
        ];

        // ============================================================
        // CAMPOS OBRIGATÓRIOS
        // ============================================================
        $this->requiredFields = [
            'nome'
        ];

        // ============================================================
        // CAMPOS PARA BUSCA
        // ============================================================
        $this->searchFields = [
            'nome',
            'descricao'
        ];

        // ============================================================
        // PERFIS PERMITIDOS
        // ============================================================
        $this->allowedRoles = [1, 2];

        // ============================================================
        // CONFIGURAÇÕES DE EXIBIÇÃO
        // ============================================================
        $this->labelSingular = 'Exemplo';
        $this->labelPlural = 'Exemplos';
        $this->perPage = 20;

        // ============================================================
        // ORDENAÇÃO PADRÃO
        // ============================================================
        $this->defaultSort = 'nome';
        $this->defaultOrder = 'ASC';

        parent::__construct();
    }

    /**
     * Sobrescreve a lista para adicionar dados extras
     */
    protected function getList($limit, $offset, $busca = '')
    {
        // Exemplo com JOIN
        /*
        $sql = "SELECT e.*, u.nome as usuario_nome
                FROM {$this->table} e
                LEFT JOIN usuario u ON e.id_usuario = u.id_usuario";
        */
        return parent::getList($limit, $offset, $busca);
    }

    /**
     * Sobrescreve validação para regras customizadas
     */
    protected function validateData($dados, $id = null)
    {
        parent::validateData($dados, $id);

        // Validação customizada
        if (isset($dados['nome']) && strlen($dados['nome']) < 3) {
            $_SESSION['flash'] = ['tipo' => 'erro', 'mensagem' => 'Nome deve ter no mínimo 3 caracteres.'];
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
}