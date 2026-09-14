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

        public function atualizar(int $usuarioId, array $d): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE preferencias_usuario
            SET receber_email = ?, receber_email_prazos = ?,
                receber_email_alertas = ?, receber_email_avaliacoes = ?
            WHERE usuario_id = ?
        ");
        return $stmt->execute([
            (int) !empty($d['receber_email']),
            (int) !empty($d['receber_email_prazos']),
            (int) !empty($d['receber_email_alertas']),
            (int) !empty($d['receber_email_avaliacoes']),
            $usuarioId,
        ]);
    }
    

    public function porUsuario(int $usuarioId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM preferencias_usuario WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}