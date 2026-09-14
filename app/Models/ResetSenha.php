<?php
// app/Models/ResetSenha.php
require_once __DIR__ . '/../Core/Model.php';

class ResetSenha extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'reset_senhas';
    }

    public function criar(int $usuarioId, string $token, string $expira): int
    {
        // Invalida tokens antigos do usuário
        $this->conn->prepare("UPDATE reset_senhas SET usado = 1 WHERE usuario_id = ? AND usado = 0")
                   ->execute([$usuarioId]);

        $stmt = $this->conn->prepare("
            INSERT INTO reset_senhas (usuario_id, token, expira_em) VALUES (?, ?, ?)
        ");
        $stmt->execute([$usuarioId, $token, $expira]);
        return (int) $this->conn->lastInsertId();
    }

    public function buscarValido(string $token): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM reset_senhas
            WHERE token = ? AND usado = 0 AND expira_em > NOW()
            LIMIT 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function marcarUsado(int $id): bool
    {
        return $this->conn->prepare("UPDATE reset_senhas SET usado = 1 WHERE id = ?")
                          ->execute([$id]);
    }
}