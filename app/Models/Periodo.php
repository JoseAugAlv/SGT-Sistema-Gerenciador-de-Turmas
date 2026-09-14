<?php
// app/Models/Periodo.php
require_once __DIR__ . '/../Core/Model.php';

class Periodo extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'periodos';
    }

    public function listarAtivos(): array
    {
        return $this->conn->query("
            SELECT * FROM periodos WHERE ativo = 1 ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodos(): array
    {
        return $this->conn->query("
            SELECT * FROM periodos ORDER BY ativo DESC, nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM periodos WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function siglaJaExiste(string $sigla, ?int $ignorarId = null): bool
    {
        if ($ignorarId) {
            $stmt = $this->conn->prepare("SELECT id FROM periodos WHERE sigla = ? AND id != ? LIMIT 1");
            $stmt->execute([$sigla, $ignorarId]);
        } else {
            $stmt = $this->conn->prepare("SELECT id FROM periodos WHERE sigla = ? LIMIT 1");
            $stmt->execute([$sigla]);
        }
        return (bool) $stmt->fetchColumn();
    }

    public function criar(string $nome, string $sigla): int
    {
        $this->conn->prepare("INSERT INTO periodos (nome, sigla) VALUES (?, ?)")
                   ->execute([$nome, strtoupper($sigla)]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizar(int $id, string $nome, string $sigla): bool
    {
        return $this->conn->prepare("UPDATE periodos SET nome = ?, sigla = ? WHERE id = ?")
                          ->execute([$nome, strtoupper($sigla), $id]);
    }

    public function alternarAtivo(int $id): bool
    {
        return $this->conn->prepare("UPDATE periodos SET ativo = 1 - ativo WHERE id = ?")
                          ->execute([$id]);
    }
}