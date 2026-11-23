<?php
// app/Models/DadosCobranca.php
// CPF e endereço de quem compra planos. Um registro por usuário, reaproveitado nas próximas compras.
require_once __DIR__ . '/../Core/Model.php';

class DadosCobranca extends Model
{
    const CAMPOS = ['nome_completo', 'cpf', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf'];

    public function __construct()
    {
        parent::__construct();
        $this->table = 'dados_cobranca';
    }

    public function porUsuario(int $usuarioId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM dados_cobranca WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function salvar(int $usuarioId, array $d): void
    {
        $cols = implode(', ', self::CAMPOS);
        $marc = implode(', ', array_fill(0, count(self::CAMPOS), '?'));
        $upd  = implode(', ', array_map(fn($c) => "{$c} = VALUES({$c})", self::CAMPOS));
        $vals = array_map(fn($c) => $d[$c] ?? null, self::CAMPOS);
        $this->conn->prepare("INSERT INTO dados_cobranca (usuario_id, {$cols}) VALUES (?, {$marc}) ON DUPLICATE KEY UPDATE {$upd}")
                   ->execute(array_merge([$usuarioId], $vals));
    }
}
