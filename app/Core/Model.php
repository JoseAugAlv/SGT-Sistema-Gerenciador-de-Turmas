<?php
// app/Core/Model.php

require_once __DIR__ . '/../Config/database.php';

class Model
{
    protected $conn;
    protected $table;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    // Buscar todos os registros
    public function all()
    {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar por ID
    public function find($id, $column = 'id')
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Inserir genérico
    public function insert($data)
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute($data);
    }

    // Inserir e retornar ID
    public function insertReturnId($data)
    {
        $this->insert($data);
        return (int) $this->conn->lastInsertId();
    }

    // Atualizar genérico
    public function update($id, $data, $column = 'id')
    {
        $fields = "";

        foreach ($data as $key => $value) {
            $fields .= "{$key} = :{$key}, ";
        }

        $fields = rtrim($fields, ", ");

        $sql = "UPDATE {$this->table} SET {$fields} WHERE {$column} = :id";
        $stmt = $this->conn->prepare($sql);

        $data['id'] = $id;

        return $stmt->execute($data);
    }

    // Deletar
    public function delete($id, $column = 'id')
    {
        $sql = "DELETE FROM {$this->table} WHERE {$column} = :id";
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute(['id' => $id]);
    }

    // Buscar com condições
    public function where($conditions, $orderBy = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $sql .= implode(" AND ", $where);
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar registros
    public function count($conditions = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $sql .= implode(" AND ", $where);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    // Verifica se existe
    public function exists($conditions)
    {
        return $this->count($conditions) > 0;
    }

    // Busca com paginação
    public function paginate($page = 1, $perPage = 20, $conditions = [], $orderBy = null)
    {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $sql .= implode(" AND ", $where);
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}