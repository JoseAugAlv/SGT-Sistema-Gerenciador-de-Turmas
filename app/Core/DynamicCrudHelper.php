<?php
// app/Helpers/DynamicCrudHelper.php

class DynamicCrudHelper
{
    /**
     * Registra automaticamente as rotas CRUD para uma tabela
     */
    public static function registerRoutes($router, $table, $controller, $roles = [1, 2])
    {
        $prefix = $table;

        $router->get("/{$prefix}", "{$controller}@index", $roles);
        $router->get("/{$prefix}/criar", "{$controller}@criar", $roles);
        $router->post("/{$prefix}/salvar", "{$controller}@salvar", $roles);
        $router->get("/{$prefix}/editar", "{$controller}@editar", $roles);
        $router->post("/{$prefix}/atualizar", "{$controller}@atualizar", $roles);
        $router->get("/{$prefix}/excluir", "{$controller}@excluir", $roles);
    }

    /**
     * Cria um controller genérico para uma tabela
     */
    public static function createController($table, $fields, $requiredFields = [], $searchFields = [], $allowedRoles = [1, 2])
    {
        $controllerCode = '<?php' . "\n\n";
        $controllerCode .= '// app/Controllers/' . ucfirst($table) . 'Controller.php' . "\n\n";
        $controllerCode .= 'require_once __DIR__ . "/../Core/CrudController.php";' . "\n\n";
        $controllerCode .= 'class ' . ucfirst($table) . 'Controller extends CrudController' . "\n";
        $controllerCode .= '{' . "\n";
        $controllerCode .= '    public function __construct()' . "\n";
        $controllerCode .= '    {' . "\n";
        $controllerCode .= '        $this->table = "' . $table . '";' . "\n";
        $controllerCode .= '        $this->fields = ' . var_export($fields, true) . ';' . "\n";
        $controllerCode .= '        $this->requiredFields = ' . var_export($requiredFields, true) . ';' . "\n";
        $controllerCode .= '        $this->searchFields = ' . var_export($searchFields, true) . ';' . "\n";
        $controllerCode .= '        $this->allowedRoles = ' . var_export($allowedRoles, true) . ';' . "\n";
        $controllerCode .= '        parent::__construct();' . "\n";
        $controllerCode .= '    }' . "\n";
        $controllerCode .= '}' . "\n";

        return $controllerCode;
    }
}