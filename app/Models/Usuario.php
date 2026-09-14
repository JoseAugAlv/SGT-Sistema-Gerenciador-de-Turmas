<?php
// app/Models/Usuario.php
require_once __DIR__ . '/../Core/Model.php';

class Usuario extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'usuarios';
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM usuarios WHERE email_token = ? LIMIT 1");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function emailExiste(string $email, ?int $ignorarId = null): bool
    {
        if ($ignorarId) {
            $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1");
            $stmt->execute([$email, $ignorarId]);
        } else {
            $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
        }
        return (bool) $stmt->fetchColumn();
    }

    public function criar(array $dados): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO usuarios
                (nome, email, senha, tipo, telefone, data_nascimento,
                 email_confirmado, email_token, email_token_expira,
                 primeiro_login, ativo, lgpd_aceito, lgpd_data)
            VALUES
                (:nome, :email, :senha, :tipo, :telefone, :data_nascimento,
                 :email_confirmado, :email_token, :email_token_expira,
                 :primeiro_login, :ativo, :lgpd_aceito, :lgpd_data)
        ");
        $stmt->execute([
            ':nome'                => $dados['nome'],
            ':email'               => $dados['email'],
            ':senha'               => $dados['senha'],
            ':tipo'                => $dados['tipo'] ?? 'aluno',
            ':telefone'            => $dados['telefone'] ?? null,
            ':data_nascimento'     => $dados['data_nascimento'] ?? null,
            ':email_confirmado'    => $dados['email_confirmado'] ?? 0,
            ':email_token'         => $dados['email_token'] ?? null,
            ':email_token_expira'  => $dados['email_token_expira'] ?? null,
            ':primeiro_login'      => $dados['primeiro_login'] ?? 1,
            ':ativo'               => $dados['ativo'] ?? 1,
            ':lgpd_aceito'         => $dados['lgpd_aceito'] ?? 0,
            ':lgpd_data'           => $dados['lgpd_data'] ?? null,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function confirmarEmail(int $id): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE usuarios
            SET email_confirmado = 1, email_token = NULL, email_token_expira = NULL
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    public function atualizarToken(int $id, string $token, string $expira): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE usuarios SET email_token = ?, email_token_expira = ? WHERE id = ?
        ");
        return $stmt->execute([$token, $expira, $id]);
    }

    public function atualizarSenha(int $id, string $hash): bool
    {
        $stmt = $this->conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    public function concluirPrimeiroAcesso(int $id, string $hashSenha): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE usuarios
            SET senha = ?, primeiro_login = 0, lgpd_aceito = 1,
                lgpd_data = NOW(), email_confirmado = 1
            WHERE id = ?
        ");
        return $stmt->execute([$hashSenha, $id]);
    }

    public function atualizarUltimoAcesso(int $id): void
    {
        $this->conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?")
                   ->execute([$id]);
    }

    public function aceitarLgpd(int $id): bool
    {
        $stmt = $this->conn->prepare("UPDATE usuarios SET lgpd_aceito = 1, lgpd_data = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }
}