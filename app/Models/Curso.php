<?php
// app/Models/Curso.php
require_once __DIR__ . '/../Core/Model.php';

class Curso extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'cursos';
    }

    public function listarAtivos(): array
    {
        return $this->conn->query("
            SELECT * FROM cursos WHERE ativo = 1 ORDER BY nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodos(): array
    {
        return $this->conn->query("
            SELECT * FROM cursos ORDER BY ativo DESC, nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM cursos WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function siglaJaExiste(string $sigla, ?int $ignorarId = null): bool
    {
        if ($ignorarId) {
            $stmt = $this->conn->prepare("SELECT id FROM cursos WHERE sigla = ? AND id != ? LIMIT 1");
            $stmt->execute([$sigla, $ignorarId]);
        } else {
            $stmt = $this->conn->prepare("SELECT id FROM cursos WHERE sigla = ? LIMIT 1");
            $stmt->execute([$sigla]);
        }
        return (bool) $stmt->fetchColumn();
    }

    public function criar(string $nome, string $sigla): int
    {
        $this->conn->prepare("INSERT INTO cursos (nome, sigla) VALUES (?, ?)")
                   ->execute([$nome, strtoupper($sigla)]);
        return (int) $this->conn->lastInsertId();
    }

    public function atualizar(int $id, string $nome, string $sigla): bool
    {
        return $this->conn->prepare("UPDATE cursos SET nome = ?, sigla = ? WHERE id = ?")
                          ->execute([$nome, strtoupper($sigla), $id]);
    }

    public function alternarAtivo(int $id): bool
    {
        return $this->conn->prepare("UPDATE cursos SET ativo = 1 - ativo WHERE id = ?")
                          ->execute([$id]);
    }
}