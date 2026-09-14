<?php
// app/Models/PreferenciasUsuario.php
require_once __DIR__ . '/../Core/Model.php';

class PreferenciasUsuario extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'preferencias_usuario';
    }

    public function criarPadrao(int $usuarioId): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO preferencias_usuario (usuario_id) VALUES (?)
        ");
        $stmt->execute([$usuarioId]);
        return (int) $this->conn->lastInsertId();
    }

    public function porUsuario(int $usuarioId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM preferencias_usuario WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}